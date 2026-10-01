# ternis.link — Targeting & Rotation Plan

> Date: 2026-10-01
> Status: DRAFT — for review before implementation
> Scope: multi-destination links (geo / device / weighted rotation)

## 1. Mission

One slug, many destinations: route by country, device, or weighted A/B without minting `launch-us` / `launch-eu` variants. Analytics stay on the slug but break down per target.

## 2. Goals / Non-Goals

Goals:
- Per-link ordered rule set: `{country_codes[], device[], weight, destination_url, label}`.
- Evaluation order: exact match (country+device) → country → device → weighted rotation → default `links.destination_url`.
- Weighted rotation is sticky-ish (deterministic hash on `ip_hash+day` so a visitor doesn't flap) and counted per target.
- API + dashboard editor + preview `?target=debug` explainer.

Non-goals (v1):
- No time-window scheduling (already have `expires_at`), no IP-range/CIDR rules, no JS-fingerprinting.
- No nested AND/OR builder — flat rules with priority `sort_order` only.
- Max 20 targets per link (abuse/perf cap).

## 3. Schema

New table `link_targets` (ULID PK):
- `id ulid PK`, `link_id ulid FK → links.id cascade`
- `label varchar(60) nullable` (e.g. "US-mobile")
- `destination_url varchar(2048)` (each target validated via `JunkUrlDetector` + `UnsafeUrlValidator`)
- `country_codes json nullable` (list of ISO-3166-1 alpha-2, uppercase, max 50)
- `device varchar(16) nullable` (`desktop|mobile|tablet|bot` — derived from UA, `bot` never served to humans)
- `weight unsigned tiny int default 100` (0 = paused, rotation share = weight/sum)
- `sort_order unsigned tiny int default 0`
- `click_count bigint default 0` (denormalized per-target)
- `is_active boolean default true`
- Indexes: `(link_id, sort_order)`, `(link_id, is_active)`.

`clicks` addition (migration): `link_target_id ulid nullable FK → link_targets.id nullOnDelete` + index `(link_id, link_target_id)`.

## 4. Services

- `DeviceDetector`: `mobile|tablet|desktop` from UA (reuse existing UA parsing; tablets separate from mobile; default desktop).
- `TargetSelector::pick(Link, Request): ?LinkTarget`:
  1. load active targets (`link_id`, ordered by `sort_order`);
  2. filter by `is_active && weight>0 || has geo/device constraint` — pure-weighted pool vs constrained;
  3. score: candidates matching country+device (2pts) > country (1) > device (1) > unconstrained rotation pool;
  4. among ties: weighted pick via `crc32(ip_hash . date('Y-m-d') . link_id) % totalWeight`;
  5. return null → caller uses `links.destination_url`.
- `LinkService`: `syncTargets(Link, array $targets, ?User $actor)` — validate, diff, replace in transaction, `forgetCachedSlug`.
- Cache: `resolveSlug` currently caches `links` row; add second key `link-targets:{link_id}` (5min TTL, invalidated on sync/update/deactivate). Redirect path does 2 cache reads, 0 extra DB on hot slugs.

## 5. API Surface

- `GET /v1/links/{link}` includes `targets[]` + `has_targeting bool`.
- `PUT /v1/links/{link}` accepts `targets[]` (full replace; `[]` clears). Per-item rules: `destination_url required|url|max:2048`, `country_codes nullable|array|max:50|ISO2`, `device nullable|in:...`, `weight 0..10000`, `label max:60`.
- `GET /v1/links/{link}/clicks/summary` adds `by_target [{id,label,clicks,share}]`.
- `RecordClick` job gains `linkTargetId`; `ClickTrackerService::track($link,$request,$target=null)`.
- OpenAPI + `docs/api.md` with curl examples.

## 6. Redirect Behavior

`RedirectController::resolve` after `resolveSlug`:
- `$target = TargetSelector::pick($link,$request)`; `$dest = $target?->destination_url ?? $link->destination_url`.
- `track($link,$request,target:$target)`; `$target?->increment('click_count')` (queue-side to keep redirect fast).
- Crawler stub (social-preview plan) uses default destination, never a rotation arm.
- Direct `/url/*` links never have targets (guard in service).

## 7. Dashboard / Livewire

- Link create/edit: "Destinations" repeater (destination, label, countries multi-select, device select, weight slider, pause toggle), live share % calculator, validation inline.
- Link detail: per-target table with clicks/share + `?target=debug` explainer box (shows matched rule and why).
- Admin overview: badge `×N targets` on links list (no per-target PII).

## 8. Security / Abuse

- Every target URL through junk + unsafe validators (admins keep existing bypass for destination only — targets always checked).
- 20-target cap + 2048-char cap enforced in request + service (double-enforced for API vs form drift).
- Guests cannot create targets (same 422 `prohibited` as custom slugs).

## 9. Tests

- `TargetSelectorTest`: geo/device priority matrix, weight distribution (seeded hash), fallback to default, inactive/zero-weight skipped.
- `LinkTargetsApiTest`: CRUD, cap enforcement, invalid ISO2 rejected, guest prohibited, cache invalidation.
- `RedirectTargetTest`: redirect lands on expected arm, `clicks.link_target_id` set, summary `by_target` shares sum to 100%.
- `DeviceDetectorTest`: mobile/tablet/desktop fixtures.

## 10. Milestones

- M1: migrations + models + `DeviceDetector` + `TargetSelector` + unit tests.
- M2: `LinkService::syncTargets` + API wiring + OpenAPI/docs + `RecordClick` extension.
- M3: Livewire editor + detail breakdown + redirect integration + full suite green.

## 11. Risks

- UA-based device is heuristic → document as best-effort, country (GeoIP) wins ties.
- Cache stampede on viral slug → targets cached separately; sync invalidates both keys atomically.
