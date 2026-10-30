<?php

use App\Http\Controllers\Api\V1\PublicQrCodeController;
use App\Http\Controllers\Auth\AuthApiController;
use App\Http\Controllers\Auth\SsoHandoffController;
use App\Http\Controllers\Auth\TernisAuthController;
use App\Http\Controllers\BioPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\ExtensionController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ProtectedGatewayController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\ScanUiController;
use App\Http\Controllers\SiteFilesController;
use App\Http\Middleware\EnforceDomainAccess;
use App\Http\Middleware\EnsureCorsOrigin;
use App\Http\Middleware\EnsureSsoHost;
use App\Http\Middleware\ResolveDomain;
use App\Models\ApiVersion;
use App\Models\Domain;
use App\Support\DomainUrls;
use App\Support\NetworkStats;
use App\Support\PublicHost;
use App\Support\SsoHosts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

// Deployment readiness is separate: pending optional migrations must not evict
// live redirect servers from the load balancer's /healthz pool.
Route::get('/readyz', [HealthController::class, 'ready'])
    ->withoutMiddleware(ResolveDomain::class)
    ->name('readyz');

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

    // Hosts outside the ternis.link session cookie (href.nz, meinlink.at,
    // my.href.nz, …) never run OAuth themselves: the identity host signs
    // the user in and hands the login back (see SsoHandoffController).
    if (in_array($method, ['redirect', 'silent'], true)
        && ! in_array($type, ['dashboard', 'admin'], true)
        && ! SsoHosts::sharesIdentitySession(request()->getHost())
        && SsoHosts::isAllowed(request()->getHost())) {
        return redirect('/auth/sso/start?'.http_build_query(array_filter([
            'to' => SsoHosts::safePath(request()->query('to')),
            'silent' => $method === 'silent' ? 1 : null,
        ])));
    }

    // Dash, admin, and public dashboards share one ternis.link session
    // (SESSION_DOMAIN), so every host runs its own SSO flow straight
    // against the single registered dash callback — no handoff needed.
    if (in_array($type, ['dashboard', 'admin', 'public-dashboard'], true)) {
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
Route::middleware('throttle:30,1')->prefix('auth/sso')->group(function () {
    Route::get('start', [SsoHandoffController::class, 'start'])->name('auth.sso.start');
    Route::get('issue', [SsoHandoffController::class, 'issue'])->name('auth.sso.issue');
    Route::get('consume', [SsoHandoffController::class, 'consume'])->name('auth.sso.consume');
});
Route::post('/logout', function () use ($redirectToDashboard) {
    $type = request()->attributes->get('domain_type');

    if (in_array($type, ['dashboard', 'admin', 'public-dashboard'], true)
        || (SsoHosts::isAllowed(request()->getHost()) && $type !== 'api')) {
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

        if (in_array($type, ['dashboard', 'admin', 'public-dashboard'], true)) {
            return app(TernisAuthController::class)->demoLogin(request());
        }

        if (in_array($type, ['public', 'business', 'ternis', 'partner'], true)) {
            return $redirectToDashboard();
        }

        abort(404);
    })->name('auth.demo');
}

/*
|--------------------------------------------------------------------------
| Public Dashboard / My Module (my.href.nz)
|--------------------------------------------------------------------------
*/
require __DIR__.'/modules/meinlink-dashboard.php';
require __DIR__.'/modules/my.php';

/*
|----------------------------------------------------------------------
| Auth API (every linked host) — CORS-protected handshake
| for cross-origin clients.
|
| A web app on another origin cannot read the session cookie, so it
| asks GET /auth/api/session whether the visitor is signed in and
| POST /auth/api/token for a short-lived bearer token for the REST
| API. Only origins listed in config('cors.allowed_origins') get an
| answer; the list is empty by default.
|
| These routes live outside the `auth` middleware on purpose: the
| session endpoint has to answer "not signed in" to guests. They are
| exempted from CSRF for the same reason — a cross-origin client has
| no CSRF token — and are protected by the origin allowlist instead.
|----------------------------------------------------------------------
*/
// Served on EVERY linked host (all hosts of config/domains.php): thanks to
// the cross-domain SSO handoff each one holds a local session, so each can
// answer the session/token questions for its own visitors.
Route::middleware([EnsureSsoHost::class, EnsureCorsOrigin::class])
    ->prefix('auth/api')
    ->group(function () {
        // One throttle per route: stacking two on the same path
        // makes them share a limiter key, which halves the budget.
        Route::get('session', [AuthApiController::class, 'session'])
            ->middleware('throttle:60,1')
            ->name('auth.api.session');

        // Minting writes a row, so it gets a tighter budget.
        Route::post('token', [AuthApiController::class, 'token'])
            ->middleware('throttle:10,1')
            ->name('auth.api.token');

        Route::delete('token', [AuthApiController::class, 'revoke'])
            ->middleware('throttle:30,1')
            ->name('auth.api.token.revoke');
    });

/*
|--------------------------------------------------------------------------
| Dashboard Module (dash.ternis.link)
|--------------------------------------------------------------------------
*/
require __DIR__.'/modules/dash.php';

/*
|--------------------------------------------------------------------------
| Admin Module (admin.ternis.link)
|--------------------------------------------------------------------------
*/
require __DIR__.'/modules/admin.php';

/*
|--------------------------------------------------------------------------
| Network Stats Module (pages/stats)
|--------------------------------------------------------------------------
*/
require __DIR__.'/modules/stats.php';

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
|--------------------------------------------------------------------------
| Blog & Content Module
|--------------------------------------------------------------------------
*/
require __DIR__.'/modules/blog.php';

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
        ->get('/api-v{version}-openapi.yaml', [DocsController::class, 'openapi'])->where('version', '[12]')->name('openapi');
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
        return view('landing.business');
    }
    if ($type === 'protected') {
        return app(ProtectedGatewayController::class)->index();
    }
    if ($type === 'public') {
        // qr.href.nz is the dedicated interactive QR code studio & generator
        if (PublicHost::isQr()) {
            return view('landing.qr');
        }

        // meinlink.at shares every public rule but gets its own German
        // landing page and theme (see meinlink.css).
        if (PublicHost::isMeinlink()) {
            return view('landing.meinlink');
        }

        // clicked.at is the newsletter & email click-tracking branded
        // domain — same public rules, dedicated marketing landing.
        if (PublicHost::isClicked()) {
            $locale = PublicHost::resolveClickedLocale();
            app()->setLocale($locale);

            $response = response()->view('landing.clicked', ['locale' => $locale]);

            $param = request()->query('lang') ?? request()->query('locale');
            if (is_string($param) && in_array(strtolower(trim($param)), ['en', 'de'], true)) {
                session(['clicked_locale' => $locale]);
                $response->withCookie(cookie()->forever('clicked_locale', $locale));
            }

            return $response;
        }

        // href.yt is the video-first & creator shortener domain with
        // its own custom theme, custom CSS and JS
        if (PublicHost::isYt()) {
            return view('landing.yt', ['stats' => NetworkStats::overview()]);
        }

        return view('landing.public');
    }

    // Custom-domain bio root: a verified user domain in bio mode serves
    // its page at `/` instead of the generic landing. System domains
    // never host bio in v1.
    if ($type === 'partner') {
        $domainModel = request()->attributes->get('domain_model');

        if ($domainModel instanceof Domain && ! $domainModel->isSystemDomain()) {
            try {
                $root = app(BioPageController::class)->showRoot(request());

                return $root;
            } catch (NotFoundHttpException) {
                // No bio page — fall through to landing below.
            } catch (Throwable) {
                // Never break landing on bio errors.
            }
        }
    }

    // int.ternis.link is the internal routing gateway (application links,
    // imprint redirects) with its own dedicated landing page.
    if (DomainUrls::isInternal()) {
        return view('landing.internal');
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

Route::get('/t/{button}/open.gif', function (string $button) {
    $type = request()->attributes->get('domain_type');

    if (! in_array($type, ['partner'], true)) {
        abort(404);
    }

    return app(BioPageController::class)->openPixel(request(), $button);
})->where('button', '[A-Za-z0-9]{20,30}')->name('bio.open-pixel');

Route::get('/t/{button}/rsvp', function (string $button) {
    $type = request()->attributes->get('domain_type');

    if (! in_array($type, ['partner'], true)) {
        abort(404);
    }

    return app(BioPageController::class)->rsvp(request(), $button);
})->where('button', '[A-Za-z0-9]{20,30}')->name('bio.rsvp');

Route::get('/draft/{page}', function (string $page) {
    $type = request()->attributes->get('domain_type');

    if (! in_array($type, ['partner'], true)) {
        abort(404);
    }

    return app(BioPageController::class)->draft(request(), $page);
})->where('page', '[A-Za-z0-9]{20,30}')->middleware('signed')->name('bio.draft');

Route::post('/unlock/{page}', function (string $page) {
    $type = request()->attributes->get('domain_type');

    if (! in_array($type, ['partner'], true)) {
        abort(404);
    }

    return app(BioPageController::class)->unlock(request(), $page);
})->where('page', '[A-Za-z0-9]{20,30}')->middleware('throttle:10,1')->name('bio.unlock');

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

    if (PublicHost::isQr()) {
        return view('landing.qr', ['compact' => true]);
    }

    if (PublicHost::isMeinlink()) {
        return view('landing.new-meinlink');
    }

    if (PublicHost::isYt()) {
        return view('landing.new-yt');
    }

    return view('landing.new');
})->name('public.new');

