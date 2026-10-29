<?php

namespace App\Services;

use App\Models\User;
use App\Support\SsoHosts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * One-time login handoff between ternis.link-linked hosts.
 *
 * The identity host mints a short-lived, single-use token bound to
 * (user, target host); the target host consumes it and starts its own
 * local session. A logout marker lets every host drop its local session
 * once the user signs out anywhere.
 */
class CrossDomainSso
{
    private const TTL = 60;

    /** @return string opaque token */
    public function issue(User $user, string $host, ?string $loginAt = null): string
    {
        $token = Str::random(64);

        Cache::put($this->key($token), [
            'user_id' => $user->getKey(),
            'host' => strtolower($host),
            'login_at' => $loginAt ?? now()->toIso8601String(),
        ], self::TTL);

        return $token;
    }

    /**
     * @return array{user: User, login_at: string}|null
     */
    public function consume(string $token, string $host): ?array
    {
        $payload = Cache::pull($this->key($token));

        if (! is_array($payload) || ($payload['host'] ?? null) !== strtolower($host) || ! SsoHosts::isAllowed($host)) {
            return null;
        }

        $user = User::find($payload['user_id']);

        return $user ? ['user' => $user, 'login_at' => (string) $payload['login_at']] : null;
    }

    public function markLoggedOut(User $user): void
    {
        Cache::put($this->revokedKey($user), now()->getTimestamp(), now()->addDays(30));
    }

    /** True when the user signed out after this local session began. */
    public function isRevoked(User $user, ?string $loginAt): bool
    {
        $revoked = Cache::get($this->revokedKey($user));

        if (! $revoked) {
            return false;
        }

        $started = $loginAt ? strtotime($loginAt) : 0;

        return $started === false || $started < (int) $revoked;
    }

    private function key(string $token): string
    {
        return 'sso:handoff:'.hash('sha256', $token);
    }

    private function revokedKey(User $user): string
    {
        return 'sso:revoked:'.$user->getKey();
    }
}
