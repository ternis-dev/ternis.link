<?php

use App\Exceptions\DailyLinkLimitException;
use App\Http\Middleware\ApiV2Response;
use App\Http\Middleware\AuthenticateApi;
use App\Http\Middleware\EnforceDomainAccess;
use App\Http\Middleware\EnsureApiVersion;
use App\Http\Middleware\EnsureDomainType;
use App\Http\Middleware\LogApiRequest;
use App\Http\Middleware\RefreshSsoToken;
use App\Http\Middleware\ResolveDomain;
use App\Http\Middleware\StartResilientSession;
use App\Models\ErrorEncounter;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('v1')
                ->group(base_path('routes/api/v1.php'));
            Route::middleware('api')->prefix('v2')->group(base_path('routes/api/v2.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Caddy terminates TLS and proxies to PHP-FPM. Trust it so
        // $request->ip() / isSecure() reflect the real client — critical
        // for IP-hash analytics and IP-based rate limiting.
        // Override with TRUSTED_PROXIES="10.0.0.1,10.0.0.2" if needed.
        $trusted = env('TRUSTED_PROXIES', '*');
        $middleware->trustProxies(at: $trusted === '*' ? '*' : array_map('trim', explode(',', (string) $trusted)));

        $middleware->prepend(ResolveDomain::class);
        $middleware->web(replace: [
            StartSession::class => StartResilientSession::class,
        ]);
        // Keep host/version checks and response formatting outside auth,
        // throttling and route bindings, so errors use the same API contract.
        $before = ThrottleRequests::class;
        foreach ([AuthenticateApi::class, LogApiRequest::class, EnsureApiVersion::class, ApiV2Response::class, EnsureDomainType::class] as $class) {
            $middleware->prependToPriorityList(before: $before, prepend: $class);
            $before = $class;
        }

        // Guests hitting an auth route bounce to the SAME host's /login:
        // route('login') resolves against APP_URL (href.nz) and would
        // funnel my.href.nz guests through the wrong host's SSO flow
        // (session + PKCE live where the flow starts).
        $middleware->redirectGuestsTo(fn (Request $request) => $request->getSchemeAndHttpHost().'/login');

        // The auth API of the public dashboard is called cross-origin:
        // a browser client has no CSRF token for it. It is protected by
        // the CORS origin allowlist (config/cors.php) instead, and the
        // endpoints only ever act on the caller's own session.
        $middleware->validateCsrfTokens(except: [
            'auth/api/*',
        ]);

        $middleware->alias([
            'resolve.domain' => ResolveDomain::class,
            'enforce.domain' => EnforceDomainAccess::class,
            'ensure.domain' => EnsureDomainType::class,
            'ensure.api-version' => EnsureApiVersion::class,
            'refresh.sso' => RefreshSsoToken::class,
            'auth.api' => AuthenticateApi::class,
            'log.api-request' => LogApiRequest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('v1/*') || $request->is('v2', 'v2/*') || $request->expectsJson(),
        );

        $exceptions->render(function (DailyLinkLimitException $e, Request $request) {
            if (! $request->is('v2/*')) {
                return null;
            }

            $retryAfter = max(1, (int) now()->diffInSeconds(now()->addDay()->startOfDay()));

            return response()->json([
                'code' => 'daily_link_limit_reached',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
                'quota' => $e->quota,
            ], 429, ['Retry-After' => (string) $retryAfter]);
        });

        // Persist one error_encounters row per rendered error response so
        // production failures stay inspectable (http_code, message,
        // host/path, user, IP hash). A `report` hook can't do this:
        // HttpExceptions live in Laravel's internal dont-report list.
        // Skips validation noise + health probes; record() never throws.
        $exceptions->respond(function ($response, Throwable $e) {
            if (! $e instanceof ValidationException
                && ! in_array(request()->path(), ['healthz', 'readyz', 'up'], true)) {
                ErrorEncounter::record($e);
            }

            return $response;
        });
    })->create();
