<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Services\TernisAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TernisAuthController extends Controller
{
    public function __construct(
        private TernisAuthService $authService,
    ) {}

    /**
     * Show login page with "Login with Ternis Auth" button.
     */
    public function showLogin()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Redirect the user to Ternis Auth for authorization.
     */
    public function redirect(Request $request)
    {
        $state = Str::random(40);
        $codeVerifier = $this->authService->generateCodeVerifier();
        $codeChallenge = $this->authService->generateCodeChallenge($codeVerifier);

        // Store in session for callback verification
        $request->session()->put('oauth_state', $state);
        $request->session()->put('oauth_code_verifier', $codeVerifier);

        $url = $this->authService->getAuthorizationUrl($state, $codeChallenge);

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
            return redirect()->route('dashboard');
        }

        $state = Str::random(40);
        $codeVerifier = $this->authService->generateCodeVerifier();
        $codeChallenge = $this->authService->generateCodeChallenge($codeVerifier);

        $request->session()->put('oauth_state', $state);
        $request->session()->put('oauth_code_verifier', $codeVerifier);

        return redirect()->away($this->authService->getSilentAuthUrl($state, $codeChallenge));
    }

    /**
     * Handle the OAuth callback from Ternis Auth.
     */
    public function callback(Request $request)
    {
        // Check for SSO errors (e.g. prompt=none → login_required)
        if ($request->has('error')) {
            if ($request->query('error') === 'login_required') {
                return redirect()->route('login');
            }

            return redirect()->route('login')
                ->with('error', 'Authentication failed: '.$request->query('error_description', 'Unknown error'));
        }

        // Verify state
        $storedState = $request->session()->pull('oauth_state');
        if (! $storedState || $storedState !== $request->query('state')) {
            abort(403, 'Invalid OAuth state. Possible CSRF attack.');
        }

        $codeVerifier = $request->session()->pull('oauth_code_verifier');
        if (! $codeVerifier) {
            abort(403, 'Missing OAuth code verifier in session.');
        }

        // Exchange code for tokens
        $tokenData = $this->authService->exchangeCode(
            $request->query('code'),
            $codeVerifier,
        );

        // Fetch user info
        $userInfo = $this->authService->getUserInfo($tokenData['access_token']);

        // Find or create local user
        $user = $this->authService->findOrCreateUser($tokenData, $userInfo);

        // Log in via Laravel session
        auth()->login($user);

        return redirect()->intended(route('dashboard'));
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

        return redirect()->route('dashboard');
    }

    /**
     * Log out locally, then continue to Ternis Auth RP-initiated
     * logout when enabled (TERNIS_AUTH_END_SESSION=true).
     */
    public function logout(Request $request)
    {
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
