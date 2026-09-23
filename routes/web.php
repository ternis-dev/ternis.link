<?php

use App\Http\Controllers\AdminController;
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
| Auth routes — served on dashboard/admin hosts, redirected from
| short-link hosts (public, business, ternis, partner), 404 elsewhere.
| Single route definitions (not duplicated per host type): Laravel only
| matches the FIRST route for a given URI, so host branching must live
| INSIDE the handler — a second /login route with different middleware
| would never be reached (the first match aborts 404 in middleware).
| The OAuth flow must start + finish on the dashboard host (session +
| PKCE live there; cookies can't cross href.nz ↔ ternis.link anyway),
| so /login and /auth/* on short-link hosts 302 to the dashboard host
| instead of 404ing. This fixes href.nz/login and ternis.link/login.
|----------------------------------------------------------------------
*/
$redirectToDashboard = function () {
    $host = config('domains.dashboard_host', 'dash.ternis.link');
    $target = request()->getScheme().'://'.$host.request()->getRequestUri();

    return redirect()->away($target, 302);
};

$serveOrRedirect = function (string $method) use ($redirectToDashboard) {
    $type = request()->attributes->get('domain_type');

    if (in_array($type, ['dashboard', 'admin'], true)) {
        return app(TernisAuthController::class)->{$method}(request());
    }

    if (in_array($type, ['public', 'business', 'ternis', 'partner'], true)) {
        return $redirectToDashboard();
    }

    abort(404);
};

Route::get('/login', fn () => $serveOrRedirect('showLogin'))->name('login');
Route::middleware('throttle:10,1')->group(function () use ($serveOrRedirect) {
    Route::get('/auth/redirect', fn () => $serveOrRedirect('redirect'))->name('auth.redirect');
    Route::get('/auth/silent', fn () => $serveOrRedirect('silent'))->name('auth.silent');
    Route::get('/auth/callback', fn () => $serveOrRedirect('callback'))->name('auth.callback');
});
Route::post('/logout', function () use ($redirectToDashboard) {
    $type = request()->attributes->get('domain_type');

    if (in_array($type, ['dashboard', 'admin'], true)) {
        return app(TernisAuthController::class)->logout(request());
    }

    if (in_array($type, ['public', 'business', 'ternis', 'partner'], true)) {
        return $redirectToDashboard();
    }

    abort(404);
})->name('logout');

if (app()->environment('local', 'testing')) {
    Route::get('/auth/demo', function () use ($redirectToDashboard) {
        $type = request()->attributes->get('domain_type');

        if (in_array($type, ['dashboard', 'admin'], true)) {
            return app(TernisAuthController::class)->demoLogin(request());
        }

        if (in_array($type, ['public', 'business', 'ternis', 'partner'], true)) {
            return $redirectToDashboard();
        }

        abort(404);
    })->name('auth.demo');
}

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
| Admin routes (admin.ternis.link only) — require admin role.
| ensure.domain 404s on any other host; EnforceDomainAccess then
| 403s authenticated non-admins and redirects guests to login.
|----------------------------------------------------------------------
*/
Route::middleware(['ensure.domain:admin', 'auth', RefreshSsoToken::class, EnforceDomainAccess::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/links', [AdminController::class, 'links'])->name('links');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
});

/*
|----------------------------------------------------------------------
| Landing pages — public on short-link hosts (no EnforceDomainAccess).
| href.nz → landing.public, href.re → landing.business, ternis/partner
| → landing.index fallback. Dashboard/admin/API branches keep their
| previous behaviour (same-host /login redirect for guests).
|----------------------------------------------------------------------
*/
Route::get('/', function () {
    $type = request()->attributes->get('domain_type');
    if ($type === 'dashboard') {
        if (! auth()->check()) {
            return redirect('/login');
        }

        return redirect()->route('dashboard');
    }
    if ($type === 'admin') {
        if (! auth()->check()) {
            return redirect('/login');
        }

        return redirect()->route('admin.dashboard');
    }
    if ($type === 'api') {
        $latest = ApiVersion::latestVersion();

        return redirect("/v{$latest}/", 302);
    }
    if ($type === 'business') {
        return view('landing.business');
    }
    if ($type === 'public') {
        return view('landing.public');
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
    // NOTE: /login and /auth/* are registered above, so they win over
    // this catch-all on short-link hosts (redirect shims, not slugs).
    Route::get('/{input}', [RedirectController::class, 'resolve'])
        ->where('input', '^(?!v\d+$)[^/]+$')
        ->name('redirect.resolve');
});
