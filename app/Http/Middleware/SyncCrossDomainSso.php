<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Auth\SsoHandoffController;
use App\Services\CrossDomainSso;
use App\Support\SsoHosts;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps per-host sessions in step with the identity session:
 *  - signed out anywhere → signed out here too
 *  - guests on a landing page get one silent "already signed in on
 *    ternis.link?" check, so the login follows them across domains.
 */
class SyncCrossDomainSso
{
    private const BOT = '/bot|crawl|spider|slurp|preview|facebookexternalhit|embedly|monitor|curl|wget|python|go-http/i';

    public function __construct(private CrossDomainSso $sso) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $request->hasSession()) {
            $loginAt = $request->session()->get('sso_login_at');

            if ($this->sso->isRevoked($user, is_string($loginAt) ? $loginAt : null)) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        if (! $user && $this->shouldProbe($request)) {
            return redirect()->away($request->getSchemeAndHttpHost().'/auth/sso/start?'.http_build_query([
                'to' => '/', 'silent' => 1,
            ]));
        }

        return $next($request);
    }

    private function shouldProbe(Request $request): bool
    {
        return config('domains.sso_silent_check', true)
            && $request->isMethod('GET')
            && $request->path() === '/'
            && ! $request->query->has('sso')
            && ! $request->cookies->has(SsoHandoffController::PROBE_COOKIE)
            && ! SsoHosts::sharesIdentitySession($request->getHost())
            && in_array($request->attributes->get('domain_type'), ['public', 'business', 'ternis', 'public-dashboard', 'partner'], true)
            && str_contains((string) $request->header('Accept'), 'text/html')
            && ! preg_match(self::BOT, (string) $request->userAgent())
            && $request->header('Purpose') !== 'prefetch'
            && ! $request->header('X-Requested-With');
    }
}
