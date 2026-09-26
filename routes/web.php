<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\TernisAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\StatsController;
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
| Dashboard routes (dash.ternis.link ONLY) — require SSO login.
| Served at the domain root (no /dashboard prefix): the hostname
| already says dashboard. ensure.domain runs before auth so the wrong
| host 404s instead of redirecting to login (fail fast, don't leak
| route existence). The admin host has its own console below and
| never serves these routes.
|----------------------------------------------------------------------
*/
/*
|----------------------------------------------------------------------
| /dashboard handling — host branching:
| - On local dev (localhost, 127.0.0.1, ::1, testserver), serves the
|   dashboard home (requiring auth).
| - On the dashboard host (dash.ternis.link): legacy 301 redirect to
|   the root URLs.
| - On ternis short-link hosts (ternis.link): 302 redirects to the
|   dashboard host (dash.ternis.link).
| - Elsewhere (admin host, href.nz, api host): 404 to avoid leaking
|   or swallowing short link slugs.
|----------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    $host = request()->getHost();
    $type = request()->attributes->get('domain_type');
    $dashHost = (string) config('domains.dashboard_host', 'dash.ternis.link');

    if (in_array($host, ['localhost', '127.0.0.1', '::1', 'testserver'], true)) {
        if (! auth()->check()) {
            return redirect('/login');
        }

        return app(DashboardController::class)->index(request());
    }

    if ($type === 'dashboard') {
        return redirect('/', 301);
    }

    if ($host === 'ternis.link' || $type === 'ternis') {
        $target = request()->getScheme().'://'.$dashHost;

        return redirect()->away($target, 302);
    }

    abort(404);
});

Route::get('/dashboard/{any}', function (string $any) {
    $host = request()->getHost();
    $type = request()->attributes->get('domain_type');
    $dashHost = (string) config('domains.dashboard_host', 'dash.ternis.link');
    $query = request()->getQueryString();
    $qs = $query ? '?'.$query : '';

    if ($type === 'dashboard') {
        return redirect('/'.$any.$qs, 301);
    }

    if ($host === 'ternis.link' || $type === 'ternis') {
        $target = request()->getScheme().'://'.$dashHost.'/'.$any.$qs;

        return redirect()->away($target, 302);
    }

    abort(404);
})->where('any', '.*');

Route::middleware(['ensure.domain:dashboard', 'auth', RefreshSsoToken::class, EnforceDomainAccess::class])->group(function () {

    // /admin on the dashboard host belongs to the admin host. Pinned
    // to the dashboard host; strips the legacy prefix so
    // dash.ternis.link/admin/users → admin.ternis.link/users.
    Route::domain((string) config('domains.dashboard_host', 'dash.ternis.link'))->get('/admin{any?}', function () {
        $adminHost = (string) config('domains.admin_host', 'admin.ternis.link');
        $suffix = substr(request()->getRequestUri(), strlen('/admin'));

        return redirect()->away(request()->getScheme().'://'.$adminHost.$suffix, 302);
    })->where('any', '.*');

    // Dashboard home. Pinned to the dashboard host: a second host-blind
    // GET / would collide with the landing home route in the collection
    // (same method+URI evicts it) and swallow the public landing.
    Route::domain((string) config('domains.dashboard_host', 'dash.ternis.link'))
        ->get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/new', [DashboardController::class, 'createLink'])->name('dashboard.new');
    Route::get('/links', [DashboardController::class, 'links'])->name('dashboard.links');
    Route::get('/links/create', [DashboardController::class, 'createLink'])->name('dashboard.links.create');
    Route::get('/links/{link}', [DashboardController::class, 'showLink'])->name('dashboard.links.show');
    Route::get('/links/{link}/edit', [DashboardController::class, 'editLink'])->name('dashboard.links.edit');
    Route::get('/links/{link}/export', [DashboardController::class, 'exportClicks'])->name('dashboard.links.export');
    Route::get('/links/{link}/qr', [DashboardController::class, 'qrCode'])->name('dashboard.links.qr');
    Route::get('/api-keys', [DashboardController::class, 'apiKeys'])->name('dashboard.api-keys');
    Route::get('/domains', [DashboardController::class, 'domains'])->name('dashboard.domains');
    Route::get('/notifications', [DashboardController::class, 'notifications'])->name('dashboard.notifications');
    Route::post('/notifications/read', [DashboardController::class, 'markAllNotificationsRead'])->name('dashboard.notifications.read-all');
    Route::post('/notifications/{id}/read', [DashboardController::class, 'markNotificationRead'])->name('dashboard.notifications.read');
    Route::get('/activity', [DashboardController::class, 'activity'])->name('dashboard.activity');
    Route::get('/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');
});

/*
|----------------------------------------------------------------------
| Admin console (admin.ternis.link ONLY) — require admin role.
| Served at the domain root: / → overview, /links, /users, /domains,
| /activity. ensure.domain 404s on any other host; EnforceDomainAccess
| then 403s authenticated non-admins and redirects guests to login.
| Legacy /admin/* URLs 301 to the root equivalents below.
|----------------------------------------------------------------------
*/
Route::middleware(['ensure.domain:admin', 'auth', RefreshSsoToken::class, EnforceDomainAccess::class])
    ->domain((string) config('domains.admin_host', 'admin.ternis.link'))
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/links', [AdminController::class, 'links'])->name('links');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/domains', [AdminController::class, 'domains'])->name('domains');
        Route::get('/activity', [AdminController::class, 'activity'])->name('activity');
        Route::get('/errors', [AdminController::class, 'errors'])->name('errors');
    });

