# ternis.link — Initial Project Plan

> Generated from [`.prompts/2026-09-17-01_INITIAL_INFO.md`](file:///Users/fabianternis/Code/GitHub/ternis-dev/ternis.link/.prompts/2026-09-17-01_INITIAL_INFO.md)
> Date: 2026-09-17T17:31:02+02:00

---

## 1. Project Overview

**ternis.link** is a Laravel PHP-powered link-shortening and insights platform focused on analytics and referrer tracking, operated by **ternis-edv.de** (ternis.dev).

---

## 2. Domain Architecture

### 2.1 Primary Domains

| Domain | Purpose | Access |
|---|---|---|
| `ternis.link` / `*.ternis.link` | Reserved for ternis family, relatives & partners | Invite-only |
| `href.re` / `*.href.re` | Reserved for ternis official business | Internal |
| `href.nz` / `*.href.nz` | Public link shortener | Public (with tiers) |
| `links.thosted.de` / `short.thosted.de` / `go.thosted.de` | ternis-hosted related things only | Internal |
| `go.ternis.net` / `go.ternis.dev` / `go.ternis.org` / `go.ternis.eu` | Go-style redirects | Internal |

### 2.2 Special-Purpose Domains

| Domain | Purpose |
|---|---|
| `links.t-api.de` | API only (`/v{version}`, `/` → latest) |
| `dash.ternis.link` | User dashboard |
| `admin.ternis.link` | Admin dashboard (potential) |
| `api.ternis.link` | Redirects to links.t-api.de |

### 2.3 Retired Domains

- ~~`short.static.re`~~ — no longer in use

---

## 3. Feature Breakdown

### 3.1 Public Link Shortening (`href.nz`)

- **`/url/{url}`** — Direct redirect (preferred)
- **`/go/{url}`** — Alternative direct redirect
- **`/{url}`** — Bare-path direct redirect (also supported)
- **`/{link_slug}`** — Redirect to a previously shortened URL
- Anyone can create short links without an API key
- API key unlocks shorter slugs
- Paid plans unlock custom subdomains and more features

### 3.2 Analytics & Tracking

- Every redirect is stored in the database (referrer, timestamp, etc.)
- `href.nz/url/*` redirects are visible to **admins only**
- Named short links provide full analytics to their owners

### 3.3 Multi-Tenancy / Partners

- Partners can add their own domains/subdomains
- `*.ternis.link` subdomains for family/relatives/partners

### 3.4 API

- Base URL: `links.t-api.de/v{version_id}`
- Root `/` redirects to the latest API version

### 3.5 Dashboards

- **User Dashboard**: `dash.ternis.link`
- **Admin Dashboard**: `admin.ternis.link` (planned)

---

## 4. Technical Architecture

### 4.1 Stack

- **Framework**: Laravel (PHP)
- **Frontend**: Blade + Livewire
- **Styling**: Vanilla CSS (nested CSS, no preprocessor)
- **Auth**: Ternis Auth SSO (OAuth 2.0 + OpenID Connect 1.0, Authorization Code Flow with PKCE)
- **Database**: MySQL / PostgreSQL (TBD)
- **Queue**: Redis / Horizon (for async analytics writes)
- **Cache**: Redis (for hot slug lookups)
- **Web Server**: Caddy (automatic HTTPS, multi-domain routing)

### 4.2 URL vs Slug Detection (`href.nz/{input}`)

When a request hits `href.nz/{input}`, the resolver must distinguish between a bare URL and a stored slug:

```
resolve(input):
  1. If input contains a dot (.) or colon (:)     → treat as URL → direct redirect
     e.g. "google.com", "https://example.org"
  2. If input contains a slash (/) after the host → treat as URL → direct redirect
     e.g. "example.com/path"
  3. Otherwise                                     → treat as slug → lookup in DB
     e.g. "myslug", "abc123"
  4. If slug not found                             → 404
```

> Slugs are restricted to `[a-zA-Z0-9_-]` — no dots, colons, or slashes — making disambiguation deterministic.

### 4.3 Key Models

```
User
├── has many: Links
├── has many: Domains (partners)
├── belongs to: Plan

Link
├── slug (unique per domain)
├── destination_url
├── domain_id
├── user_id (nullable for anonymous)
├── click_count (denormalized)

Click (analytics)
├── link_id
├── referrer
├── user_agent
├── ip_hash
├── country / geo
├── timestamp

Domain
├── hostname
├── user_id (nullable = system domain)
├── type: enum(ternis, business, public, partner)

Plan
├── name
├── min_slug_length
├── custom_subdomain: bool
├── rate_limit
```

### 4.4 Domain Routing Strategy

Laravel middleware to resolve the incoming domain and apply access rules:

```
Request → DomainResolver middleware
  ├── ternis.link / *.ternis.link  → require auth (family/partner)
  ├── href.re / *.href.re          → require auth (business)
  ├── href.nz / *.href.nz          → public (tiered)
  ├── links.t-api.de               → API routes only
  ├── dash.ternis.link              → dashboard SPA
  └── partner domains               → lookup in domains table
```

### 4.5 Authentication — Ternis Auth SSO Integration

ternis.link **does not manage passwords or user registration** — all authentication is delegated to the Ternis Auth platform via OAuth 2.0 / OpenID Connect 1.0.

**SSO Provider Domains:**
- `auth.ternis.net` — Primary global SSO authority
- `auth.ternis.org` — Foundation & community auth
- `auth.ternis.dev` — Developer sandbox
- `auth.t-api.de` — API & German infrastructure gateway
- `auth.thosted.de` — ternis-hosted auth
- `account.ternis.org` / `account.ternis.net` — User self-service portals
- `user.t-api.de` — Profile picture & CDN (fast edge avatar delivery)
- `tauth.de` / `ternisauth.de` — German regional low-latency edge mirrors

**Integration flow:**
```
User → dash.ternis.link/login
  → Redirect to auth.ternis.net/oauth/authorize (Authorization Code + PKCE)
  → User authenticates at Ternis Auth
  → Callback to dash.ternis.link/auth/callback with ?code=...
  → Exchange code for access_token + refresh_token
  → Fetch /oauth/userinfo → get sub, name, email, user_type, role
  → Find-or-create local User record by SSO `sub` (UUID)
  → Establish Laravel session
```

**User type mapping from SSO claims:**

| SSO `user_type` | SSO Scopes | Local role | Notes |
|---|---|---|---|
| `ternis_member` | `ternis:member` | `admin` or `family` | Based on member badge / orgs |
| (general user) | `openid profile email ternis:sso` | `user` | Standard user |
| (paying customer) | `ternis:customer` | `user` (with paid plan) | Plan derived from entitlements |
| (verified partner) | `ternis:partner` | `partner` | Partner domain access |

**Profile Pictures** (`user.t-api.de`):
- Avatars are served via `https://user.t-api.de/{sso_sub}.png` (or `.svg`) — no auth needed
- Supports query params: `?size=128`, `?fallback=initials`, `?bg=4F46E5`
- Never returns broken images — falls back to neutral silhouette (HTTP 200 + `X-Ternis-Fallback` header)
- Username-based URLs also work: `https://user.t-api.de/{username}.png`

**Required `.env` config:**
```env
TERNIS_AUTH_BASE_URL=https://auth.ternis.net
TERNIS_AUTH_CLIENT_ID=<uuid>
TERNIS_AUTH_CLIENT_SECRET=<secret>
TERNIS_AUTH_REDIRECT_URI=https://dash.ternis.link/auth/callback
```

**Silent SSO** (`prompt=none`):
- To check if a user has an active Ternis Auth session without showing a login screen, redirect with `prompt=none`
- If authenticated → authorization code issued immediately (zero friction)
- If not → callback returns `?error=login_required` → show login button
- Useful for auto-login on `dash.ternis.link` page load

**Links API authentication** (`links.t-api.de`):
- Authenticated users get a local API key (stored in `api_keys` table)
- API requests use `Authorization: Bearer <api_key>` — validated locally, no SSO round-trip per request
- Alternatively, users can pass their Ternis Auth access token; the API validates it against the SSO userinfo endpoint (cached)

---

## 5. Implementation Phases

### Phase 1 — Foundation 🏗️

- [ ] Initialize Laravel project
- [ ] Set up database migrations (users, links, clicks, domains, plans)
- [ ] Ternis Auth SSO integration (OAuth 2.0 + PKCE login flow, user provisioning from SSO claims)
- [ ] Domain routing middleware
- [ ] Basic redirect engine (`/{slug}` → destination)
- [ ] Click tracking (synchronous first, then queue)

### Phase 2 — Public Link Shortening 🔗

- [ ] `href.nz/url/{url}` direct redirect (no slug, admin-only analytics)
- [ ] `href.nz/go/{url}` and `href.nz/{url}` alternatives
- [ ] Anonymous link creation (longer slugs)
- [ ] API key registration and authenticated link creation (shorter slugs)

### Phase 3 — API 🔌

- [ ] Versioned API at `links.t-api.de/v1`
- [ ] Root redirect to latest version
- [ ] CRUD endpoints for links
- [ ] Rate limiting per plan
- [ ] **Versioning strategy**: retired API versions keep full functionality (no breaking removals). Controllers and file structure are named per version (e.g. `App\Http\Controllers\Api\V1\LinkController`, `routes/api/v1.php`)

### Phase 4 — Dashboard 📊

- [ ] User dashboard at `dash.ternis.link`
- [ ] Link management (create, edit, delete, view analytics)
- [ ] Click analytics (referrer breakdown, time series, geo)

### Phase 5 — Multi-Tenancy & Partners 🤝

- [ ] Partner domain registration
- [ ] Custom subdomain support (paid plans)
- [ ] Per-domain slug namespace

### Phase 6 — Admin & Polish 🛡️

- [ ] Admin dashboard at `admin.ternis.link`
- [ ] System-wide analytics
- [ ] User/plan management
- [ ] Abuse detection & link moderation

---

## 6. Open Questions

1. **Database choice** — MySQL or PostgreSQL?
2. ~~**Frontend stack**~~ — ✅ Blade + Livewire with Vanilla CSS (nested)
3. ~~**Auth system**~~ — ✅ Ternis Auth SSO (OAuth 2.0 + OpenID Connect 1.0, Authorization Code + PKCE). No local passwords.
4. ~~**Hosting / deployment**~~ — ✅ Caddy web server
5. **Click privacy** — IP hashing algorithm & retention policy?
6. ~~**`href.nz/{url}` bare redirect**~~ — ✅ Resolved: slug charset `[a-zA-Z0-9_-]` makes detection deterministic (dots/colons/slashes → URL, otherwise → slug lookup)
