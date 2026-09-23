<?php

use App\Http\Middleware\AuthenticateApi;
use App\Http\Middleware\EnforceDomainAccess;
use App\Http\Middleware\EnsureDomainType;
use App\Http\Middleware\RefreshSsoToken;
use App\Http\Middleware\ResolveDomain;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('v1')
                ->group(base_path('routes/api/v1.php'));
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

        $middleware->alias([
            'resolve.domain' => ResolveDomain::class,
            'enforce.domain' => EnforceDomainAccess::class,
            'ensure.domain' => EnsureDomainType::class,
            'refresh.sso' => RefreshSsoToken::class,
            'auth.api' => AuthenticateApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('v1/*') || $request->expectsJson(),
        );
    })->create();
