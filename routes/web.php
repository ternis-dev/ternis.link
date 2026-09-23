<?php

use App\Http\Controllers\Auth\TernisAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RedirectController;
use App\Http\Middleware\EnforceDomainAccess;
use App\Http\Middleware\RefreshSsoToken;
use App\Http\Middleware\ResolveDomain;
use App\Models\ApiVersion;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| All routes go through ResolveDomain middleware to detect host context.
|--------------------------------------------------------------------------
*/
Route::middleware(ResolveDomain::class)->group(function () {

    /*
    |----------------------------------------------------------------------
    | Auth routes (dash.ternis.link)
    |----------------------------------------------------------------------
    */
    Route::get('/login', [TernisAuthController::class, 'showLogin'])->name('login');
    Route::get('/auth/redirect', [TernisAuthController::class, 'redirect'])->name('auth.redirect');
    Route::get('/auth/silent', [TernisAuthController::class, 'silent'])->name('auth.silent');
    Route::get('/auth/callback', [TernisAuthController::class, 'callback'])->name('auth.callback');
    Route::post('/logout', [TernisAuthController::class, 'logout'])->name('logout');

    if (app()->environment('local', 'testing')) {
        Route::get('/auth/demo', [TernisAuthController::class, 'demoLogin'])->name('auth.demo');
    }

    /*
    |----------------------------------------------------------------------
    | Dashboard routes (dash.ternis.link) — require SSO login
    |----------------------------------------------------------------------
    */
    Route::middleware(['auth', RefreshSsoToken::class, EnforceDomainAccess::class])->prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/links', [DashboardController::class, 'links'])->name('dashboard.links');
        Route::get('/links/create', [DashboardController::class, 'createLink'])->name('dashboard.links.create');
        Route::get('/links/{link}', [DashboardController::class, 'showLink'])->name('dashboard.links.show');
        Route::get('/api-keys', [DashboardController::class, 'apiKeys'])->name('dashboard.api-keys');
    });

    /*
    |----------------------------------------------------------------------
    | Redirect routes (href.nz, href.re, ternis.link, etc.)
    |----------------------------------------------------------------------
    */
    Route::middleware(EnforceDomainAccess::class)->group(function () {
        // Landing page
        Route::get('/', function () {
            $type = request()->attributes->get('domain_type');
            if ($type === 'dashboard') {
                return redirect()->route('dashboard');
            }
            if ($type === 'api') {
                $latest = ApiVersion::latestVersion();

                return redirect("/v{$latest}/", 302);
            }

            return view('landing.index');
        })->name('home');

        // Direct URL redirects (preferred)
        Route::get('/url/{url}', [RedirectController::class, 'directUrl'])
            ->where('url', '.*')
            ->name('redirect.url');

        // Alternative direct URL redirect
        Route::get('/go/{url}', [RedirectController::class, 'goUrl'])
            ->where('url', '.*')
            ->name('redirect.go');

        // Slug or URL detection — MUST be last (catch-all)
        Route::get('/{input}', [RedirectController::class, 'resolve'])
            ->where('input', '[^/]+')
            ->name('redirect.resolve');
    });
});
