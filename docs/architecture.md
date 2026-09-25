# Architecture

## Stack

- **Framework:** Laravel 13, PHP `^8.3` (`composer.json`)
- **Frontend:** Blade + Livewire 4, Tailwind CSS v4 (`@tailwindcss/vite`), Chart.js 4
- **Build:** Vite 8 (`vite.config.js` inputs: `resources/css/app.css`, `resources/css/landing-public.css`, `resources/js/app.js`)
- **DB:** SQLite (local/testing) / PostgreSQL or MySQL (production); `database/database.sqlite` for dev
- **Queue/Cache/Session:** `sync`/`database` locally, Redis in production (`.env.example`)
- **Auth:** Ternis Auth SSO only (OAuth 2.0 + PKCE, `app/Services/TernisAuthService.php`) — no local passwords
- **Web server:** Caddy (`Caddyfile`) — automatic TLS, all vhosts proxy to the same Laravel app

## High-level layout

```
app/
  Console/Commands/   DeactivateExpiredLinks, PruneExpiredIps, PurgeJunkLinks
  Enums/              UserRole, DomainType, ApiVersionStatus
  Http/
    Controllers/      Health, Redirect, Preview, Dashboard, Admin, Legal, Auth/*, Api/V1/*
    Middleware/       ResolveDomain, EnsureDomainType, EnforceDomainAccess,
                      AuthenticateApi, EnsureApiVersion, RefreshSsoToken
    Requests/         StoreLinkRequest, UpdateLinkRequest, StorePublicLinkRequest, StoreDomainRequest
  Jobs/               RecordClick (ShouldQueue)
  Livewire/           Public/ShortenForm, Dashboard/*, Admin/*
  Models/             User, Link, Domain, Click, ApiKey, ApiVersion, Plan, OAuthIdentity, ErrorEncounter
  Services/           LinkService, SlugGeneratorService, SlugResolverService, ClickTrackerService,
                      GeoIpService, DomainService, DomainVerificationService,
                      TernisAuthService, TurnstileService, JunkUrlDetector
  Support/            IpHash, IpCapture, DomainUrls
bootstrap/app.php     routing + global middleware + exception hook
config/domains.php    host → domain_type map, wildcard roots, canonical hosts, auth_required
config/privacy.php    IP_CAPTURE_ENABLED / IP_RETENTION_DAYS
config/services.php   ternis_auth (base_url, client_*, redirect_uri, scopes, avatar, end_session), turnstile (site key/secret)
routes/web.php        healthz, auth shims, dashboard, admin, legal, landing, redirects
routes/api/v1.php     /v1/* (mounted with prefix v1 in bootstrap/app.php)
routes/console.php    scheduler (links:deactivate-expired, privacy:prune-ips — daily)
```

## Request lifecycle

1. **Trust proxies** (`bootstrap/app.php`): `TRUSTED_PROXIES` (default `*` behind Caddy) so
   `$request->ip()` / `isSecure()` reflect the real client. Required for IP-hash analytics + throttling.
2. **`ResolveDomain` (global prepend):** sets `domain_type`, `domain_hostname`, `domain_model`.
   Bypasses `/healthz` and `/up` so LB/IP probes always answer. `api.ternis.link` 301s to
   `links.t-api.de`. Unknown hosts 404 (`Unknown domain`). See `domains-routing.md`.
3. **Route matching (`routes/web.php`, `routes/api/v1.php`):**
   - Auth routes (`/login`, `/auth/*`, `/logout`) branch **inside the handler** on `domain_type`:
     `dashboard,admin` serve; `public,business,ternis,partner` 302 to the dashboard host;
     else 404. (Laravel matches only the first route per URI, so per-host duplicates would shadow.)
   - Dashboard routes require `ensure.domain:dashboard,admin` **before** `auth` (fail fast with 404
     instead of leaking route existence via login redirect), then `auth` + `refresh.sso` + `enforce.domain`.
   - Admin routes require `ensure.domain:admin` + `auth` + `refresh.sso` + `enforce.domain` under `/admin`.
   - Redirect routes require `ensure.domain:public,business,ternis,partner` + `enforce.domain`.
     The catch-all `/{input}` is **last** and excludes `v{number}` so `/v1` falls through to the API.
