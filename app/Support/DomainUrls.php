<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
     * Absolute URL on the public dashboard host (my.href.nz), preserving
     * the current scheme. Used for public-link dashboard links rendered
     * on short-link hosts.
     */
    public static function publicDashboard(string $path = '/'): string
    {
        return static::onHost(config('domains.public_dashboard_host', 'my.href.nz'), $path);
    }

    /**
     * Hostnames managed on the public dashboard (my.href.nz): the
     * no-account shorteners href.nz + meinlink.at + href.yt (+ the QR
     * studio host). clicked.at stays on dash.ternis.link by design.
     *
     * @return list<string>
     */
    public static function publicDashboardHostnames(): array
    {
        return array_values(array_unique(array_filter([
            (string) config('domains.public_host', 'href.nz'),
            (string) config('domains.meinlink_host', 'meinlink.at'),
            (string) config('domains.yt_host', 'href.yt'),
            (string) config('domains.qr_host', 'qr.href.nz'),
        ])));
    }

    /**
     * Absolute URL on the admin host (moderation links in admin
     * notifications).
     */
    public static function admin(string $path = '/'): string
    {
        return static::onHost(config('domains.admin_host', 'admin.ternis.link'), $path);
    }

    /**
     * Absolute URL on the internal gateway host (int.ternis.link).
     */
    public static function internal(string $path = '/'): string
    {
        return static::onHost(config('domains.internal_host', 'int.ternis.link'), $path);
    }

    /**
     * Check if the given (or current) host is the internal gateway host.
     */
    public static function isInternal(?string $host = null): bool
    {
        try {
            $host ??= request()->getHost();
        } catch (\Throwable) {
            $host = null;
        }

        return $host === (string) config('domains.internal_host', 'int.ternis.link');
    }

    /**
     * Absolute URL pointing to the int.ternis.link/impressum gateway,
     * including the current domain context and language.
     */
    public static function impressum(?string $domain = null, ?string $lang = null): string
    {
        try {
            $domain ??= request()->getHost();
        } catch (\Throwable) {
            $domain = null;
        }

        $domain = is_string($domain) && $domain !== '' ? $domain : 'ternis.link';
        $lang = is_string($lang) && $lang !== '' ? $lang : (app()->getLocale() ?: 'en');

        $query = http_build_query([
            'domain' => $domain,
            'lang' => $lang,
        ]);

        return "https://int.ternis.link/impressum?{$query}";
    }

    /**
     * Handle /impressum and /imprint requests:
     * Redirects to the canonical imprint on ternis.dev preserving ?domain={domain}&{other}
     */
    public static function handleImpressumRedirect(Request $request): RedirectResponse
    {
        $domain = $request->query('domain');
        if (! is_string($domain) || trim($domain) === '') {
            $referer = (string) $request->header('referer');
            if ($referer !== '') {
                $refHost = parse_url($referer, PHP_URL_HOST);
                if (is_string($refHost) && $refHost !== '' && ! str_contains($refHost, 'ternis.dev')) {
                    $domain = $refHost;
                }
            }
        }

        if (! is_string($domain) || trim($domain) === '' || $domain === 'int.ternis.link') {
            $currentHost = $request->getHost();
            $domain = ($currentHost !== 'int.ternis.link' && $currentHost !== '') ? $currentHost : 'ternis.link';
        }

        $lang = $request->query('lang');
        if (! is_string($lang) || ! in_array(strtolower($lang), ['de', 'en'], true)) {
            if (str_ends_with($domain, '.at') || str_ends_with($domain, '.de')) {
                $lang = 'de';
            } else {
                $preferred = $request->getPreferredLanguage(['en', 'de']);
                $lang = in_array($preferred, ['en', 'de'], true) ? $preferred : (app()->getLocale() === 'de' ? 'de' : 'en');
            }
        }

        $query = $request->query();
        $query['domain'] = $domain;
        unset($query['lang']);

        $target = "https://ternis.dev/{$lang}/legal/imprint";
        if (! empty($query)) {
            $target .= '?'.http_build_query($query);
        }

        return redirect()->away($target, 302);
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
