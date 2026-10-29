<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Link;
use App\Support\DomainUrls;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class BotProtectionService
{
    /**
     * Check whether bot protection is globally enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) config('bot_protection.enabled', true);
    }

    /**
     * Determine if a visitor should be challenged for this link.
     */
    public function shouldChallenge(Link $link, Request $request): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        // If visitor has already verified as human recently, bypass challenges.
        if ($this->isHumanVerified($request)) {
            return false;
        }

        // 1. Link-level bot protection toggle.
        if ($link->hasBotProtection()) {
            return true;
        }

        // 2. Suspicious user-agent filtering.
        if ($this->shouldFilterUserAgents() && $this->isSuspiciousUserAgent($request->userAgent())) {
            return true;
        }

        return false;
    }

    /**
     * Check if suspicious user-agent screening is enabled.
     */
    public function shouldFilterUserAgents(): bool
    {
        return (bool) config('bot_protection.filter_suspicious_user_agents', true);
    }

    /**
     * Check whether a user-agent string matches known scraper/automation signatures.
     */
    public function isSuspiciousUserAgent(?string $userAgent): bool
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return true;
        }

        $ua = strtolower($userAgent);
        $suspiciousList = (array) config('bot_protection.suspicious_user_agents', []);

        foreach ($suspiciousList as $pattern) {
            $pattern = strtolower(trim((string) $pattern));
            if ($pattern !== '' && str_contains($ua, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the visitor holds a valid, unexpired human verification cookie or session.
     */
    public function isHumanVerified(Request $request): bool
    {
        $cookieName = (string) config('bot_protection.cookie_name', 'tl_human_verified');
        $cookieValue = $request->cookie($cookieName);

        if (is_string($cookieValue) && trim($cookieValue) !== '') {
            try {
                $payload = Crypt::decryptString($cookieValue);
                if (str_contains($payload, '|')) {
                    [$verifiedAt, $token] = explode('|', $payload, 2);
                    $verifiedAt = (int) $verifiedAt;
                    $maxAge = ((int) config('bot_protection.remember_duration_minutes', 120)) * 60;

                    if ((time() - $verifiedAt) <= $maxAge) {
                        return true;
                    }
                }
            } catch (\Throwable) {
                // Invalid or tampered cookie — treat as unverified.
            }
        }

        $sessionVerifiedAt = $request->session()->get('tl_human_verified_at');
        if (is_int($sessionVerifiedAt)) {
            $maxAge = ((int) config('bot_protection.remember_duration_minutes', 120)) * 60;
            if ((time() - $sessionVerifiedAt) <= $maxAge) {
                return true;
            }
        }

        return false;
    }

    /**
     * Issue an encrypted human verification cookie remembering the visitor.
     */
    public function makeHumanVerificationCookie(?string $host = null): Cookie
    {
        $minutes = (int) config('bot_protection.remember_duration_minutes', 120);
        $cookieName = (string) config('bot_protection.cookie_name', 'tl_human_verified');
        $payload = Crypt::encryptString(time().'|'.Str::random(32));

        $cookieDomain = null;
        if (is_string($host) && str_ends_with($host, 'ternis.link')) {
            $cookieDomain = '.ternis.link';
        }

        return cookie(
            $cookieName,
            $payload,
            $minutes,
            path: '/',
            domain: $cookieDomain,
            secure: request()->isSecure(),
            httpOnly: true,
            sameSite: 'lax',
        );
    }

    /**
     * Generate a cryptographic Request ID.
     */
    public function generateRequestId(): string
    {
        return 'rid_'.strtolower((string) Str::ulid());
    }

    /**
     * Build the redirect challenge URL on protected.ternis.link.
     */
    public function buildChallengeUrl(Link $link, Request $request, ?string $requestId = null): string
    {
        $requestId ??= $this->generateRequestId();
        $timestamp = time();
        $signature = $this->signChallenge($link->id, $requestId, $timestamp);

        $path = '/challenge?'.http_build_query([
            'link' => $link->id,
            'rid' => $requestId,
            'ts' => $timestamp,
            'sig' => $signature,
        ]);

        return DomainUrls::protected($path);
    }

    /**
     * Build the dedicated suspicious user-agent challenge URL on protected.ternis.link.
     */
    public function buildUserAgentChallengeUrl(Link $link, Request $request, ?string $requestId = null): string
    {
        $requestId ??= $this->generateRequestId();
        $timestamp = time();
        $signature = $this->signChallenge($link->id, $requestId, $timestamp);

        $path = '/user-agent?'.http_build_query([
            'link' => $link->id,
            'rid' => $requestId,
            'ts' => $timestamp,
            'sig' => $signature,
        ]);

        return DomainUrls::protected($path);
    }

    /**
     * Cryptographically sign challenge parameters.
     */
    public function signChallenge(string $linkId, string $requestId, int $timestamp): string
    {
        $key = (string) config('app.key');

        return hash_hmac('sha256', "{$linkId}:{$requestId}:{$timestamp}", $key);
    }

    /**
     * Verify challenge signature integrity and time window (15 minutes).
     */
    public function verifyChallenge(string $linkId, string $requestId, int $timestamp, string $signature): bool
    {
        if (abs(time() - $timestamp) > 900) {
            return false;
        }

        $expected = $this->signChallenge($linkId, $requestId, $timestamp);

        return hash_equals($expected, $signature);
    }
}