4. **API versioning (`EnsureApiVersion`):** runs before auth on `/v1/*`; always sets
   `API-Version` + `API-Latest-Version`; deprecated adds `Deprecation: true` + `Sunset`;
   retired returns `410 { message, version, latest_version }`. `GET /v1/` is public.
5. **Auth:**
   - Web: session guard (`config/auth.php`, provider `eloquent:User`), SSO token refreshed by
     `RefreshSsoToken` (logout + redirect to `/login` when refresh fails).
   - API: `AuthenticateApi` — `tl_*` API key (SHA-256 `key_hash` + 8-char `key_prefix`) or
     SSO bearer token validated against userinfo and cached 5 min (`sso_token:{sha256}`).
6. **Redirect path (`RedirectController`):** `SlugResolverService::classify` → slug lookup
   (`LinkService::resolveSlug`, 5-min `link:{domain_id}:{slug}` cache, misses never cached) or
   direct-URL tracking row (`findOrCreateDirectUrlLink`, slug `u_{sha256[:16]}`); then
   `ClickTrackerService::track` dispatches async `RecordClick`; 302 to destination.
   Unresolvable slugs render `redirect.not-found`.
7. **Errors:** `withExceptions` renders JSON for `api/*`, `v1/*`, or `expectsJson`; otherwise
   branded `errors/{403,404,419,429,500,503}` views. Every rendered non-validation error
   (except `healthz`/`up`) is persisted via `ErrorEncounter::record()` — never throws.
8. **Guest bot protection:** when Turnstile is configured, the public Livewire form requires a
   single-use widget token and `TurnstileService` verifies it with Cloudflare Siteverify before
   link creation. Partial key configuration fails closed; the public JSON API remains throttled
   but does not require a widget token.

## Key services

| Service | Responsibility |
|---------|----------------|
| `LinkService` | Creation (plan quotas, slug validation, junk rejection), `resolveSlug` cache, `update`/`deactivate` with invalidation, tag normalization (max 10, `^[a-z0-9][a-z0-9-]{0,28}[a-z0-9]$`) |
| `SlugGeneratorService` | Collision-checked random slugs (`generate(length, domainId)`) |
| `SlugResolverService` | `classify(input): slug\|url` (`.`, `:`, `/` → URL; else `[a-zA-Z0-9_-]` → slug), `normalizeUrl` (prepend `https://`) |
| `ClickTrackerService` | Builds `RecordClick` payload (referrer ≤2048, UA ≤512, IP hash, encrypted IP, geo) |
| `GeoIpService` | `lookup(request): [country_code, city]` |
| `DomainService` / `DomainVerificationService` | Normalize/validate hostnames, reserved-host guard, `createForUser`, `claimSubdomain`, TXT `verify` + `instructions`, `deactivate` |
| `TernisAuthService` | PKCE verifier/challenge, authorize + silent (`prompt=none`) URLs, code exchange, userinfo, `findOrCreateUser` (role mapping, default `free` plan, token store), `refreshAccessToken`, end-session URL |
| `TurnstileService` | Cloudflare Siteverify client; fails closed on partial configuration, transport errors, invalid/expired/replayed tokens, oversized tokens, and action/hostname mismatches |
| `JunkUrlDetector` | `isJunk` / `reasons` / `rejectIfJunk` (throws `JunkUrlException extends ValidationException`, 422) — scanner probes, localhost, private IPs |
| `IpHash` / `IpCapture` (`app/Support`) | HMAC-SHA256 IP hash (`IP_HASH_PEPPER`, fallback plain SHA-256); encrypted capture gate (`IP_CAPTURE_ENABLED`) + retention (`IP_RETENTION_DAYS`, default 30) |
| `DomainUrls` | `dashboard(path)` — absolute dashboard URL preserving scheme (avoids `route('login')` resolving against `APP_URL=href.nz` and 404ing) |

## Background work

- `RecordClick` (queue): creates `clicks` row + `increment click_count`. Production must run
  `php artisan queue:work --tries=3` (never stay on `sync`).
- `links:deactivate-expired` (daily): deactivates past-`expires_at` links, invalidates slug cache, preserves clicks.
- `privacy:prune-ips` (daily): nulls `clicks.ip_encrypted` / `links.creator_ip_encrypted` past retention; hashes stay.
- `links:purge-junk [--apply]` (manual): lists (default) or deactivates scanner-junk links.
