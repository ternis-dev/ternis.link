# Domains & Routing

Source files: `config/domains.php`, `app/Http/Middleware/ResolveDomain.php`,
`app/Http/Middleware/EnsureDomainType.php`, `app/Http/Middleware/EnforceDomainAccess.php`,
`routes/web.php`, `routes/api/v1.php`, `bootstrap/app.php`, `Caddyfile`.

## Domain → type map (`config/domains.php`)

| Host(s) | `domain_type` | Purpose |
|---------|---------------|---------|
| `href.nz`, `*.href.nz` | `public` | Public shortener + guest form (`landing.public`) |
| `href.re`, `*.href.re` | `business` | Official business links (`landing.business`, no guest form) |
| `ternis.link`, `*.ternis.link` | `ternis` | Family/relatives/partners (`landing.index` fallback) |
| `links.thosted.de`, `short.thosted.de`, `go.thosted.de` | `ternis` | Internal infra redirects |
| `go.ternis.net`, `go.ternis.dev`, `go.ternis.org`, `go.ternis.eu` | `ternis` | Go-style vanity redirects |
| `links.t-api.de` | `api` | API (`/v1`, `/` → latest version) |
| `api.ternis.link` | — | Permanent redirect to `links.t-api.de` (handled in `ResolveDomain`) |
| `dash.ternis.link` | `dashboard` | User dashboard |
| `admin.ternis.link` | `admin` | Admin panel |
| Custom partner hostnames (`domains` table) | `partner` | Verified custom domains |
| `*.ternis.link`, `*.href.re`, `*.href.nz` (`wildcard_roots`) | looked up in `domains` table, else 404 | Subdomain claims |

`auth_required`: `ternis/business/dashboard/admin = true`, `public = false`, `api = per-route`.
Canonical hosts: `dashboard_host` (`DOMAIN_DASHBOARD`), `public_host`, `business_host`.

## Middleware

### `ResolveDomain` (global `prepend`)
Order:
1. Bypass `healthz` / `up` (LB/IP probes answer on any Host).
2. `api.ternis.link` → `301 https://links.t-api.de{uri}`.
3. `config/domains.map` direct match.
4. Wildcard `*.ternis.link|*.href.re|*.href.nz` → `domains` table lookup, else 404.
5. Custom partner domains table lookup.
6. Localhost/testserver fallback (`admin*` → admin; `dashboard*|login*|auth*|links*|new|api-keys*|domains*|settings*` → dashboard; `v1*` → api; else public href.nz).
7. `abort(404 Unknown domain)` otherwise.
Sets request attributes `domain_type`, `domain_hostname`, `domain_model`. Do **not**
wrap routes in a second `ResolveDomain` group (double DB lookups).

### `EnsureDomainType` (`ensure.domain:a,b,...`)
404s when `domain_type` is not in the allowed list. Runs **before** auth on
dashboard/admin/API routes so wrong hosts fail fast without leaking route existence.

### `EnforceDomainAccess` (`enforce.domain` / `EnforceDomainAccess::class`)
Reads `config('domains.auth_required.{type}')`:
- Guests on `ternis,business,dashboard,admin` → same-host `/login` redirect
  (never `route('login')` — that resolves against `APP_URL=https://href.nz` and would 404 in prod).
- `admin` requires `User::isAdmin()` else 403.
- `ternis` requires `admin|family|partner` else 403.
- `business` requires admin else 403.
- `public,api` pass through.
- Short-link resolution (`/url`, `/go`, `/{slug}`) intentionally skips this middleware: opening a link is public on every redirect host.

## Route inventory (`routes/web.php`)

