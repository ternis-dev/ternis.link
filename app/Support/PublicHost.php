<?php

namespace App\Support;

/**
 * The public short-link type serves two hosts with their own
 * language and theme: href.nz (English) and meinlink.at (German).
 * Branching on the host keeps every public rule (guest access,
 * quotas, redirects) identical — only the presentation differs.
 */
final class PublicHost
{
    public static function isMeinlink(?string $host = null): bool
    {
        $host ??= request()->getHost();

        return $host === (string) config('domains.meinlink_host', 'meinlink.at');
    }
}
