<?php

namespace App\Support;

/**
 * Host rules for cross-domain SSO.
 *
 * Browsers never share cookies between registrable domains (href.nz,
 * meinlink.at, ternis.link, …), so a login on the identity host
 * (dash.ternis.link) is handed to every other configured host through a
 * one-time token (see CrossDomainSso). Only hosts from config/domains.php
 * (plus wildcard subdomains of its roots) may receive a handoff.
 */
class SsoHosts
{
    /** Host that owns the ternis.link session and runs the OAuth flow. */
    public static function identityHost(): string
    {
        return (string) config('domains.dashboard_host', 'dash.ternis.link');
    }

    /**
     * Explicitly configured hosts that can receive a login handoff
     * (everything in the domain map except pure API hosts).
     *
     * @return list<string>
     */
    public static function hosts(): array
    {
        $hosts = [];

        foreach ((array) config('domains.map', []) as $host => $type) {
            if ($type !== 'api') {
                $hosts[] = (string) $host;
            }
        }

        return array_values(array_unique($hosts));
    }

    public static function isAllowed(?string $host): bool
    {
        if (! is_string($host) || $host === '' || ! preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/i', $host)) {
            return false;
        }

        $host = strtolower($host);

        if (in_array($host, self::hosts(), true)) {
            return true;
        }

        foreach ((array) config('domains.wildcard_roots', []) as $root) {
            if (str_ends_with($host, '.'.$root)) {
                return true;
            }
        }

        return false;
    }

    /**
     * True when the host already shares the identity session cookie
     * (SESSION_DOMAIN, e.g. .ternis.link) and needs no handoff.
     */
    public static function sharesIdentitySession(?string $host): bool
    {
        $domain = ltrim((string) config('session.domain'), '.');

        if (! is_string($host) || $domain === '') {
            return $host === self::identityHost();
        }

        $host = strtolower($host);

        return $host === $domain || str_ends_with($host, '.'.$domain);
    }

    /** Local, same-origin path only — never an open redirect. */
    public static function safePath(mixed $path): string
    {
        if (! is_string($path) || $path === '' || $path[0] !== '/'
            || str_starts_with($path, '//') || str_contains($path, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $path)) {
            return '/';
        }

        return $path;
    }
}