if (app()->environment('local', 'testing')) {
    // Design preview for the meinlink.at landing page (bypasses the
    // host check so the theme can be screenshotted without DNS).
    // Never available in production.
    Route::get('/_preview/at', fn () => view('landing.meinlink'))->name('preview.at');
    Route::get('/_preview/re', fn () => view('landing.business'))->name('preview.re');
    Route::get('/_preview/int', fn () => view('landing.internal'))->name('preview.int');
    Route::get('/_preview/protected', [ProtectedGatewayController::class, 'index'])->name('preview.protected');
    Route::get('/_preview/yt', fn () => view('landing.yt', ['stats' => NetworkStats::overview()]))->name('preview.yt');
    Route::get('/_preview/yt-new', fn () => view('landing.new-yt'))->name('preview.yt-new');
    Route::get('/_preview/yt-login', fn () => view('auth.login-yt'))->name('preview.yt-login');
    Route::get('/_preview/yt-error', fn () => response()->view('components.layouts.public-error-yt', [
        'code' => '404',
        'title' => 'Video link not found.',
        'slot' => 'The video redirect xyz for href.yt was not found.',
    ], 404))->name('preview.yt-error');
    Route::get('/_preview/clicked', function () {
        $locale = PublicHost::resolveClickedLocale();
        app()->setLocale($locale);

        $response = response()->view('landing.clicked', ['locale' => $locale]);

        $param = request()->query('lang') ?? request()->query('locale');
        if (is_string($param) && in_array(strtolower(trim($param)), ['en', 'de'], true)) {
            session(['clicked_locale' => $locale]);
            $response->withCookie(cookie()->forever('clicked_locale', $locale));
        }

        return $response;
    })->name('preview.clicked');
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

Route::get('/impressum', function (Request $request) {
    return DomainUrls::handleImpressumRedirect($request);
})->name('impressum');

Route::get('/imprint', function (Request $request) {
    return DomainUrls::handleImpressumRedirect($request);
})->name('imprint');

// Dedicated QR code generator routes for the official QR API host.
Route::domain((string) config('domains.qr_api_host', 'qr.t-api.de'))->group(function () {
    Route::get('/url/{url}', [QrController::class, 'generateUrl'])->where('url', '.*')->name('qr.direct-url');
    Route::get('/text/{text}', [QrController::class, 'generateText'])->where('text', '.*')->name('qr.direct-text');
    Route::get('/wifi/{ssid?}', [QrController::class, 'generateWifi'])->where('ssid', '.*')->name('qr.direct-wifi');
    Route::get('/vcard', [QrController::class, 'generateVcard'])->name('qr.direct-vcard');
    Route::get('/contact', [QrController::class, 'generateVcard'])->name('qr.direct-contact');
    Route::get('/email/{email?}', [QrController::class, 'generateEmail'])->where('email', '.*')->name('qr.direct-email');
    Route::get('/phone/{phone}', [QrController::class, 'generatePhone'])->where('phone', '.*')->name('qr.direct-phone');
    Route::get('/tel/{phone}', [QrController::class, 'generatePhone'])->where('phone', '.*')->name('qr.direct-tel');
    Route::get('/sms/{phone?}', [QrController::class, 'generateSms'])->where('phone', '.*')->name('qr.direct-sms');
    Route::get('/whatsapp/{phone?}', [QrController::class, 'generateWhatsapp'])->where('phone', '.*')->name('qr.direct-whatsapp');
    Route::get('/geo/{coords?}', [QrController::class, 'generateGeo'])->where('coords', '.*')->name('qr.direct-geo');
    Route::get('/event', [QrController::class, 'generateEvent'])->name('qr.direct-event');
    Route::get('/calendar', [QrController::class, 'generateEvent'])->name('qr.direct-calendar');
    Route::get('/crypto/{address?}', [QrController::class, 'generateCrypto'])->where('address', '.*')->name('qr.direct-crypto');
    Route::get('/raw/{data}', [QrController::class, 'generateRaw'])->where('data', '.*')->name('qr.direct-raw');
});

Route::domain((string) config('domains.qr_api_host', 'qr.t-api.de'))
    ->get('/', fn () => redirect('/v1/'))
    ->name('qr.api.home');

Route::domain((string) config('domains.protected_host', 'protected.ternis.link'))->group(function () {
    Route::get('/', [ProtectedGatewayController::class, 'index'])->name('protected.index');
    Route::get('/challenge', [ProtectedGatewayController::class, 'challenge'])->name('protected.challenge');
    Route::get('/user-agent', [ProtectedGatewayController::class, 'userAgent'])->name('protected.user-agent');
    Route::post('/verify', [ProtectedGatewayController::class, 'verify'])->name('protected.verify');
    Route::post('/unlock', [ProtectedGatewayController::class, 'unlock'])->name('protected.unlock');
});

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

    // Link Threat & Security Inspector UI (href.nz, meinlink.at, and ternis.link only)
    Route::match(['get', 'post'], '/scan', [ScanUiController::class, 'show'])
        ->middleware('throttle:10,1')
        ->name('scan.ui');

    // Pretty QR codes (public hosts only, throttled like /v1/qr):
    // /qr/{url} (PNG default), /qr/{url}/{mime}, /{slug}/qr[.mime]
    // and /{url}.{mime} for short-link slugs or direct URLs. Above
    // the catch-all so slugs stay resolvable; unknown slugs 404. The
    // mime route comes first: /qr/{url} is greedy and would otherwise
    // swallow the trailing /{mime} segment into the URL (then it just
    // encodes). Slug-QR routes come before the suffixed route so
    // /{slug}/qr.png isn't misread as url "{slug}/qr".
    Route::middleware(['ensure.domain:public', 'throttle:10,1'])->group(function () {
        Route::get('/qr/text/{text}', [QrController::class, 'generateText'])->where('text', '.*')->name('qr.text');
        Route::get('/qr/wifi/{ssid?}', [QrController::class, 'generateWifi'])->where('ssid', '.*')->name('qr.wifi');
        Route::get('/qr/vcard', [QrController::class, 'generateVcard'])->name('qr.vcard');
        Route::get('/qr/contact', [QrController::class, 'generateVcard'])->name('qr.contact');
        Route::get('/qr/email/{email?}', [QrController::class, 'generateEmail'])->where('email', '.*')->name('qr.email');
        Route::get('/qr/phone/{phone}', [QrController::class, 'generatePhone'])->where('phone', '.*')->name('qr.phone');
        Route::get('/qr/tel/{phone}', [QrController::class, 'generatePhone'])->where('phone', '.*')->name('qr.tel');
        Route::get('/qr/sms/{phone?}', [QrController::class, 'generateSms'])->where('phone', '.*')->name('qr.sms');
        Route::get('/qr/whatsapp/{phone?}', [QrController::class, 'generateWhatsapp'])->where('phone', '.*')->name('qr.whatsapp');
        Route::get('/qr/geo/{coords?}', [QrController::class, 'generateGeo'])->where('coords', '.*')->name('qr.geo');
        Route::get('/qr/event', [QrController::class, 'generateEvent'])->name('qr.event');
        Route::get('/qr/calendar', [QrController::class, 'generateEvent'])->name('qr.calendar');
        Route::get('/qr/crypto/{address?}', [QrController::class, 'generateCrypto'])->where('address', '.*')->name('qr.crypto');
        Route::get('/qr/raw/{data}', [QrController::class, 'generateRaw'])->where('data', '.*')->name('qr.raw');

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

    // Password unlock for protected slugs (POST only, throttled).
    Route::middleware(['ensure.domain:public,business,ternis,partner', 'throttle:10,1'])->post('/{input}/unlock', [RedirectController::class, 'unlock'])
        ->where('input', '^(?!v\d+$)[a-zA-Z0-9_-]+$')
        ->name('redirect.unlock');
});