// Legacy /admin/* on the admin host → root equivalents (permanent).
// Runs without auth so bookmarks keep working for signed-in admins;
// guests still land on the canonical URL and hit the auth redirect.
Route::middleware(['ensure.domain:admin'])->domain((string) config('domains.admin_host', 'admin.ternis.link'))->get('/admin{any?}', function () {
    $suffix = substr(request()->getPathInfo(), strlen('/admin'));
    $query = request()->getQueryString();
    $qs = $query ? '?'.$query : '';

    return redirect('/'.ltrim($suffix, '/').$qs, 301);
})->where('any', '.*');

/*
|----------------------------------------------------------------------
| Public network stats (ternis.link/stats) — aggregate counts only,
| no personal data, so no login needed. Ternis host only; every other
| host 404s here. Removed links stay counted (nothing is deleted).
|----------------------------------------------------------------------
*/
Route::middleware(['ensure.domain:ternis'])->prefix('stats')->name('stats.')->group(function () {
    Route::get('/', [StatsController::class, 'index'])->name('index');
    Route::get('/domains', [StatsController::class, 'domains'])->name('domains');
    Route::get('/links', [StatsController::class, 'links'])->name('links');
});

/*
|----------------------------------------------------------------------
| Legal pages — Markdown from resources/legal/*.md, allowlisted slugs.
| Canonical home is ternis.link: the single route serves there, 301s
| dashboard/admin hosts to the canonical host, and 404s everywhere
| else. One definition (not per-host duplicates): Laravel only matches
| the FIRST route per URI, so host branching lives inside the handler.
|----------------------------------------------------------------------
*/
Route::get('/legal/{any}', function (string $any) {
    $type = request()->attributes->get('domain_type');

    if ($type === 'ternis') {
        if (! preg_match('/^[a-z-]+$/', $any)) {
            abort(404);
        }

        return app(LegalController::class)->show($any);
    }

    if (in_array($type, ['dashboard', 'admin'], true)) {
        $query = request()->getQueryString();
        $qs = $query ? '?'.$query : '';

        return redirect()->away('https://ternis.link/legal/'.$any.$qs, 301);
    }

    abort(404);
})->where('any', '.*')->name('legal.show');

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
|
| Resolution is PUBLIC on all four hosts: opening a short link never
| requires login (guests on href.re/ternis.link used to bounce to the
| dashboard login before the slug was even looked up). Login + role
| gates stay on the dashboard/admin UI only.
|----------------------------------------------------------------------
*/
Route::middleware(['ensure.domain:public,business,ternis,partner'])->group(function () {
    // Direct URL redirects (preferred)
    Route::get('/url/{url}', [RedirectController::class, 'directUrl'])
        ->where('url', '.*')
        ->name('redirect.url');

    // Alternative direct URL redirect
    Route::get('/go/{url}', [RedirectController::class, 'goUrl'])
        ->where('url', '.*')
        ->name('redirect.go');

    // Link preview sandbox (href.nz only — the handler 404s elsewhere).
    // Must stay above the catch-all: /preview/x would classify as slug.
    Route::get('/preview/{input}', [PreviewController::class, 'show'])
        ->where('input', '.*')
        ->name('redirect.preview');

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
