<?php

namespace App\Support;

use Illuminate\Http\Request;

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

    /**
     * Resolve the preferred locale for clicked.at (en or de).
     * Order of precedence:
     * 1. Explicit query parameter (?lang=de or ?locale=en)
     * 2. Persisted cookie (clicked_locale)
     * 3. Session state (clicked_locale)
     * 4. HTTP Accept-Language request header
     * 5. Default fallback ('en')
     */
    public static function resolveClickedLocale(?Request $request = null): string
    {
        $request ??= request();

        // 1. Explicit query parameter
        $param = $request->query('lang') ?? $request->query('locale');
        if (is_string($param)) {
            $param = strtolower(trim($param));
            if (in_array($param, ['en', 'de'], true)) {
                return $param;
            }
        }

        // 2. Cookie preference
        $cookie = $request->cookie('clicked_locale');
        if (is_string($cookie) && in_array(strtolower($cookie), ['en', 'de'], true)) {
            return strtolower($cookie);
        }

        // 3. Session preference
        $session = session('clicked_locale');
        if (is_string($session) && in_array(strtolower($session), ['en', 'de'], true)) {
            return strtolower($session);
        }

        // 4. HTTP Accept-Language header (e.g. de-AT, de;q=0.9, en;q=0.8)
        $preferred = $request->getPreferredLanguage(['en', 'de']);
        if (is_string($preferred) && in_array($preferred, ['en', 'de'], true)) {
            return $preferred;
        }

        // 5. Default fallback
        return 'en';
    }
}
