<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\V1\PublicQrCodeController;
use App\Http\Controllers\Auth\TernisAuthController;
use App\Http\Controllers\BioPageController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\ExtensionController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\SiteFilesController;
use App\Http\Controllers\StatsController;
use App\Http\Middleware\EnforceDomainAccess;
use App\Http\Middleware\RefreshSsoToken;
use App\Http\Middleware\ResolveDomain;
use App\Models\ApiVersion;
use App\Support\ContentCollection;
use App\Support\NetworkStats;
use App\Support\PublicHost;
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
|----------------------------------------------------------------------
| Machine-readable site files — REAL routes, host-blind (crawlers and
| agents fetch these per hostname). They must stay ABOVE the short-link
| catch-all (/{input}) so /sitemap.xml etc. are never swallowed as
| link slugs. Handled by SiteFilesController; .md twins for /pages/*
| live with their ternis-only HTML originals further below.
|----------------------------------------------------------------------
*/
Route::get('/robots.txt', [SiteFilesController::class, 'robots'])->name('site.robots');
Route::get('/sitemap.xml', [SiteFilesController::class, 'sitemap'])->name('site.sitemap');
Route::get('/llms.txt', [SiteFilesController::class, 'llms'])->name('site.llms');
Route::get('/llms-full.txt', [SiteFilesController::class, 'llmsFull'])->name('site.llms-full');

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

    // href.nz serves its own sketch-styled login card (the card links
    // to the dashboard host where SSO actually runs). Every other
    // short-link host keeps the plain 302.
    if ($method === 'showLogin' && $type === 'public') {
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

    // /new is host-pinned like / above: the public /new route below
    // shares the same method+URI and would otherwise evict this
    // definition from the collection. Localhost dev never matches a
    // pinned host, so the public closure serves the dashboard branch
    // there (ResolveDomain maps localhost/new to the dashboard type).
    Route::domain((string) config('domains.dashboard_host', 'dash.ternis.link'))
        ->get('/new', [DashboardController::class, 'createLink'])->name('dashboard.new');
    // /links is host-pinned like /new above: it would otherwise match
    // first on the docs host (registered before docs /{slug}) and
    // bounce guests to a /login that 404s there. Localhost dev falls
    // through to the docs route's dashboard branch below.
    Route::domain((string) config('domains.dashboard_host', 'dash.ternis.link'))
        ->get('/links', [DashboardController::class, 'links'])->name('dashboard.links');
    Route::get('/links/create', [DashboardController::class, 'createLink'])->name('dashboard.links.create');
    Route::get('/links/export', [DashboardController::class, 'exportLinks'])->name('dashboard.links.export-all');
    Route::get('/links/{link}', [DashboardController::class, 'showLink'])->name('dashboard.links.show');
    Route::get('/links/{link}/edit', [DashboardController::class, 'editLink'])->name('dashboard.links.edit');
    Route::get('/links/{link}/export', [DashboardController::class, 'exportClicks'])->name('dashboard.links.export');
    Route::get('/links/{link}/qr', [DashboardController::class, 'qrCode'])->name('dashboard.links.qr');
    Route::get('/api-keys', [DashboardController::class, 'apiKeys'])->name('dashboard.api-keys');
    Route::get('/api-keys/{key}', [DashboardController::class, 'showApiKey'])->name('dashboard.api-keys.show');
    Route::get('/bio', [DashboardController::class, 'bio'])->name('dashboard.bio');
    Route::get('/bio/{page}', [DashboardController::class, 'showBio'])->name('dashboard.bio.show');
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
| Public network stats (ternis.link/pages/stats) — aggregate counts
| only, no personal data, so no login needed and nothing is
| exportable. The /pages/ namespace keeps app pages from ever
| colliding with single-segment shortlink slugs. Ternis host only;
| every other host 404s here. Removed links stay counted (nothing
| is deleted). Legacy /stats/* URLs 301 to the new home.
|----------------------------------------------------------------------
*/
Route::middleware(['ensure.domain:ternis'])->prefix('pages/stats')->name('pages.stats.')->group(function () {
    Route::get('/', [StatsController::class, 'index'])->name('index');
    Route::get('/domains', [StatsController::class, 'domains'])->name('domains');
});

/*
|----------------------------------------------------------------------
| Markdown twins of the stats pages (text/markdown, same aggregates,
| no chrome). Same ternis-only pinning as the HTML originals.
|----------------------------------------------------------------------
*/
Route::middleware(['ensure.domain:ternis'])->group(function () {
    Route::get('/pages/stats.md', [StatsController::class, 'indexMd'])->name('pages.stats.index-md');
    Route::get('/pages/stats/domains.md', [StatsController::class, 'domainsMd'])->name('pages.stats.domains-md');
});

Route::middleware(['ensure.domain:ternis'])->get('/stats{any?}', function () {
    $suffix = substr(request()->getPathInfo(), strlen('/stats'));
    $query = request()->getQueryString();
    $qs = $query ? '?'.$query : '';

    // The public Top Links page was removed (per-link leaderboards
    // distort the analytics members rely on) — old bookmarks land on
    // the overview instead of a 404.
    if (rtrim($suffix, '/') === '/links') {
        return redirect('/pages/stats'.$qs, 301);
    }

    return redirect('/pages/stats'.rtrim($suffix, '/').$qs, 301);
})->where('any', '.*');

/*
|----------------------------------------------------------------------
| Browser extension download page (ternis.link/pages/extension) —
| same /pages/ pattern as stats/legal: public, no login, ternis host
| only (every other host 404s). HTML + Markdown twin + version JSON
| (manifest.json is the version source of truth) + zip download
| (built by `php artisan extension:build` into public/extension/).
|----------------------------------------------------------------------
*/
Route::middleware(['ensure.domain:ternis'])->prefix('pages/extension')->name('pages.extension.')->group(function () {
    Route::get('/', [ExtensionController::class, 'index'])->name('index');
    Route::get('/version', [ExtensionController::class, 'version'])->name('version');
    Route::get('/download', [ExtensionController::class, 'download'])->name('download');
});

Route::middleware(['ensure.domain:ternis'])->get('/pages/extension.md', [ExtensionController::class, 'indexMd'])->name('pages.extension.index-md');

/*
|----------------------------------------------------------------------
| Short memorable URL for the extension (ternis.link/extension) —
| 301 to the canonical /pages/extension home, preserving subpaths
| (/extension/download, /extension/version, /extension.md) and query
| strings. Single definition with host branching INSIDE the handler:
| Laravel only matches the FIRST route per URI, so a ternis-pinned
| route here would shadow docs.ternis.link/extension on the docs
| host (same reason /login and /legal/* branch internally).
| Ternis → redirect; docs → delegate to the docs page (.md twin
| included); everywhere else → 404. Registered above the short-link
| catch-all so `extension` is never mistaken for a link slug.
|----------------------------------------------------------------------
*/
Route::get('/extension{any?}', function () {
    $type = request()->attributes->get('domain_type');
    $suffix = substr(request()->getPathInfo(), strlen('/extension'));
    $query = request()->getQueryString();
    $qs = $query ? '?'.$query : '';

    if ($type === 'ternis') {
        return redirect('/pages/extension'.rtrim($suffix, '/').$qs, 301);
    }

    if ($type === 'docs') {
        if ($suffix === '') {
            return app(DocsController::class)->show('extension');
        }

        if ($suffix === '.md') {
            return app(DocsController::class)->showMd('extension');
        }
    }

    abort(404);
})->where('any', '.*');

/*
|----------------------------------------------------------------------
| Short memorable URLs for the blog (ternis.link/blog, /blogs) —
| 301 to the canonical /pages/blog home, preserving entry subpaths
| (/blogs/{slug}, /blogs/{slug}.md) and query strings. Same single-
| definition pattern as /extension above: ternis → redirect,
| everywhere else → 404. The `any` constraint keeps bare slugs like
| `blogroll` on the short-link catch-all below. Registered above it
| so `blog` is never mistaken for a link slug.
|----------------------------------------------------------------------
*/
foreach (['/blogs', '/blog'] as $shortcut) {
    Route::get($shortcut.'{any?}', function () use ($shortcut) {
        if (request()->attributes->get('domain_type') !== 'ternis') {
            abort(404);
        }

        $suffix = substr(request()->getPathInfo(), strlen($shortcut));
        $query = request()->getQueryString();
        $qs = $query ? '?'.$query : '';

        // Entry shortcuts resolve straight to the canonical dated URL
        // (single 301, no chain through the undated slug).
        if (preg_match('#^/([a-z0-9-]+)(\.md)?$#', $suffix, $m)) {
            $entry = ContentCollection::entry('blog', $m[1]);

            if ($entry !== null) {
                return redirect('/pages/blog/'.$entry['canonical'].($m[2] ?? '').$qs, 301);
            }
        }

        return redirect('/pages/blog'.rtrim($suffix, '/').$qs, 301);
    })->where('any', '(/.*)?');
}

/*
|----------------------------------------------------------------------
| Legal pages — Markdown from resources/legal/*.md, allowlisted slugs.
| Canonical home is ternis.link/pages/legal/{slug}: the route below
| serves there; the legacy /legal/* route 301s (same host or
| canonical host for dashboard/admin) and 404s everywhere else. One
| legacy definition (not per-host duplicates): Laravel only matches
| the FIRST route per URI, so host branching lives inside the handler.
|----------------------------------------------------------------------
*/
Route::middleware(['ensure.domain:ternis'])->get('/pages/legal/{slug}', [PagesController::class, 'legal'])
    ->where('slug', '[a-z-]+')
    ->name('pages.legal');

// Markdown twin of a legal page (serves the .md source verbatim).
Route::middleware(['ensure.domain:ternis'])->get('/pages/legal/{slug}.md', [PagesController::class, 'legalMd'])
    ->where('slug', '[a-z-]+')
    ->name('pages.legal-md');

/*
|----------------------------------------------------------------------
| File-driven collections (changelog, news, blog) — index + entries,
| HTML with text/markdown twins. Auto-discovered from
| resources/content/{collection}/*.md. The {collection} constraint
| doubles as the allowlist: unknown collections match no route (404).
| Ternis host only, same pinning as the other /pages/* content.
|----------------------------------------------------------------------
*/
Route::middleware(['ensure.domain:ternis'])
    ->where(['collection' => 'changelog|news|blog'])
    ->name('pages.collection.')
    ->group(function () {
        Route::get('/pages/{collection}', [ContentController::class, 'index'])->name('index');
        Route::get('/pages/{collection}.md', [ContentController::class, 'indexMd'])->name('index-md');
        Route::get('/pages/{collection}/{slug}', [ContentController::class, 'show'])
            ->where('slug', '[a-z0-9-]+')
            ->name('show');
        Route::get('/pages/{collection}/{slug}.md', [ContentController::class, 'showMd'])
            ->where('slug', '[a-z0-9-]+')
            ->name('show-md');
    });

Route::get('/legal/{any}', function (string $any) {
    $type = request()->attributes->get('domain_type');
    $query = request()->getQueryString();
    $qs = $query ? '?'.$query : '';

    if ($type === 'ternis') {
        return redirect('/pages/legal/'.$any.$qs, 301);
    }

    if (in_array($type, ['dashboard', 'admin'], true)) {
        return redirect()->away('https://ternis.link/pages/legal/'.$any.$qs, 301);
    }

    abort(404);
})->where('any', '.*');

/*
|----------------------------------------------------------------------
| Developer docs (docs.ternis.link ONLY) — renders docs/*.md as HTML
| with raw Markdown twins, no login. Registered ABOVE the landing home
| and the short-link catch-all: the host-blind `/` would otherwise
| serve the landing, and `/{input}` would swallow doc slugs.
| Every other host 404s here via ensure.domain.
|----------------------------------------------------------------------
*/
Route::name('docs.')->group(function () {
    // Host-pinned like the dashboard /: the host-blind landing / below
    // shares the method+URI and would otherwise evict this definition.
    Route::middleware(['ensure.domain:docs'])
        ->domain((string) config('domains.docs_host', 'docs.ternis.link'))
        ->get('/', [DocsController::class, 'index'])->name('index');
    Route::middleware(['ensure.domain:docs'])
        ->get('/api-v1-openapi.yaml', [DocsController::class, 'openapi'])->name('openapi');
    // Slug allowlist (same pattern as /pages/{collection}): a greedy
    // {slug} here would shadow single-segment routes registered below
    // (/new, /{input}) on every other host before ensure.domain 404s.
    // /{slug} (but not .md) also allows the dashboard type: pinned
    // dashboard routes never match localhost dev, so /links (a docs
    // slug AND a dashboard URI) falls through here with the dashboard
    // type — the controller delegates those to the dashboard (see
    // show()). Middleware is per-route (not grouped) so the dashboard
    // allowance doesn't leak onto the other docs routes.
    $docSlugs = implode('|', array_keys(DocsController::PAGES));
    Route::middleware(['ensure.domain:docs'])
        ->get('/{slug}.md', [DocsController::class, 'showMd'])
        ->where('slug', $docSlugs)
        ->name('show-md');
    Route::middleware(['ensure.domain:docs,dashboard'])
        ->get('/{slug}', [DocsController::class, 'show'])
        ->where('slug', $docSlugs)
        ->name('show');
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
        return view('landing.business', ['stats' => NetworkStats::overview()]);
    }
    if ($type === 'public') {
        // meinlink.at shares every public rule but gets its own German
        // landing page and theme (see meinlink.css).
        if (PublicHost::isMeinlink()) {
            return view('landing.meinlink');
        }

        return view('landing.public');
    }

    // Custom-domain bio root: a verified user domain in bio mode serves
    // its page at `/` instead of the generic landing. System domains
    // never host bio in v1.
    if ($type === 'partner') {
        $domainModel = request()->attributes->get('domain_model');

        if ($domainModel instanceof \App\Models\Domain && ! $domainModel->isSystemDomain()) {
            try {
                $root = app(BioPageController::class)->showRoot(request());

                return $root;
            } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                // No bio page — fall through to landing below.
            } catch (\Throwable) {
                // Never break landing on bio errors.
            }
        }
    }

    return view('landing.index', ['stats' => NetworkStats::overview()]);
})->name('home');

