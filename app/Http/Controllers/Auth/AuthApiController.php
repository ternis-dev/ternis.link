<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ApiSessionToken;
use App\Models\User;
use App\Support\Activity;
use App\Support\PublicDashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Auth API of the public dashboard (my.ternis.link / my.href.nz).
 *
 * A cross-origin client (an allowlisted web app, a browser extension,
 * an embed) cannot read the session cookie of another origin, so it
 * asks these two questions instead:
 *
 *   GET  /auth/api/session  — am I signed in, and who am I?
 *   POST /auth/api/token    — exchange that session for a short-lived
 *                             bearer token for the regular REST API.
 *
 * The endpoints are CORS-protected: only origins listed in
 * config('cors.allowed_origins') get an answer, and the session
 * cookie travels with the request so the client can be told who the
 * visitor is.
 */
class AuthApiController extends Controller
{
    /**
     * Default lifetime of a minted token.
     */
    public const DEFAULT_TTL = 900;

    /**
     * Shortest lifetime a client may ask for. Below a minute the token
     * would expire before a client could realistically use it.
     */
    public const MIN_TTL = 60;

    /**
     * Hard upper bound for a client-requested lifetime.
     */
    public const MAX_TTL = 3600;

    /**
     * Session state of the caller. Always 200 — an anonymous visitor
     * is a valid answer, not an error.
     */
    public function session(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'authenticated' => $user !== null,
            'host' => PublicDashboard::label(),
            'user' => $user ? $this->userPayload($user) : null,
        ]);
    }

    /**
     * Mint a short-lived bearer token for the signed-in user.
     */
    public function token(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $ttl = $this->resolveTtl($request);

        $raw = ApiSessionToken::PREFIX.Str::random(48);

        $token = ApiSessionToken::create([
            'user_id' => $user->id,
            'token_hash' => ApiSessionToken::hashToken($raw),
            'token_prefix' => substr($raw, 0, 8),
            'origin' => $request->headers->get('Origin'),
            'expires_at' => now()->addSeconds($ttl),
        ]);

        Activity::record(
            ActivityLog::AUTH_TOKEN_ISSUED,
            $user,
            $token,
            [
                'via' => 'auth-api',
                'origin' => $token->origin,
                'expires_at' => $token->expires_at->toIso8601String(),
            ]
        );

        $this->enforceTokenLimit($user);

        return response()->json([
            'token' => $raw,
            'token_type' => 'Bearer',
            'expires_at' => $token->expires_at->toIso8601String(),
            'expires_in' => $ttl,
            'host' => PublicDashboard::label(),
            'user' => $this->userPayload($user),
        ], 201);
    }

    /**
     * Revoke the token presented with this request.
     */
    public function revoke(Request $request): JsonResponse
    {
        $raw = $request->bearerToken();

        if (! $raw || ! str_starts_with($raw, ApiSessionToken::PREFIX)) {
            return response()->json(['message' => 'Nothing to revoke.'], 404);
        }

        $token = ApiSessionToken::where('token_hash', ApiSessionToken::hashToken($raw))->first();

        if (! $token || $token->user_id !== $request->user()?->id) {
            return response()->json(['message' => 'Nothing to revoke.'], 404);
        }

        $token->update(['revoked_at' => now()]);

        Activity::record(
            ActivityLog::AUTH_TOKEN_REVOKED,
            $request->user(),
            $token,
            ['via' => 'auth-api']
        );

        return response()->json(null, 204);
    }

    /**
     * Requested lifetime, clamped to the allowed window.
     */
    private function resolveTtl(Request $request): int
    {
        $requested = (int) $request->input('ttl', self::DEFAULT_TTL);

        if ($requested <= 0) {
            return self::DEFAULT_TTL;
        }

        return max(self::MIN_TTL, min($requested, self::MAX_TTL));
    }

    /**
     * Keep the number of live session tokens per account bounded.
     *
     * Without a cap a script holding the session cookie could mint
     * thousands of tokens and fill the table. The oldest ones are
     * revoked instead — a client that still holds one of them gets a
     * clear 401 rather than a silent surprise.
     *
     * Tokens of this account that are already dead (expired or
     * revoked) are removed right away: they can never authenticate
     * anything again, and dropping them keeps the table proportional
     * to the number of live sessions instead of to the request rate.
     */
    private function enforceTokenLimit(User $user): void
    {
        ApiSessionToken::where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('expires_at', '<=', now())
                    ->orWhereNotNull('revoked_at');
            })
            ->delete();

        $max = (int) config('privacy.session_token_max_active', 5);

        if ($max <= 0) {
            return;
        }

        $active = ApiSessionToken::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            // ULIDs are lexicographically time-ordered, so this picks
            // the genuinely oldest token. Sorting by created_at would
            // tie for tokens minted in the same second and revoke an
            // arbitrary one of them.
            ->orderByDesc('id')
            ->pluck('id');

        if ($active->count() <= $max) {
            return;
        }

        ApiSessionToken::whereIn('id', $active->slice($max)->all())
            ->update(['revoked_at' => now()]);
    }

    /**
     * Public description of the signed-in user.
     *
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'plan' => $user->plan?->name,
        ];
    }
}
