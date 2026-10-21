<?php

namespace App\Support;

/**
 * Host helpers for the public dashboard (my.ternis.link and its alias
 * host my.href.nz).
 *
 * Both hosts serve the same app, same session and same data. Only the
 * generated URLs and the brand shown in the header differ, so every
 * public-dashboard URL is built through this class instead of a plain
 * route() call — a hard-coded route name would always point at the
 * canonical host.
 */
class PublicDashboard
{
    /**
     * Canonical host (my.ternis.link).
     */
    public static function canonicalHost(): string
    {
        return (string) config('domains.public_dashboard_host', 'my.ternis.link');
    }

    /**
     * Second host serving the same app (my.href.nz). Empty when the
     * alias is disabled.
     */
    public static function aliasHost(): string
    {
        return (string) config('domains.public_dashboard_alias_host', 'my.href.nz');
    }

    /**
     * Every host the public dashboard is served on.
     *
     * @return list<string>
     */
    public static function hosts(): array
    {
        return array_values(array_unique(array_filter([
            self::canonicalHost(),
            self::aliasHost(),
        ])));
    }

    /**
     * The host the current request came in on (falls back to the
     * canonical host outside a request, e.g. queued jobs).
     */
    public static function currentHost(): string
    {
        try {
            $host = request()->getHost();
        } catch (\Throwable) {
            $host = null;
        }

        return in_array($host, self::hosts(), true) ? $host : self::canonicalHost();
    }

    /**
     * Host label shown in the header: "my.ternis.link" or "my.href.nz".
     */
    public static function label(): string
    {
        return self::currentHost();
    }

    /**
     * Route name prefix for the current host. The canonical host keeps
     * the historical "public-dashboard." names; the alias host uses
     * "public-dashboard-alt.".
     */
    public static function routePrefix(): string
    {
        return self::currentHost() === self::canonicalHost()
            ? 'public-dashboard.'
            : 'public-dashboard-alt.';
    }

    /**
     * Build a public-dashboard URL for the current host.
     *
     * @param  mixed  $parameters  Route parameters (array or single value).
     */
    public static function route(string $name, mixed $parameters = []): string
    {
        $parameters = is_array($parameters) ? $parameters : [$parameters];

        if ($name === '' || $name === 'index') {
            return route(rtrim(self::routePrefix(), '.'), $parameters);
        }

        return route(self::routePrefix().$name, $parameters);
    }

    /**
     * URL of the public dashboard home on the current host.
     */
    public static function homeUrl(): string
    {
        return route(rtrim(self::routePrefix(), '.'));
    }

    /**
     * Route name prefix of the legacy public dashboard on the current
     * host ("public-dashboard.legacy." / "public-dashboard-alt.legacy.").
     */
    public static function legacyRoutePrefix(): string
    {
        return self::routePrefix().'legacy.';
    }

    /**
     * URL of the legacy public dashboard home on the current host.
     */
    public static function legacyHomeUrl(): string
    {
        return route(rtrim(self::legacyRoutePrefix(), '.'));
    }

    /**
     * True when the current request is served by the alias host.
     */
    public static function onAliasHost(): bool
    {
        return self::currentHost() === self::aliasHost();
    }
}
