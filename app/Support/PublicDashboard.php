<?php

namespace App\Support;

/**
 * Host helpers for the public dashboard (my.href.nz).
 *
 * my.href.nz is the single public-dashboard host. The former hosts
 * (my.ternis.link, my.href.yt) 301 to it, see ResolveDomain. Every
 * public-dashboard URL is built through this class instead of a plain
 * route() call so the host stays configurable (DOMAIN_PUBLIC_DASHBOARD).
 */
class PublicDashboard
{
    /**
     * The public dashboard host (my.href.nz).
     */
    public static function canonicalHost(): string
    {
        return (string) config('domains.public_dashboard_host', 'my.href.nz');
    }

    /**
     * Every host the public dashboard is served on.
     *
     * @return list<string>
     */
    public static function hosts(): array
    {
        return array_values(array_filter([self::canonicalHost()]));
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
     * Host label shown in the header ("my.href.nz").
     */
    public static function label(): string
    {
        return self::currentHost();
    }

    /**
     * View namespace of the dashboard UI.
     */
    public static function viewNamespace(): string
    {
        return 'hnz-dashboard';
    }

    /**
     * Route name prefix of the public dashboard.
     */
    public static function routePrefix(): string
    {
        return 'public-dashboard.';
    }

    /**
     * Route name prefix for a dashboard theme.
     *
     * The shared Livewire components (link table, link form, analytics)
     * render on two surfaces: the dash and the public dashboard.
     */
    public static function routePrefixFor(?string $theme = null): string
    {
        return match ($theme) {
            'public' => self::routePrefix(),
            default => 'dashboard.',
        };
    }

    /**
     * True when the theme renders the public-links surface rather than
     * the dash.
     */
    public static function isPublicTheme(?string $theme = null): bool
    {
        return $theme === 'public';
    }

    /**
     * Build a public-dashboard URL.
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
     * URL of the public dashboard home.
     */
    public static function homeUrl(): string
    {
        return route(rtrim(self::routePrefix(), '.'));
    }
}
