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
        $path = '/'.ltrim($path, '/');
        $scheme = request()->getScheme();
        $host = config('domains.dashboard_host', 'dash.ternis.link');

        return "{$scheme}://{$host}{$path}";
    }
}
