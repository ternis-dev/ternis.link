<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;

/**
 * Encrypted short-retention IP capture for abuse forensics.
 *
 * Hashes (IpHash) answer every day-to-day question; this exists for
 * the one hashes can't answer — "which network launched this spam
 * wave" — and is therefore kept minimal: encrypted at rest with the
 * app key, auto-deleted after the retention window, off-switch included.
 */
final class IpCapture
{
    public static function enabled(): bool
    {
        return (bool) config('privacy.capture_ips', true);
    }

    public static function retentionDays(): int
    {
        return max(1, (int) config('privacy.ip_retention_days', 30));
    }

    /**
     * Encrypt an IP for storage, or null when capture is disabled or
     * there is nothing to store.
     *
     * Note: model columns using the `encrypted` cast must receive the
     * RAW ip (the cast encrypts on write) — never this method's output.
     */
    public static function encrypt(?string $ip): ?string
    {
        if (! static::enabled() || $ip === null || trim($ip) === '') {
            return null;
        }

        return Crypt::encryptString($ip);
    }
}
