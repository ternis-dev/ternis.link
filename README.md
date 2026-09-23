# ternis.link

A Laravel PHP-powered link-shortening and insights service by **ternis-edv.de** (`ternis.dev`), focused on referrer tracking, high-performance redirects, and multi-domain architecture.

---

## 🚀 Key Features

- **Multi-Domain Routing**:
  - `href.nz` / `*.href.nz`: Public link shortener (tiered plans, direct `/url/{url}` redirects, and bare redirects).
  - `href.re` / `*.href.re`: Reserved for official business links.
  - `ternis.link` / `*.ternis.link`: Reserved for family members, relatives, and partners.
  - `links.thosted.de`, `short.thosted.de`, `go.thosted.de`: Internal infrastructure redirects.
  - `go.ternis.net`, `go.ternis.dev`, `go.ternis.org`, `go.ternis.eu`: Go-style vanity redirects.
  - `dash.ternis.link`: User dashboard with live management & insights.
  - `links.t-api.de`: Dedicated API domain (`/v1`, `/` → latest version).
  - `api.ternis.link`: Permanent redirect to `links.t-api.de`.
- **Authentication — Ternis Auth SSO Only**:
  - **No local passwords or registration**: Authentication is delegated exclusively to Ternis Auth SSO via OAuth 2.0 / OpenID Connect with PKCE.
  - Silent SSO authentication check support (`prompt=none`).
  - Roles (`admin`, `partner`, `family`, `user`) automatically synchronized from SSO claims and badge metadata.
  - Avatars served through `user.t-api.de/{sso_sub}.png`.
- **Deterministic Slug vs. URL Classifier**:
  - Slugs restricted to `[a-zA-Z0-9_-]`.
  - Inputs with dots, colons, or slashes are automatically treated as direct URLs; otherwise, looked up as short slugs.
- **Anonymous Link Creation**:
  - Guests can shorten links without an account via the `href.nz` landing-page form or `POST /v1/links/public` (public system domains only, 8+ char slugs).
  - Abuse-contained via `throttle:10,1` per IP plus a 50/day per-IP quota (tracked by SHA-256 IP hash, never raw IPs). API keys and plans unlock shorter slugs.
- **Analytics & Tracking**:
  - Every redirect logs referrers, user agents, IP hashes (SHA-256 for privacy), and timestamp asynchronously.
  - Direct URL redirects (`/url/*`) are tracked with `is_direct_url = true` (visible only to admins).
- **Frontend**:
  - Built with Blade + Livewire 4.
  - Custom Vanilla nested CSS (`public/css/app.css`) with dark mode interface.
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

All 94 feature and unit tests cover:
- URL vs. Slug classification and URL normalization
- Unique slug generation per domain
- Anonymous link creation (public API, guest web form, quotas, throttling)
- Multi-domain resolution middleware & wildcard subdomains
- Ternis Auth OAuth PKCE authorization redirect & user provisioning callback
- Direct URL redirects (`/url/{url}`, `/go/{url}`) & bare path redirects
- API v1 CRUD endpoints, Bearer API key authentication, and click analytics
- Livewire dashboard link table, link creation form, and API key manager
