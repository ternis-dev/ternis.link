# Links

Source: `app/Models/Link.php`, `app/Services/LinkService.php`,
`app/Services/SlugGeneratorService.php`, `app/Services/SlugResolverService.php`,
`app/Http/Controllers/RedirectController.php`, `app/Http/Requests/*`.

## Model (`links`)

ULID primary key. Fields: `slug`, `destination_url` (text), `description` (≤500, nullable),
`tags` (json, nullable), `domain_id` (FK cascade), `user_id` (nullable, `nullOnDelete` —
null = guest-created), `click_count`, `is_active`, `expires_at`,
`creator_ip_hash` (64, SHA-256/HMAC — guest quota + forensics, indexed with `created_at`),
`creator_ip_encrypted` (Laravel-encrypted, short retention).
Constraints: `unique(domain_id, slug)`; indexes on `user_id`, `expires_at`.
Relations: `domain BelongsTo`, `user BelongsTo`, `clicks HasMany`.
Helpers: `isExpired()`, `isAccessible()` (`is_active && !expired`), `scopeAccessible()`,
`incrementClicks()`, `cacheKey(domainId, slug) = "link:{domain_id}:{slug}"`,
`forgetCachedSlug()`.

Tags: lowercase slugs, max 10 per link, `^[a-z0-9][a-z0-9-]{0,28}[a-z0-9]$`
(`LinkService::normalizeTags` lowercases/trims/dedupes/caps; `invalidTags` reports drops).

## Slug rules

- Charset: `[a-zA-Z0-9_-]` only (`validateCustomSlug`), max 255.
- Per-domain uniqueness (`slugAvailable`).
- Custom minimum length = owner's plan `min_slug_length` (free 6, pro 5, business/partner 3,
  family 1 — see `database/seeders/PlanSeeder.php`; fallback 6).
- Privileged users (admins, plans with `slug_length_choice`) may pass `slug_length` (3–64,
  clamped 1–64 defensively) to size auto-generated slugs; an explicit custom `slug` always wins.
- Guests: **always** auto-generated 8 chars (`GUEST_SLUG_LENGTH`); any custom `slug`
  throws `ValidationException` / `422 { slug: prohibited }`.

## Slug-vs-URL classifier (`SlugResolverService`)

`classify(input): 'url' | 'slug'`:
- contains `.`, `:`, or `/` → `url` (direct redirect, no slug lookup).
- else matches `^[a-zA-Z0-9_-]+$` → `slug`.
- fallback → `url`.
`normalizeUrl(url)`: prepends `https://` when no `http(s)://` scheme.

`RedirectController`:
- `GET /url/{url}` (`directUrl`) and `GET /go/{url}` (`goUrl`) — preferred direct-URL path.
- `GET /{input}` (`resolve`, catch-all, last) — classify then slug lookup or direct redirect.
- Slug path: `LinkService::resolveSlug` (hot-slug cache, then `accessible()` query);
  unknown slugs render `redirect.not-found`.
- Direct-URL path: `findOrCreateDirectUrlLink(url, domain)` — `firstOrCreate` on
  `(domain_id, slug = 'u_' + sha256(url)[:16])`; tracked with `is_direct_url = true`
  (visible only to admins).
- All redirects dispatch async click tracking, then 302 to the destination.
- `v{number}` slugs are reserved on redirect hosts so `/v1` reaches the API.

## Creation (`LinkService::create`)

Signature: `create(destinationUrl, domain, user?, customSlug?, expiresAt?, creatorIpHash?,
generatedLength?, description?, tags?, creatorIp?)`.

1. Trim + `junkUrls->rejectIfJunk` **first** (422 `JunkUrlException`; scanner probes never
   touch quota/rate-limit state).
2. Raw creator IP (guests): hash wins (`IpHash::make`) for quotas; encrypted copy kept
   for forensics when `IpCapture::enabled()`.
3. Guest + custom slug → `ValidationException` (guests get 8-char auto slugs).
4. Generated length: explicit choice (clamped) wins; else guest 8 / plan minimum (fallback 6).
5. Domain must be `is_active`; user-owned custom domains must be `verified_at`-set.
6. Quotas: authed → `ensureWithinDailyQuota` (plan `max_links_per_day`, null = unlimited;
   422) + `ensureWithinRateLimit` (plan `rate_limit_per_minute`; 429 `ThrottleRequestsException`
   with `Retry-After`); guest → `ensureAnonymousWithinDailyQuota` (50/day per IP hash;
   counts only `user_id null` rows with that hash, so internal direct-URL rows never count).
7. Custom slug → charset/length/uniqueness validation; else `slugGenerator->generate(length, domainId)`
   (collision-checked).
8. `Link::create` (description trimmed to 500, tags normalized, `is_active=true`); on success
   `RateLimiter::hit('create-link:{userId}', 60)` for authed users.

Anonymous entry points: href.nz guest form (`Livewire/Public/ShortenForm`, no slug field)
and `POST /v1/links/public` (public system domains only, `throttle:10,1` per IP + 50/day quota,
SHA-256 IP hash — never raw IPs).

## Updates, deactivation, expiry

- `update(link, data)` — re-validates destinations against the junk detector, trims
  descriptions, normalizes tags, then `forgetCachedSlug`.
- `deactivate(link)` — soft-deactivate (`is_active=false`, analytics preserved) + cache forget.
  `DELETE /v1/links/{link}` maps here (204).
- `links:deactivate-expired` (daily scheduler) — deactivates past-`expires_at` links and
  invalidates their cache entries explicitly.

## Redirect cache

- Hot slugs cached 5 min (`RESOLVE_CACHE_TTL = 300`, key `link:{domain_id}:{slug}`) — hits skip the DB.
- **Misses are never cached** so fresh slugs are visible immediately.
- Writes invalidate via `Link` model events + explicit `forgetCachedSlug` in
  `update` / `deactivate` / `DeactivateExpiredLinks`.
- Stale hits (deactivated/expired while cached) fall through to the DB instead of
  serving the wrong redirect.
