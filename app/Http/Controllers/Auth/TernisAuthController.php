<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\User;
use App\Services\TernisAuthService;
use App\Support\Activity;
use App\Support\PublicHost;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TernisAuthController extends Controller
{
    public function __construct(
        private TernisAuthService $authService,
    ) {}

    /**
     * Show login page with "Login with Ternis Auth" button.
     *
     * The public short-link host (href.nz) gets its own sketch-styled
     * page in the landing's look; the SSO handshake itself still runs
     * on the dashboard host (session + PKCE live there), so the page
     * links over instead of starting OAuth locally.
     */
    public function showLogin(Request $request)
    {
        if (auth()->check()) {
            return $this->homeRedirect($request);
        }

        if ($request->attributes->get('domain_type') === 'public-dashboard') {
            return view('auth.login-public-dashboard');
        }

        if ($request->attributes->get('domain_type') === 'public') {
            if (PublicHost::isMeinlink($request->getHost())) {
                return view('auth.login-meinlink');
            }
            if (PublicHost::isClicked($request->getHost())) {
                return view('auth.login-clicked', ['locale' => PublicHost::resolveClickedLocale()]);
            }
            if (PublicHost::isYt($request->getHost())) {
                return view('auth.login-yt');
            }

            return view('auth.login-public');
        }

        return view('auth.login');
    }

    /**
     * Redirect the user to Ternis Auth for authorization.
     *
     * SSO always runs with the single registered dash callback URI.
     * An allowlisted `origin` (admin / public-dashboard base URL) is
     * remembered so the finished login can be handed back via a
     * one-time ticket (see callback() + consume()).
     */
    public function redirect(Request $request)
    {
        $flow = $this->beginFlow($request);

        $url = $this->authService->getAuthorizationUrl($flow['state_param'], $flow['challenge']);

        return redirect()->away($url);
    }

    /**
     * Silent SSO check: redirects with prompt=none so an existing
     * Ternis Auth session yields a code immediately, otherwise the
     * callback receives ?error=login_required.
     */
    public function silent(Request $request)
    {
        if (auth()->check()) {
            return $this->homeRedirect($request);
        }

        $flow = $this->beginFlow($request);

        return redirect()->away($this->authService->getSilentAuthUrl($flow['state_param'], $flow['challenge']));
    }

    /**
     * Handle the OAuth callback from Ternis Auth.
     */
    public function callback(Request $request)
    {
        // Check for SSO errors (e.g. prompt=none → login_required)
        if ($request->has('error')) {
            $loginUrl = $request->getSchemeAndHttpHost().'/login';
            if ($request->query('error') === 'login_required') {
                return redirect()->away($loginUrl);
            }

            return redirect()->away($loginUrl)
                ->with('error', 'Authentication failed: '.$request->query('error_description', 'Unknown error'));
        }

        // Resolve the flow: self-contained encrypted state first (no
        // session needed), legacy session comparison as fallback.
        $flow = $this->resolveFlow($request);

        if ($flow === null) {
            Log::warning('SSO callback without verifiable state', [
                'host' => $request->getHost(),
                'has_code' => $request->query->has('code'),
                'has_state_param' => $request->query->has('state'),
            ]);

            return redirect()->away($request->getSchemeAndHttpHost().'/login')
                ->with('error', 'Your sign-in session expired before Ternis Auth sent you back (cookies blocked, private window, or a retried page). Please sign in again.');
        }

        $codeVerifier = $flow['verifier'];

        // A stale, reused, or hand-pasted code (e.g. reloading a callback
        // URL) makes the provider reject the exchange — send the user back
        // to login with a friendly message instead of a 500. Each step is
        // caught separately so logs (and error_encounters) show exactly
        // where SSO broke: token exchange, userinfo, or local provisioning.
        try {
            // Exchange code for tokens
            $tokenData = $this->authService->exchangeCode(
                $request->query('code'),
                $codeVerifier,
            );
        } catch (\Throwable $e) {
            $this->logSsoFailure('token-exchange', $e);

            return redirect()->away($request->getSchemeAndHttpHost().'/login')
                ->with('error', 'Sign-in failed at Ternis Auth (token step). Please try again.');
        }

        try {
            // Fetch user info
            $userInfo = $this->authService->getUserInfo($tokenData['access_token']);

            // Find or create local user
            $user = $this->authService->findOrCreateUser($tokenData, $userInfo);
        } catch (\Throwable $e) {
            $this->logSsoFailure('userinfo-provisioning', $e);

            return redirect()->away($request->getSchemeAndHttpHost().'/login')
                ->with('error', 'Sign-in failed while fetching your profile. Please try again.');
        }

        // Log in via Laravel session
        auth()->login($user);

        // Freshness marker for destructive self-service (account
        // deletion requires a login younger than 15 minutes).
        $request->session()->put('sso_login_at', now()->toIso8601String());

        // Signing back in cancels a scheduled self-deletion.
        if ($user->deletion_requested_at !== null) {
            $user->update(['deletion_requested_at' => null]);
            $request->session()->flash('info', 'Welcome back — your scheduled account deletion was cancelled.');
        }

        Activity::record(ActivityLog::AUTH_LOGIN, $user, $user);

        // Flows that started on another dashboard host (admin,
        // public-dashboard) finish here and hand the login back with a
        // one-time ticket — the provider only knows this callback.
        $origin = $flow['origin'];

        if (is_string($origin) && $origin !== '' && $this->originHost($origin) !== $request->getHost()) {
            $ticket = Str::random(64);
            Cache::put('sso-ticket:'.$ticket, $user->id, 120);

            return redirect()->away(rtrim($origin, '/').'/auth/consume?ticket='.$ticket);
        }

        return redirect()->intended($this->homeUrl($request));
    }

    /**
     * Consume a one-time SSO ticket issued by the dash callback and
     * sign the user in on this host (admin / public-dashboard only).
     * Tickets are single-use and expire after 120 seconds.
     */
    public function consume(Request $request)
    {
        $ticket = $request->query('ticket');
        $userId = is_string($ticket) && $ticket !== '' ? Cache::pull('sso-ticket:'.$ticket) : null;
        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            return redirect()->away($request->getSchemeAndHttpHost().'/login')
                ->with('error', 'That sign-in link expired or was already used. Please sign in again.');
        }

        auth()->login($user);

        $request->session()->put('sso_login_at', now()->toIso8601String());

        if ($user->deletion_requested_at !== null) {
            $user->update(['deletion_requested_at' => null]);
            $request->session()->flash('info', 'Welcome back — your scheduled account deletion was cancelled.');
        }

        Activity::record(ActivityLog::AUTH_LOGIN, $user, $user, ['via' => 'sso-ticket']);

        return redirect()->intended($this->homeUrl($request));
    }

    /**
     * Log an SSO step failure with provider status/body context (never
     * secrets — failure bodies carry error codes, not tokens) and record
     * it for the error_encounters table via report().
     */
    private function logSsoFailure(string $step, \Throwable $e): void
    {
        $status = $e instanceof RequestException
            ? $e->response?->status()
            : null;

        Log::warning('SSO callback failure', [
            'step' => $step,
            'provider_status' => $status,
            'error' => $e->getMessage(),
        ]);

        report($e);
    }

    /**
     * Home URL for the current host: my.href.nz guests land back on the
     * public dashboard, admin guests on the admin console, everyone
     * else on dash.ternis.link.
     */
    private function homeUrl(Request $request): string
    {
        if ($request->attributes->get('domain_type') === 'public-dashboard') {
            return route('public-dashboard');
        }

        if ($request->attributes->get('domain_type') === 'admin') {
            return route('admin.dashboard');
        }

        return route('dashboard');
    }

    /**
     * Start an SSO flow. Returns the opaque `state` parameter for the
     * provider and the PKCE challenge for it.
     *
     * The state is self-contained: state, verifier, origin, and expiry
     * travel inside the APP_KEY-encrypted parameter, so the callback
     * verifies without any server session. A plain copy stays in the
     * session as fallback for flows started before this deploy.
     *
     * @return array{state_param: string, challenge: string}
     */
    private function beginFlow(Request $request): array
    {
        $state = Str::random(40);
        $verifier = $this->authService->generateCodeVerifier();

        $request->session()->put('oauth_state', $state);
        $request->session()->put('oauth_code_verifier', $verifier);

        $origin = $this->validatedOrigin($request->query('origin'));

        if ($origin !== null) {
            $request->session()->put('sso_origin', $origin);
        }

        $payload = json_encode([
            's' => $state,
            'v' => $verifier,
            'o' => $origin,
            'exp' => time() + 600,
        ]);

        return [
            'state_param' => encrypt($payload),
            'challenge' => $this->authService->generateCodeChallenge($verifier),
        ];
    }

    /**
     * Resolve the callback to its flow: verifier plus origin, if any.
     *
     * Preferred path decrypts the self-contained state (session-free).
     * Fallback compares a plain state against the session copy left by
     * older flows. Anything else (tampered, expired, absent) is null —
     * the caller restarts login instead of 403ing.
     *
     * @return array{verifier: string, origin: ?string}|null
     */
    private function resolveFlow(Request $request): ?array
    {
        $param = $request->query('state');

        if (is_string($param) && $param !== '') {
            try {
                $flow = json_decode(decrypt($param), true);
            } catch (\Throwable) {
                $flow = null;
            }

            if (is_array($flow)
                && isset($flow['s'], $flow['v']) && is_string($flow['s']) && is_string($flow['v'])
                && isset($flow['exp']) && is_int($flow['exp']) && $flow['exp'] > time()) {
                $origin = isset($flow['o']) && is_string($flow['o']) ? $this->validatedOrigin($flow['o']) : null;

                return ['verifier' => $flow['v'], 'origin' => $origin];
            }
        }

        $stored = $request->session()->pull('oauth_state');

        if (is_string($stored) && $stored !== '' && is_string($param) && hash_equals($stored, $param)) {
            $verifier = $request->session()->pull('oauth_code_verifier');

            if (is_string($verifier) && $verifier !== '') {
                $origin = $this->validatedOrigin($request->session()->pull('sso_origin'));

                return ['verifier' => $verifier, 'origin' => $origin];
            }
        }

        return null;
    }

    /**
     * Hosts allowed to receive a handed-back SSO login. Only dashboard
     * hosts that serve their own login — never arbitrary URLs.
     *
     * @return list<string>
     */
    private function ssoHosts(): array
    {
        return [
            (string) config('domains.dashboard_host', 'dash.ternis.link'),
            (string) config('domains.admin_host', 'admin.ternis.link'),
            (string) config('domains.public_dashboard_host', 'my.href.nz'),
        ];
    }

    /**
     * Validate an `origin` base URL (scheme + host of a known SSO host).
     * Returns the normalized base or null.
     */
    private function validatedOrigin(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $parts = parse_url($value);

        if (! is_array($parts) || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! in_array($host, $this->ssoHosts(), true)) {
            return null;
        }

        return ($parts['scheme'] ?? 'https').'://'.$host;
    }

    /**
     * Host part of a validated origin base URL, or null.
     */
    private function originHost(string $origin): ?string
    {
        $host = parse_url($origin, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : null;
    }

    private function homeRedirect(Request $request)
    {
        return redirect()->away($this->homeUrl($request));
    }

    /**
     * Temporary local developer demo login (only available in local/testing environments).
     */
    public function demoLogin(Request $request)
    {
        if (! app()->environment('local', 'testing')) {
            abort(404);
        }

        $roleParam = $request->query('role', 'admin');

        $roleMap = [
            'admin' => [
                'sub' => '00000000-0000-0000-0000-000000000001',
                'name' => 'Demo Admin',
                'email' => 'admin@demo.local',
                'role' => UserRole::Admin,
                'type' => 'ternis_member',
                'plan' => 'business',
            ],
            'family' => [
                'sub' => '00000000-0000-0000-0000-000000000002',
                'name' => 'Demo Family',
                'email' => 'family@demo.local',
                'role' => UserRole::Family,
                'type' => 'ternis_member',
                'plan' => 'family',
            ],
            'partner' => [
                'sub' => '00000000-0000-0000-0000-000000000003',
                'name' => 'Demo Partner',
                'email' => 'partner@demo.local',
                'role' => UserRole::Partner,
                'type' => 'partner',
                'plan' => 'partner',
            ],
            'user' => [
                'sub' => '00000000-0000-0000-0000-000000000004',
                'name' => 'Demo User',
                'email' => 'user@demo.local',
                'role' => UserRole::User,
                'type' => 'general',
                'plan' => 'free',
            ],
        ];

        $profile = $roleMap[$roleParam] ?? $roleMap['admin'];
        $plan = Plan::where('name', $profile['plan'])->first()
            ?? Plan::first();

        $user = User::updateOrCreate(
            ['sso_sub' => $profile['sub']],
            [
                'name' => $profile['name'],
                'email' => $profile['email'],
                'avatar_url' => null,
                'sso_user_type' => $profile['type'],
                'role' => $profile['role'],
                'plan_id' => $plan?->id,
            ]
        );

        auth()->login($user);

        $request->session()->put('sso_login_at', now()->toIso8601String());

        Activity::record(ActivityLog::AUTH_LOGIN, $user, $user, ['via' => 'demo']);

        return $this->homeRedirect($request);
    }

    /**
     * Log out locally, then continue to Ternis Auth RP-initiated
     * logout when enabled (TERNIS_AUTH_END_SESSION=true).
     */
    public function logout(Request $request)
    {
        $user = auth()->user();

        if ($user) {
            Activity::record(ActivityLog::AUTH_LOGOUT, $user, $user);
        }

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $endSessionUrl = $this->authService->getEndSessionUrl();

        if ($endSessionUrl) {
            return redirect()->away($endSessionUrl);
        }

        return redirect('/');
    }
}