| Method | URI | Name | Constraint | Notes |
|--------|-----|------|------------|-------|
| GET | `/healthz` | `healthz` | `withoutMiddleware(ResolveDomain)` | Any Host/IP, `HealthController` JSON |
| GET | `/login` | `login` | in-handler branch | dashboard/admin serve; short-link hosts 302 to dashboard host; else 404 |
| GET | `/auth/redirect` | `auth.redirect` | `throttle:10,1` + branch | PKCE start |
| GET | `/auth/silent` | `auth.silent` | `throttle:10,1` + branch | `prompt=none` iframe |
| GET | `/auth/callback` | `auth.callback` | `throttle:10,1` + branch | code exchange; stale codes → login with message, not 500 |
| POST | `/logout` | `logout` | branch | local logout + optional RP-initiated end-session |
| GET | `/auth/demo` | `auth.demo` | local/testing only + branch | `demoLogin` |
| GET | `/dashboard`, `/dashboard/{any}` | — | dashboard host only (+ `auth` + `refresh.sso` + `enforce.domain`) | Legacy prefix: 301 to root URLs (localhost serves home); admin host 404s |
| GET | `/admin{any?}` on dashboard host | — | same group | 302 to admin host root equivalent, prefix stripped (`/admin/users` → `/users`) |
| GET | `/` on dashboard host | `dashboard` | same group, host-pinned | Avoids colliding with landing `/` in the route collection |
| GET | `/new`, `/links`, `/links/create`, `/links/{link}`, `/links/{link}/edit`, `/links/{link}/export`, `/api-keys`, `/domains`, `/notifications`, `/activity`, `/settings` | `dashboard.*` | same group, dashboard host only | `DashboardController` (strictly per-user; layout `layouts.dashboard`) |
| GET | `/`, `/links`, `/users`, `/domains`, `/activity`, `/errors` on admin host | `admin.*` | `ensure.domain:admin` + `auth` + `refresh.sso` + `enforce.domain`, host-pinned | `AdminController` (system-wide; distinct layout `layouts.admin`) |
| GET | `/admin{any?}` on admin host | — | `ensure.domain:admin`, no auth | Legacy 301 to root equivalents (`/admin/users` → `/users`) |
| GET | `/legal/{any}` `.*` | `legal.show` | in-handler branch (no ensure.domain) | ternis serves (allowlist `terms,privacy`); dashboard/admin 301 to `https://ternis.link/legal/*`; else 404 |
| GET | `/legal/{any}` on dashboard/admin hosts | — | `ensure.domain:dashboard,admin` | 301 to `https://ternis.link/legal/*` (single canonical legal host) |
| GET | `/pages/stats`, `/pages/stats/domains`, `/pages/stats/links` | `pages.stats.*` | `ensure.domain:ternis`, public, nothing exportable | Aggregate-only network stats (counts by day/domain, no PII); removed links stay counted |
| GET | `/stats{any?}` | — | `ensure.domain:ternis` | Legacy 301 to `/pages/stats/*` (the `/pages/` namespace never collides with shortlink slugs) |
| GET | `/` | `home` | none (branches on `domain_type`) | dashboard → login/dashboard; admin → login/admin; api → 302 `/v{latest}/`; business → `landing.business`; public → `landing.public`; else `landing.index` |
| GET | `/url/{url}` `.*` | `redirect.url` | `ensure.domain:public,business,ternis,partner`, public (no auth) | Preferred direct-URL redirect |
| GET | `/go/{url}` `.*` | `redirect.go` | same | Alternative direct-URL redirect |
| GET | `/preview/{input}` `.*` | `redirect.preview` | same (handler 404s except href.nz) | Above catch-all; sandbox preview |
| GET | `/{input}` `^(?!v\d+$)[^/]+$` | `redirect.resolve` | same, **last** | Slug-vs-URL detect, public on all hosts; `v{number}` reserved so `/v1` reaches the API |

Why auth branching lives inside the handler: Laravel matches only the **first**
route per URI — duplicated per-host `/login` routes would shadow each other,
so a single definition inspects `domain_type` and serves / 302s / 404s.

## API host pinning (`routes/api/v1.php`, mounted as `prefix v1`)

- `GET /v1/` — `ensure.domain:api`, public, `throttle:api` → `VersionController@show`
  (also landing target of `links.t-api.de/`).
- `POST /v1/links/public` — `ensure.domain:api,public`, `throttle:10,1`, anonymous.
- All other `/v1/*` — `ensure.domain:api` + `ensure.api-version:1` + `auth.api` + `throttle:api`.
- Slugs matching `v{number}` are reserved on redirect hosts so single-segment API
  roots fall through to the API instead of becoming slugs.

## Caddy (`Caddyfile`)

Every block uses `root * /var/www/ternis-link/public` + `php_fastcgi unix//run/php/php-fpm.sock` +
`file_server` + `encode gzip`; Laravel does host routing. Blocks:
`href.nz (+*.href.nz)`, `href.re (+*.href.re)`, `ternis.link (+*.ternis.link)`,
`links/short/go.thosted.de`, `go.ternis.{net,dev,org,eu}`, `links.t-api.de`,
`api.ternis.link → redir https://links.t-api.de{uri} permanent`,
`dash.ternis.link + admin.ternis.link`.
