# ternis.link

A Laravel PHP-powered link-shortening and insights service by **ternis-edv.de** (`ternis.dev`), focused on referrer tracking, high-performance redirects, and multi-domain architecture.

---

## 🚀 Key Features

- **Multi-Domain Routing**:
  - `href.nz` / `*.href.nz`: Public link shortener with its own landing page (`landing.public`, `public/css/landing-public.css`). Guest form + direct `/url/{url}` redirects and bare redirects.
  - `href.re` / `*.href.re`: Reserved for official business links with its own landing page (`landing.business`, `public/css/landing-business.css`, no guest form).
  - `ternis.link` / `*.ternis.link`: Reserved for family members, relatives, and partners (generic `landing.index` fallback).
  - `links.thosted.de`, `short.thosted.de`, `go.thosted.de`: Internal infrastructure redirects.
  - `go.ternis.net`, `go.ternis.dev`, `go.ternis.org`, `go.ternis.eu`: Go-style vanity redirects.
  - `dash.ternis.link`: User dashboard with live management & insights.
  - `admin.ternis.link`: Admin dashboard with system overview, link moderation, and user/plan management.
  - `links.t-api.de`: Dedicated API domain (`/v1`, `/` → latest version).
  - `api.ternis.link`: Permanent redirect to `links.t-api.de`.
  - Landing assets (CSS + fonts) are fully local: `public/css/landing-*.css`, `public/css/error.css`, `public/fonts/*.woff2` (Inter + Space Grotesk, no CDN).
- **Authentication — Ternis Auth SSO Only**:
  - **No local passwords or registration**: Authentication is delegated exclusively to Ternis Auth SSO via OAuth 2.0 / OpenID Connect with PKCE.
  - Silent SSO authentication check support (`prompt=none`).
  - Roles (`admin`, `partner`, `family`, `user`) automatically synchronized from SSO claims and badge metadata.
  - Avatars served through `user.t-api.de/{sso_sub}.png`.
- **Deterministic Slug vs. URL Classifier**:
  - Slugs restricted to `[a-zA-Z0-9_-]`.
  - Inputs with dots, colons, or slashes are automatically treated as direct URLs; otherwise, looked up as short slugs.
- **Anonymous Link Creation**:
  - Guests shorten links without an account via the `href.nz` landing-page form or `POST /v1/links/public` (public system domains only). Guest slugs are always auto-generated (8 chars) — custom `slug` values are rejected with `422 { slug: prohibited }`, and `LinkService::create()` throws for guests passing a custom slug.
  - Logged-in users may choose custom slugs (plan minimum enforced, free = 6) or leave blank for auto-generation (plan minimum length, free = 6 chars).
  - Abuse-contained via `throttle:10,1` per IP plus a 50/day per-IP quota (tracked by SHA-256 IP hash, never raw IPs). API keys and plans unlock shorter slugs.
- **Analytics & Tracking**:
  - Every redirect logs referrers, user agents, IP hashes (SHA-256 for privacy), and timestamp asynchronously.
  - Direct URL redirects (`/url/*`) are tracked with `is_direct_url = true` (visible only to admins).
  - Hot slugs are cached for 5 minutes (`link:{domain_id}:{slug}`) — misses are never cached, writes invalidate via model events, and the expiry cleanup invalidates explicitly.
- **Frontend**:
  - Built with Blade + Livewire 4.
  - Custom Vanilla nested CSS (`public/css/app.css`) with dark mode interface, plus per-domain landing CSS (`landing-public.css`, `landing-business.css`) and shared error CSS (`error.css`).
  - Self-hosted fonts (`public/fonts/inter-var.woff2`, `space-grotesk-var.woff2`) — no external CDN.
  - Custom branded error pages (`resources/views/errors/404,403,419,429,500,503.blade.php`) for web requests; API/`expectsJson` requests still receive JSON.
- **Deployment**:
  - Production-ready `Caddyfile` for automatic HTTPS and multi-domain proxying.

---

## 🚢 Production Deployment

1. **Environment**: copy `.env.example` → `.env` and apply the `Production overrides`
   block (`APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`,
   `TRUSTED_PROXIES=*`, PostgreSQL + Redis, Ternis Auth credentials).
2. **Install & migrate**: `composer install --no-dev`, `php artisan key:generate`,
   `php artisan migrate --force`, `npm install && npm run build`.
3. **Queue worker** (async `RecordClick` analytics — do not stay on `sync`):
   run `php artisan queue:work --tries=3` under systemd/supervisor with restarts.
4. **Scheduler** (daily `links:deactivate-expired` cleanup):
   `* * * * * php /var/www/ternis-link/artisan schedule:run >> /dev/null 2>&1`.
