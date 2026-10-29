<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\CrossDomainSso;
use App\Support\Activity;
use App\Support\SsoHosts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Cross-domain login: /auth/sso/{start,issue,consume}.
 *
 *  start   (any host)      guest → bounce to the identity host
 *  issue   (identity host) signed in → mint token and send it back;
 *                          guest → normal login, then resume here
 *  consume (any host)      redeem token → local session
 */
class SsoHandoffController extends Controller
{
    public const PROBE_COOKIE = 'sso_probe';

    public function __construct(private CrossDomainSso $sso) {}

    public function start(Request $request)
    {
        $to = SsoHosts::safePath($request->query('to'));

        if (auth()->check()) {
            return redirect()->away($request->getSchemeAndHttpHost().$to);
        }

        $query = ['host' => $request->getHost(), 'to' => $to];

        if ($request->boolean('silent')) {
            $query['silent'] = 1;
        }

        return redirect()->away($this->identityUrl($request).'/auth/sso/issue?'.http_build_query($query));
    }

    public function issue(Request $request)
    {
        if ($request->getHost() !== SsoHosts::identityHost()) {
            abort(404);
        }

        $host = strtolower((string) $request->query('host'));
        $to = SsoHosts::safePath($request->query('to'));

        if (! SsoHosts::isAllowed($host)) {
            abort(400, 'Unknown target host.');
        }

        $base = $request->getScheme().'://'.$host;

        if (auth()->check()) {
            $loginAt = $request->session()->get('sso_login_at');
            $token = $this->sso->issue(auth()->user(), $host, is_string($loginAt) ? $loginAt : null);

            return redirect()->away($base.'/auth/sso/consume?'.http_build_query(['token' => $token, 'to' => $to]));
        }

        if ($request->boolean('silent')) {
            return redirect()->away($base.'/auth/sso/consume?'.http_build_query(['none' => 1, 'to' => $to]));
        }

        // Real login on the identity host, then come straight back here.
        $request->session()->put('url.intended', $request->fullUrl());

        return redirect()->away($request->getSchemeAndHttpHost().'/login');
    }

    public function consume(Request $request)
    {
        $to = SsoHosts::safePath($request->query('to'));
        $probe = Cookie::make(self::PROBE_COOKIE, '1', 60 * 6, '/', null, $request->isSecure(), true, false, 'lax');

        if ($request->boolean('none')) {
            return redirect()->away($request->getSchemeAndHttpHost().$to)->withCookie($probe);
        }

        $token = $request->query('token');
        $result = is_string($token) ? $this->sso->consume($token, $request->getHost()) : null;

        if ($result === null) {
            return redirect()->away($request->getSchemeAndHttpHost().'/login')
                ->with('error', 'Your sign-in link expired. Please try again.');
        }

        auth()->login($result['user']);
        $request->session()->regenerate();
        $request->session()->put('sso_login_at', $result['login_at']);

        Activity::record(ActivityLog::AUTH_LOGIN, $result['user'], $result['user']);

        return redirect()->away($request->getSchemeAndHttpHost().$to)->withCookie($probe);
    }

    private function identityUrl(Request $request): string
    {
        return $request->getScheme().'://'.SsoHosts::identityHost();
    }
}
