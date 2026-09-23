<?php

use App\Http\Controllers\Auth\TernisAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\RedirectController;
use App\Http\Middleware\EnforceDomainAccess;
use App\Http\Middleware\RefreshSsoToken;
use App\Http\Middleware\ResolveDomain;
use App\Models\ApiVersion;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health check — intentionally OUTSIDE ResolveDomain.
| Load balancers / container probes may hit this via IP or an unknown
| Host header, so it must answer regardless of domain resolution.
|--------------------------------------------------------------------------
*/
Route::get('/healthz', HealthController::class)
    ->withoutMiddleware(ResolveDomain::class)
    ->name('healthz');

/*
|--------------------------------------------------------------------------
| Domain context is resolved globally (see bootstrap/app.php prepend of
| ResolveDomain). Do NOT wrap these routes in another ResolveDomain
| group — that would resolve twice (double DB lookups). Host-pinning
| below relies on the `domain_type` attribute already being set.
|--------------------------------------------------------------------------
*/

/*
|----------------------------------------------------------------------
| Auth routes — dashboard hosts only (dash/admin.ternis.link).
| Pinned via ensure.domain so /login and /auth/* 404 on redirect
| and API domains instead of leaking session flows there.
|----------------------------------------------------------------------
*/
Route::middleware('ensure.domain:dashboard,admin')->group(function () {
    Route::get('/login', [TernisAuthController::class, 'showLogin'])->name('login');
    // Throttled: these initiate/complete the OAuth round-trip against
    // Ternis Auth — don't let attackers loop them for free.
    Route::middleware('throttle:10,1')->group(function () {
        Route::get('/auth/redirect', [TernisAuthController::class, 'redirect'])->name('auth.redirect');
        Route::get('/auth/silent', [TernisAuthController::class, 'silent'])->name('auth.silent');
        Route::get('/auth/callback', [TernisAuthController::class, 'callback'])->name('auth.callback');
    });
    Route::post('/logout', [TernisAuthController::class, 'logout'])->name('logout');

    if (app()->environment('local', 'testing')) {
        Route::get('/auth/demo', [TernisAuthController::class, 'demoLogin'])->name('auth.demo');
    }
});

/*
|----------------------------------------------------------------------
| Dashboard routes (dash.ternis.link) — require SSO login.
| ensure.domain runs before auth so the wrong host 404s instead of
| redirecting to login (fail fast, don't leak route existence).
|----------------------------------------------------------------------
*/
Route::middleware(['ensure.domain:dashboard,admin', 'auth', RefreshSsoToken::class, EnforceDomainAccess::class])->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/links', [DashboardController::class, 'links'])->name('dashboard.links');
    Route::get('/links/create', [DashboardController::class, 'createLink'])->name('dashboard.links.create');
    Route::get('/links/{link}', [DashboardController::class, 'showLink'])->name('dashboard.links.show');
    Route::get('/api-keys', [DashboardController::class, 'apiKeys'])->name('dashboard.api-keys');
});

/*
|----------------------------------------------------------------------
| Landing page — open on all hosts (branches by domain_type).
| Kept under EnforceDomainAccess so dash.ternis.link/ still
| redirects guests to login instead of showing the public landing.
|----------------------------------------------------------------------
*/
Route::middleware(EnforceDomainAccess::class)->get('/', function () {
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

/*
|----------------------------------------------------------------------
| Redirect routes — short-link hosts only
| (public, business, ternis, partner). API/dashboard/admin hosts 404
| here so /{slug} probing can't run on the wrong domain.
|----------------------------------------------------------------------
*/
Route::middleware(['ensure.domain:public,business,ternis,partner', EnforceDomainAccess::class])->group(function () {
    // Direct URL redirects (preferred)
    Route::get('/url/{url}', [RedirectController::class, 'directUrl'])
        ->where('url', '.*')
        ->name('redirect.url');

    // Alternative direct URL redirect
    Route::get('/go/{url}', [RedirectController::class, 'goUrl'])
        ->where('url', '.*')
        ->name('redirect.go');

    // Slug or URL detection — MUST be last (catch-all).
    // Version prefixes (v1, v2, …) are reserved so single-segment API
    // roots (/v1, /v1/) fall through to routes/api/v*.php instead of
    // being treated as slugs. Web routes load before API routes, so
    // without this the API version root would 404 via ensure.domain.
    Route::get('/{input}', [RedirectController::class, 'resolve'])
        ->where('input', '^(?!v\d+$)[^/]+$')
        ->name('redirect.resolve');
});
