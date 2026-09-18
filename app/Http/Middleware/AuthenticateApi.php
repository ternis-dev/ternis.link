<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\User;
use App\Services\TernisAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    public function __construct(
        private TernisAuthService $authService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['error' => 'Authentication required.'], 401);
        }

        // Strategy 1: Local API key (starts with "tl_" prefix)
        if (str_starts_with($token, 'tl_')) {
            $keyHash = hash('sha256', $token);
            $apiKey = ApiKey::where('key_hash', $keyHash)->first();

            if (! $apiKey || ! $apiKey->isValid()) {
                return response()->json(['error' => 'Invalid or expired API key.'], 401);
            }

            $apiKey->touchLastUsed();
            $request->setUserResolver(fn () => $apiKey->user);
            auth()->setUser($apiKey->user);

            return $next($request);
        }

        // Strategy 2: SSO access token (validate against Ternis Auth userinfo, cached)
        $cacheKey = 'sso_token:'.hash('sha256', $token);

        $userInfo = Cache::remember($cacheKey, 300, function () use ($token) {
            try {
                return $this->authService->getUserInfo($token);
            } catch (\Exception) {
                return null;
            }
        });

        if (! $userInfo) {
            Cache::forget($cacheKey);

            return response()->json(['error' => 'Invalid access token.'], 401);
        }

        $user = User::where('sso_sub', $userInfo['sub'])->first();

        if (! $user) {
            return response()->json(['error' => 'User not found. Please log in via the dashboard first.'], 401);
        }

        $request->setUserResolver(fn () => $user);
        auth()->setUser($user);

        return $next($request);
    }
}
