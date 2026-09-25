<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileService
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public const ACTION = 'shorten_link';

    /**
     * A partial configuration must fail closed instead of silently
     * accepting submissions without server-side verification.
     */
    public function isEnabled(): bool
    {
        $siteKey = trim((string) config('services.turnstile.key'));
        $secretKey = trim((string) config('services.turnstile.secret'));

        return $siteKey !== '' || $secretKey !== '';
    }

    /**
     * Verify a Turnstile response token with Cloudflare's Siteverify API.
     */
    public function verify(
        ?string $token,
        ?string $ip = null,
        string $expectedAction = self::ACTION,
        ?string $expectedHostname = null,
    ): bool {
        if (! $this->isEnabled()) {
            return true;
        }

        $secretKey = trim((string) config('services.turnstile.secret'));
        $token = trim((string) $token);

        if ($secretKey === '' || $token === '' || strlen($token) > 2048) {
            return false;
        }

        try {
            $payload = [
                'secret' => $secretKey,
                'response' => $token,
            ];

            if ($ip !== null && $ip !== '') {
                $payload['remoteip'] = $ip;
            }

            $response = Http::asForm()
                ->timeout(5)
                ->post(self::VERIFY_URL, $payload);

            if (! $response->successful()) {
                Log::warning('Turnstile verification request failed', [
                    'status' => $response->status(),
                ]);

                return false;
            }

            $data = $response->json();

            if (! is_array($data) || ! ($data['success'] ?? false)) {
                Log::notice('Turnstile token rejected', [
                    'error_codes' => is_array($data) ? ($data['error-codes'] ?? []) : ['invalid-response'],
                ]);

                return false;
            }

            if (! hash_equals($expectedAction, (string) ($data['action'] ?? ''))) {
                return false;
            }

            $expectedHostname = strtolower(trim((string) $expectedHostname));
            $verifiedHostname = strtolower(trim((string) ($data['hostname'] ?? '')));

            return $expectedHostname === ''
                || hash_equals($expectedHostname, $verifiedHostname);
        } catch (\Throwable $e) {
            Log::error('Turnstile verification exception', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
