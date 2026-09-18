<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
     * Log out the user (local session only).
     */
    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
