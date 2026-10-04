<?php

namespace App\Support;

/**
 * The public short-link type serves multiple hosts with their own
 * language and theme: href.nz (English), meinlink.at (German),
 * and clicked.at (newsletter/email click-tracking focus).
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

    public static function isClicked(?string $host = null): bool
    {
        $host ??= request()->getHost();

        return $host === (string) config('domains.clicked_host', 'clicked.at');
    }
}