Route::get('/t/{button}', function (string $button) {
    $type = request()->attributes->get('domain_type');

    if (! in_array($type, ['partner'], true)) {
        abort(404);
    }

    return app(BioPageController::class)->tap(request(), $button);
})->where('button', '[A-Za-z0-9]{20,30}')->name('bio.tap');

Route::get('/new', function () {
    $type = request()->attributes->get('domain_type');

    // Localhost dev: the dashboard /new above is host-pinned and never
    // matches here — serve the create page directly (same pattern as
    // the / route's dashboard branch).
    if ($type === 'dashboard') {
        if (! auth()->check()) {
            return redirect('/login');
        }

        return app(DashboardController::class)->createLink();
    }

    if ($type !== 'public') {
        abort(404);
    }

    if (PublicHost::isMeinlink()) {
        return view('landing.new-meinlink');
    }

    return view('landing.new');
})->name('public.new');

if (app()->environment('local', 'testing')) {
    // Design preview for the meinlink.at landing page (bypasses the
    // host check so the theme can be screenshotted without DNS).
    // Never available in production.
    Route::get('/_preview/at', fn () => view('landing.meinlink'))->name('preview.at');
    Route::get('/_preview/re', fn () => view('landing.business', ['stats' => NetworkStats::overview()]))->name('preview.re');
    Route::get('/_preview/at-new', fn () => view('landing.new-meinlink'))->name('preview.at-new');
    Route::get('/_preview/at-login', fn () => view('auth.login-meinlink'))->name('preview.at-login');
    Route::get('/_preview/at-error', fn () => response()->view('components.layouts.public-error-meinlink', [
        'code' => '404',
        'title' => 'Link nicht gefunden.',
        'slot' => 'Der Kurzlink meink-xyz für die Domain meinlink.at wurde nicht gefunden, ist inaktiv oder abgelaufen.',
    ], 404))->name('preview.at-error');
}

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

    // Pretty QR codes (public hosts only, throttled like /v1/qr):
    // /qr/{url} (PNG default), /qr/{url}/{mime}, /{slug}/qr[.mime]
    // and /{url}.{mime} for short-link slugs or direct URLs. Above
    // the catch-all so slugs stay resolvable; unknown slugs 404. The
    // mime route comes first: /qr/{url} is greedy and would otherwise
    // swallow the trailing /{mime} segment into the URL (then it just
    // encodes). Slug-QR routes come before the suffixed route so
    // /{slug}/qr.png isn't misread as url "{slug}/qr".
    Route::middleware(['ensure.domain:public', 'throttle:10,1'])->group(function () {
        Route::get('/qr/{url}/{mime}', [PublicQrCodeController::class, 'prettyMime'])
            ->where(['url' => '.*', 'mime' => 'png|svg'])
            ->name('qr.pretty-mime');
        Route::get('/qr/{url}', [PublicQrCodeController::class, 'pretty'])
            ->where('url', '.*')
            ->name('qr.pretty');
        Route::get('/{slug}/qr', [PublicQrCodeController::class, 'slugQr'])
            ->where('slug', '(?!v[0-9]+/)[a-zA-Z0-9_-]+')
            ->name('qr.slug');
        Route::get('/{slug}/qr.{mime}', [PublicQrCodeController::class, 'slugQrMime'])
            ->where(['slug' => '(?!v[0-9]+/)[a-zA-Z0-9_-]+', 'mime' => 'png|svg'])
            ->name('qr.slug-mime');
        Route::get('/{url}.{mime}', [PublicQrCodeController::class, 'suffixed'])
            ->where(['url' => '(?!v[0-9]+/).*', 'mime' => 'png|svg'])
            ->name('qr.suffixed');
    });

    // Root-level direct URL redirects remain supported for href.nz:
    // href.nz/https://example.com/path. Other direct URLs must use the
    // explicit /url/{url} form so short links are not confused with
    // arbitrary hostnames.
    Route::middleware('ensure.domain:public')->get('/{url}', [RedirectController::class, 'directUrl'])
        ->where('url', 'https?://.*')
        ->name('redirect.root-url');

    // Short-link slugs remain root-level URLs (href.nz/abc123). Restrict
    // this route to slug characters so href.nz/example.com cannot be
    // mistaken for an implicit direct URL.
    Route::middleware('ensure.domain:public,business,ternis,partner')->get('/{input}', [RedirectController::class, 'resolve'])
        ->where('input', '^(?!v\d+$)[a-zA-Z0-9_-]+$')
        ->name('redirect.resolve');
});
