<?php

declare(strict_types=1);

namespace App\Services;

use AltchaOrg\Altcha\Algorithm\Sha;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\VerifySolutionOptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Self-hosted proof-of-work humanity check (Altcha) for the public
 * guest form. Zero external requests — the widget JS is bundled
 * locally and challenges are minted + verified here.
 *
 * Always on (no keys to misconfigure): the HMAC secret defaults to
 * the app key and can be overridden via ALTCHA_SECRET. Solved
 * payloads are single-use — each submission burns its challenge, so
 * one solved PoW can't mint unlimited links.
 */
class AltchaService
{
    /**
     * Challenge lifetime in seconds (also the single-use mark TTL).
     */
    public const EXPIRES_SECONDS = 600;

    public function secret(): string
    {
        $secret = (string) (config('services.altcha.secret') ?? '');

        return $secret !== '' ? $secret : (string) config('app.key');
    }

    private function altcha(): Altcha
    {
        return new Altcha(hmacSignatureSecret: $this->secret());
    }

    /**
     * Mint a fresh challenge for the widget (GET /altcha/challenge).
     *
     * @return array<string, mixed>
     */
    public function challenge(): array
    {
        $challenge = $this->altcha()->createChallenge(new CreateChallengeOptions(
            algorithm: new Sha(),
            cost: (int) config('services.altcha.cost', 1000),
            keyPrefix: '00',
            expiresAt: time() + self::EXPIRES_SECONDS,
        ));

        return $challenge->toArray();
    }

    /**
     * Verify a solved payload: HMAC signature, expiry, real PoW —
     * plus single-use (replays are rejected even inside the window).
     */
    public function verify(?string $payload): bool
    {
        $payload = trim((string) $payload);

        if ($payload === '' || strlen($payload) > 8192) {
            return false;
        }

        try {
            $result = $this->altcha()->verifySolution(new VerifySolutionOptions(
                payload: $payload,
                algorithm: new Sha(),
            ));
        } catch (\Throwable $e) {
            Log::notice('Altcha payload rejected', ['message' => $e->getMessage()]);

            return false;
        }

        if (! $result->verified) {
            return false;
        }

        $usedKey = 'altcha:used:'.sha1($payload);

        if (Cache::has($usedKey)) {
            return false;
        }

        Cache::put($usedKey, true, self::EXPIRES_SECONDS);

        return true;
    }
}
