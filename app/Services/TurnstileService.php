<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileService
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Determine whether Turnstile verification is actively configured.
     */
    public function isEnabled(): bool
    {
        return ! empty(config('services.turnstile.secret'));
    }

    /**
     * Verify a Turnstile response token with Cloudflare's siteverify API.
     */
    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        try {
            $payload = [
                'secret' => (string) config('services.turnstile.secret'),
                'response' => $token,
            ];

            if (! empty($ip)) {
                $payload['remoteip'] = $ip;
            }

            $response = Http::asForm()
                ->timeout(5)
                ->post(self::VERIFY_URL, $payload);

            if (! $response->successful()) {
                Log::warning('Turnstile verification request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $data = $response->json();

            return (bool) ($data['success'] ?? false);
        } catch (\Throwable $e) {
            Log::error('Turnstile verification exception', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