5. **Web server**: use the shipped `Caddyfile` (automatic TLS for all domains).
   The app trusts the proxy via `TRUSTED_PROXIES` so client IPs stay correct.
6. **Health check**: point monitoring at `GET /healthz` — `200 {"status":"ok"}`
   when the database is reachable, `503` otherwise. Answers on any Host/IP.

**Rate-limit layers**: HTTP `throttle:api` (60/min) on API v1, `throttle:10,1` on
OAuth entry points, plus per-plan per-minute/daily quotas enforced in
`LinkService`. Public redirects are intentionally unthrottled for speed.

**Host pinning**: `ResolveDomain` runs once globally; auth routes (`/login`,
`/auth/*`, `/logout`) are served on `dash/admin.ternis.link` and 302 to the
dashboard host (`config/domains.dashboard_host`, scheme-preserving) from
short-link hosts (`public,business,ternis,partner`) — so `href.nz/login` and
`ternis.link/login` redirect instead of 404ing — and 404 elsewhere (API hosts).
Single route definitions branch on `domain_type` inside the handler (Laravel only
matches the first route per URI, so duplicated per-host routes would shadow each other).
Authenticated `/v1/*` is `links.t-api.de`-only, `/dashboard/*` is
`dash/admin.ternis.link`-only, `/admin/*` is `admin.ternis.link`-only
(`/` there redirects to the admin overview), and `/url/*` + `/go/*` + `/{slug}` are
short-link hosts only (`public,business,ternis,partner`). Guest redirects on
auth-gated hosts go to same-host `/login` (never `route('login')`, which resolves
against `APP_URL` and would 404 in production). Landing `/` is public on
short-link hosts (no `EnforceDomainAccess`): `public` → `landing.public`,
`business` → `landing.business`, `ternis/partner` → `landing.index` fallback;
dashboard/admin `/` redirect guests to same-host `/login`. Slugs matching
`v{number}` (e.g. `v1`) are reserved on redirect hosts so the public
`GET /v1/` version root falls through to the API. `/healthz`
bypasses resolution so LB/IP probes always answer. API contract:
`docs/api-v1-openapi.yaml`.

**API versioning**: `EnsureApiVersion` runs before auth on all `/v1/*`
routes. Every response carries `API-Version` + `API-Latest-Version`;
deprecated versions add `Deprecation: true` + `Sunset`, retired versions
return `410 { message, version, latest_version }`. `GET /v1/` is public
(no auth) and is the landing target of `links.t-api.de/`. Errors are
`{ message }` (`{ message, errors }` for validation); plan per-minute
overages are `429` with `Retry-After`.

---

## 🛠 Tech Stack

- **Framework**: Laravel 13 (PHP 8.3+)
- **Frontend**: Blade + Livewire 4
- **Styling**: Vanilla CSS (CSS nesting)
- **Database**: SQLite (local/testing) / PostgreSQL or MySQL (production)
- **Auth**: Ternis Auth SSO (OAuth 2.0 + PKCE)
- **Web Server**: Caddy

---

## 🧪 Testing

Run the test suite:

```bash
php artisan test
```

All 132 feature and unit tests cover:
- URL vs. Slug classification and URL normalization
- Unique slug generation per domain (guests always 8-char auto, authed 6-char default via plan minimum)
- Anonymous link creation (public API rejects custom slugs, guest web form has no slug field, quotas, throttling)
- Per-domain landing pages (`href.nz` public + form vs `href.re` business, local fonts/CSS)
- Auth redirect shims (`href.nz/login`, `ternis.link/login`, `href.re/login` 302 to dashboard host)
- Custom error pages (web HTML `errors/*` views, JSON for API/`expectsJson`)
- Multi-domain resolution middleware & wildcard subdomains
- Host pinning (`EnsureDomainType` 404s on wrong hosts, healthz bypass)
- API polish (`GET /v1/` metadata, `API-Version` headers, `Deprecation`/`Sunset`, `410` retired, `{ message }` errors, `429` + `Retry-After`)
- Ternis Auth OAuth PKCE authorization redirect & user provisioning callback
- Direct URL redirects (`/url/{url}`, `/go/{url}`) & bare path redirects
- API v1 CRUD endpoints, Bearer API key authentication, and click analytics
- Livewire dashboard link table, link creation form, and API key manager
- Admin dashboard (`admin.ternis.link` overview, link moderation, user role/plan management, host pinning, self-demotion guard)
- Redirect cache (hot-slug hits skip DB, per-domain keys, update/deactivate/expiry invalidation, no negative caching)

> Multi-domain tests must put the host in the URL
> (e.g. `$this->get('http://links.t-api.de/v1/links')`):
> `withHeaders(['Host' => ...])` is ignored for relative URIs because
> Laravel prepends `APP_URL` instead.
