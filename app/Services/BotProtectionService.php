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

        // Link configured to let ANYONE click it (unrestricted automated access, crawlers, bots)
        if ($link->allowsBots()) {
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
     * Return the exact matching suspicious pattern or null.
     */
    public function getMatchedSuspiciousPattern(?string $userAgent): ?string
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return 'empty_user_agent';
        }

        $ua = strtolower($userAgent);
        $suspiciousList = (array) config('bot_protection.suspicious_user_agents', []);

        foreach ($suspiciousList as $pattern) {
            $p = strtolower(trim((string) $pattern));
            if ($p !== '' && str_contains($ua, $p)) {
                return $pattern;
            }
        }

        return null;
    }

    /**
     * Inspect human verification cookie state and cryptographic payload.
     */
    public function inspectHumanCookie(Request $request): array
    {
        $cookieName = (string) config('bot_protection.cookie_name', 'tl_human_verified');
        $raw = $request->cookie($cookieName);

        if (! is_string($raw) || trim($raw) === '') {
            return [
                'has_cookie' => false,
                'is_valid' => false,
                'status' => 'missing',
                'description' => 'No human verification cookie present.',
            ];
        }

        try {
            $payload = Crypt::decryptString($raw);
            if (! str_contains($payload, '|')) {
                return [
                    'has_cookie' => true,
                    'is_valid' => false,
                    'status' => 'malformed',
                    'description' => 'Cookie decrypted but structure is missing delimiter.',
                ];
            }

            [$verifiedAt, $token] = explode('|', $payload, 2);
            $verifiedAt = (int) $verifiedAt;
            $ageSeconds = time() - $verifiedAt;
            $maxAgeSeconds = ((int) config('bot_protection.remember_duration_minutes', 120)) * 60;
            $ttlRemaining = max(0, $maxAgeSeconds - $ageSeconds);
            $isExpired = $ageSeconds > $maxAgeSeconds;

            return [
                'has_cookie' => true,
                'is_valid' => ! $isExpired,
                'status' => $isExpired ? 'expired' : 'valid',
                'verified_at' => date('Y-m-d H:i:s', $verifiedAt),
                'verified_at_timestamp' => $verifiedAt,
                'age_seconds' => $ageSeconds,
                'ttl_remaining_seconds' => $ttlRemaining,
                'token_prefix' => substr($token, 0, 8).'...',
                'description' => $isExpired
                    ? "Expired {$ageSeconds}s ago (max lifetime {$maxAgeSeconds}s)."
                    : "Valid and trusted. Expires in {$ttlRemaining}s.",
            ];
        } catch (\Throwable) {
            return [
                'has_cookie' => true,
                'is_valid' => false,
                'status' => 'tampered',
                'description' => 'Decryption failed: signature tampered or encrypted with different APP_KEY.',
            ];
        }
    }

    /**
     * Provide a comprehensive, step-by-step diagnostic trace of the protection decision.
     */
    public function explainProtectionDecision(?Link $link, Request $request, ?string $customUserAgent = null, ?bool $overrideHasCookie = null): array
    {
        $ua = $customUserAgent !== null ? $customUserAgent : $request->userAgent();
        $cookieInspection = $this->inspectHumanCookie($request);
        $isHuman = $overrideHasCookie !== null ? $overrideHasCookie : $cookieInspection['is_valid'];

        $trace = [];

        // Step 1: Global master switch
        $globalEnabled = $this->isEnabled();
        $trace[] = [
            'step' => 1,
            'rule' => 'Global Bot Protection Master Switch',
            'passed' => $globalEnabled,
            'detail' => $globalEnabled ? 'Master switch is active.' : 'Master switch is disabled (bypass all).',
        ];

        if (! $globalEnabled) {
            return [
                'decision' => 'ALLOW',
                'action' => 'Direct 302 Redirect',
                'reason' => 'Bot protection is globally disabled in configuration.',
                'matched_rule' => 'master_switch_disabled',
                'cookie' => $cookieInspection,
                'trace' => $trace,
            ];
        }

        // Step 2: Link allows bots exemption
        if ($link && $link->allowsBots()) {
            $trace[] = [
                'step' => 2,
                'rule' => 'Link allows_bots Exemption',
                'passed' => false,
                'detail' => 'Link explicitly configured with allows_bots = true (unrestricted crawler access).',
            ];

            return [
                'decision' => 'ALLOW',
                'action' => 'Direct 302 Redirect',
                'reason' => 'Link owner configured allows_bots=true to permit search engines & automated scrapers without challenges.',
                'matched_rule' => 'link_allows_bots',
                'cookie' => $cookieInspection,
                'trace' => $trace,
            ];
        }

        $trace[] = [
            'step' => 2,
            'rule' => 'Link allows_bots Exemption',
            'passed' => true,
            'detail' => 'Link does not allow unrestricted bots.',
        ];

        // Step 3: Cryptographic Proof of Human (Cookie)
        $ttl = (int) ($cookieInspection['ttl_remaining_seconds'] ?? (config('bot_protection.remember_duration_minutes', 120) * 60));
        $trace[] = [
            'step' => 3,
            'rule' => 'Human Verification Session & Cookie',
            'passed' => $isHuman,
            'detail' => $isHuman
                ? "Valid verification cookie detected (expires in {$ttl}s)."
                : ($cookieInspection['has_cookie'] ? "Verification cookie present but {$cookieInspection['status']}." : 'No verification cookie present.'),
        ];

        if ($isHuman) {
            return [
                'decision' => 'ALLOW',
                'action' => 'Direct 302 Redirect (Human Verified Session)',
                'reason' => "Visitor holds a valid human verification cookie (TTL remaining: {$ttl}s).",
                'matched_rule' => 'human_verified_session',
                'cookie' => $cookieInspection,
                'trace' => $trace,
            ];
        }

        // Step 4: Link-Level Bot Protection toggle
        $linkHasBotProtection = $link ? $link->hasBotProtection() : false;
        $trace[] = [
            'step' => 4,
            'rule' => 'Link-Level Bot Protection Flag',
            'passed' => $linkHasBotProtection,
            'detail' => $linkHasBotProtection
                ? 'Link has bot_protection = true enabled by owner.'
                : 'Link does not have explicit bot_protection toggle enabled.',
        ];

        if ($linkHasBotProtection) {
            return [
                'decision' => 'CHALLENGE_TURNSTILE',
                'action' => 'Redirect to protected.ternis.link/challenge',
                'reason' => 'Link owner enabled mandatory Bot Protection for this URL.',
                'matched_rule' => 'link_bot_protection_flag',
                'cookie' => $cookieInspection,
                'trace' => $trace,
            ];
        }

        // Step 5: Suspicious User-Agent filtering
        $filterUa = $this->shouldFilterUserAgents();
        $matchedPattern = $this->getMatchedSuspiciousPattern($ua);
        $isSuspiciousUa = $filterUa && ($matchedPattern !== null);

        $trace[] = [
            'step' => 5,
            'rule' => 'Edge User-Agent Screening',
            'passed' => $isSuspiciousUa,
            'detail' => $isSuspiciousUa
                ? "Client User-Agent matches suspicious automation signature: '{$matchedPattern}'."
                : ($filterUa ? 'Client User-Agent does not match suspicious automation blocklist.' : 'UA screening disabled in config.'),
        ];

        if ($isSuspiciousUa) {
            return [
                'decision' => 'CHALLENGE_USER_AGENT',
                'action' => 'Redirect to protected.ternis.link/user-agent',
                'reason' => "User-Agent matched known automation/scraper signature pattern '{$matchedPattern}'.",
                'matched_rule' => 'suspicious_user_agent_signature',
                'matched_pattern' => $matchedPattern,
                'cookie' => $cookieInspection,
                'trace' => $trace,
            ];
        }

        // Step 6: Standard clean traffic
        return [
            'decision' => 'ALLOW',
            'action' => 'Direct 302 Redirect',
            'reason' => 'Clean browser signature, no suspicious patterns detected, and target link does not enforce Turnstile.',
            'matched_rule' => 'clean_standard_traffic',
            'cookie' => $cookieInspection,
            'trace' => $trace,
        ];
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

        if ($request->hasSession()) {
            $sessionVerifiedAt = $request->session()->get('tl_human_verified_at');
            if (is_int($sessionVerifiedAt)) {
                $maxAge = ((int) config('bot_protection.remember_duration_minutes', 120)) * 60;
                if ((time() - $sessionVerifiedAt) <= $maxAge) {
                    return true;
                }
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
     * Clear / expire the human verification cookie.
     */
    public function makeExpiredHumanVerificationCookie(?string $host = null): Cookie
    {
        $cookieName = (string) config('bot_protection.cookie_name', 'tl_human_verified');
        $cookieDomain = null;
        if (is_string($host) && str_ends_with($host, 'ternis.link')) {
            $cookieDomain = '.ternis.link';
        }

        return cookie()->forget($cookieName, path: '/', domain: $cookieDomain);
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
