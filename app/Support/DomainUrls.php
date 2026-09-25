<?php

namespace App\Support;

class DomainUrls
{
    /**
     * Absolute URL on the dashboard host, preserving the current scheme.
     * Used for login/dashboard links rendered on short-link hosts where
     * route('login') would resolve against APP_URL and 404.
     */
    public static function dashboard(string $path = '/login'): string
    {
        return static::onHost(config('domains.dashboard_host', 'dash.ternis.link'), $path);
    }

    /**
     * Absolute URL on the admin host (moderation links in admin
     * notifications).
     */
    public static function admin(string $path = '/'): string
    {
        return static::onHost(config('domains.admin_host', 'admin.ternis.link'), $path);
    }

    private static function onHost(string $host, string $path): string
    {
        $path = '/'.ltrim($path, '/');

        try {
            // Queued notifications run on the console (no HTTP request):
            // fall back to the app URL scheme so mail links stay https.
            $scheme = app()->runningInConsole()
                ? parse_url((string) config('app.url'), PHP_URL_SCHEME)
                : request()->getScheme();
        } catch (\Throwable) {
            $scheme = null;
        }

        $scheme = is_string($scheme) && $scheme !== '' ? $scheme : 'https';

        return "{$scheme}://{$host}{$path}";
    }
}
