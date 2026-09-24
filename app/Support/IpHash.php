<?php

namespace App\Support;

/**
 * Privacy-preserving IP hashing for quota counting, unique-visitor
 * dedup and rate limiting. Only equality matters ("same visitor?"),
 * so the raw address never touches the disk.
 *
 * A plain SHA-256 of an IPv4 address is enumerable (2^32 guesses),
 * so set IP_HASH_PEPPER to a long random secret to make stored
 * hashes unreversible on DB theft. Empty pepper = legacy plain
 * SHA-256 (backward compatible). Set it once: rotating the pepper
 * orphans old quota/counter linkage (counters restart, nothing breaks).
 */
final class IpHash
{
    public static function make(?string $ip): ?string
    {
        if ($ip === null || trim($ip) === '') {
            return null;
        }

        $pepper = (string) config('app.ip_hash_pepper', '');

        if ($pepper === '') {
            return hash('sha256', $ip);
        }

        return hash_hmac('sha256', $ip, $pepper);
    }
}
