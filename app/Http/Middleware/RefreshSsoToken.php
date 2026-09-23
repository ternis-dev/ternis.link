<?php

namespace App\Http\Middleware;

use App\Services\TernisAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Transparently refresh expired SSO access tokens for session users.
 * Falls back to a logged-out redirect to login when the refresh
 * token is missing or rejected. Users without an SSO identity
 * (e.g. local demo logins) pass through untouched.
 */
class RefreshSsoToken
{
    public function __construct(
        private TernisAuthService $authService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->oauthIdentity && $user->oauthIdentity->isTokenExpired()) {
            if (! $this->authService->refreshAccessToken($user)) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Your session has expired. Please log in again.');
            }
        }

        return $next($request);
    }
}
