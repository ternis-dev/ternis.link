<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ApiKey;
use App\Models\ApiVersion;
use App\Support\Activity;
use App\Support\DomainUrls;
use App\Support\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    /**
     * GET /v1/api-keys — List the user's own keys (newest first).
     *
     * The raw token is never persisted and therefore never listed;
     * only prefix + metadata are returned (`key_hash` stays hidden).
     */
    public function index(Request $request): JsonResponse
    {
        $keys = $request->user()->apiKeys()
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($keys);
    }

    /**
     * POST /v1/api-keys — Create a key, returning the raw token ONCE.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'show_on_dashboard' => ['sometimes', 'boolean'],
        ]);

        $rawKey = 'tl_'.Str::random(48);

        $key = ApiKey::create([
            'user_id' => $request->user()->id,
            'key_hash' => ApiKey::hashToken($rawKey),
            'key_prefix' => substr($rawKey, 0, 8),
            'api_version' => ApiVersion::latestVersion(),
            'name' => $validated['name'],
            'show_on_dashboard' => $validated['show_on_dashboard'] ?? true,
        ]);

        Activity::record(ActivityLog::API_KEY_CREATED, $request->user(), $key, [
            'name' => $key->name,
            'key_prefix' => $key->key_prefix,
            'show_on_dashboard' => $key->show_on_dashboard,
            'via' => 'api',
        ]);

        Notifier::security(
            $request->user(),
            'New API key created',
            ["A new API key “{$key->name}” ({$key->key_prefix}…) was created on your account."],
            DomainUrls::dashboard('/api-keys'),
            'View API keys',
        );

        return response()->json([
            ...$key->fresh()->toArray(),
            'api_key' => $rawKey,
        ], 201);
    }

    /**
     * PATCH /v1/api-keys/{key} — Rename a key or toggle its
     * dashboard visibility (`show_on_dashboard`). Links are never
     * moved or deleted; hiding only changes where they are listed.
     */
    public function update(Request $request, ApiKey $apiKey): JsonResponse
    {
        if ($apiKey->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'You do not own this API key.');
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'show_on_dashboard' => ['sometimes', 'required', 'boolean'],
        ]);

        if ($validated === []) {
            return response()->json($apiKey, 200);
        }

        $apiKey->update($validated);

        Activity::record(ActivityLog::API_KEY_UPDATED, $request->user(), $apiKey, [
            'name' => $apiKey->name,
            'key_prefix' => $apiKey->key_prefix,
            'show_on_dashboard' => $apiKey->show_on_dashboard,
            'via' => 'api',
        ]);

        return response()->json($apiKey->fresh(), 200);
    }

    /**
     * DELETE /v1/api-keys/{key} — Revoke an owned key (links preserved).
     */
    public function destroy(Request $request, ApiKey $apiKey): JsonResponse
    {
        if ($apiKey->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'You do not own this API key.');
        }

        if ($apiKey->revoked_at === null) {
            $apiKey->update(['revoked_at' => now()]);

            Activity::record(ActivityLog::API_KEY_REVOKED, $request->user(), $apiKey, [
                'name' => $apiKey->name,
                'key_prefix' => $apiKey->key_prefix,
                'via' => 'api',
            ]);

            Notifier::security(
                $apiKey->user ?? $request->user(),
                'API key revoked',
                ["The API key “{$apiKey->name}” ({$apiKey->key_prefix}…) on your account was revoked."],
                DomainUrls::dashboard('/api-keys'),
                'View API keys',
            );
        }

        return response()->json(null, 204);
    }
}
