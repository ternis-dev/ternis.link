# ternis.link — Documentation Index

`ternis.link` is a Laravel 13 (PHP 8.3+) link-shortening and insights service with
multi-domain routing, SSO-only auth, async click analytics, and a Blade + Livewire 4 frontend.

Contract source of truth for the HTTP API: [`api-v1-openapi.yaml`](./api-v1-openapi.yaml).

## Contents

| Doc | What it covers |
|-----|----------------|
| [`architecture.md`](./architecture.md) | System overview, tech stack, request lifecycle, middleware pipeline, key services |
| [`domains-routing.md`](./domains-routing.md) | All hosts, `domain_type`s, `ResolveDomain` / `EnsureDomainType` / `EnforceDomainAccess`, host pinning, `Caddyfile` |
| [`authentication.md`](./authentication.md) | Ternis Auth SSO (OAuth2 + PKCE), silent auth, refresh, demo login, roles, API auth (`tl_*` + SSO token) |
| [`links.md`](./links.md) | Link model, slug rules, slug-vs-URL classifier, quotas, guest vs authed creation, cache, expiry |
| [`api.md`](./api.md) | API v1 guide (CRUD, domains, clicks, public endpoint), versioning headers, errors, throttling, curl examples |
| [`analytics.md`](./analytics.md) | Click tracking pipeline, `RecordClick` queue, dashboard charts, CSV export |
| [`custom-domains.md`](./custom-domains.md) | Custom hostname registration, DNS TXT verification, subdomain claims, plan gating |
| [`privacy-security.md`](./privacy-security.md) | IP hashing, encrypted capture + retention, junk-URL detector, throttling, error encounters |
| [`frontend.md`](./frontend.md) | Blade layouts, UI component system, landing pages, Livewire components, Chart.js, themes, fonts |
| [`dashboard-admin.md`](./dashboard-admin.md) | User dashboard and admin panel page-by-page |
| [`database.md`](./database.md) | Schema, ER overview, ULIDs, seeders (plans/domains/versions) |
| [`deployment.md`](./deployment.md) | Env, install, queue worker, scheduler, Caddy/TLS, health checks, production checklist |
| [`development.md`](./development.md) | Local setup, testing (incl. multi-domain pitfall), code style, Vite |
| [`operations.md`](./operations.md) | Artisan commands, scheduler, monitoring, troubleshooting |

## Quick links

- Health probe: `GET /healthz` → `200 {"status":"ok"}` on any Host/IP (`app/Http/Controllers/HealthController.php`).
- API base (production): `https://links.t-api.de/v1` — `GET /v1/` is public version metadata.
- Dashboard: `https://dash.ternis.link` — Admin: `https://admin.ternis.link`.
- Public shortener: `https://href.nz` — Business: `https://href.re`.
