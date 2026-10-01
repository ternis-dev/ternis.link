# ternis.link — Social Preview Plan

> Date: 2026-10-01
> Status: DRAFT — for review before implementation
> Scope: per-link Open Graph overrides + crawler-aware redirect + preview sandbox

## 1. Mission

Shared short links look broken in Slack/X/WhatsApp today: crawlers follow the 302 to `destination_url` and pick whatever tags live there. Let owners set `og_title`, `og_description`, `og_image_url` per link so shares render predictably, with safe fallbacks when unset.

## 2. Goals / Non-Goals

Goals:
- Per-link `og_title (≤120)`, `og_description (≤300)`, `og_image_url (https, ≤5MB, jpg/png/webp/gif)`.
- Crawler requests get a fast HTML stub with `<meta property="og:*">` + canonical + refresh/JS redirect; humans keep the 302.
- Preview sandbox (`PreviewController::show`) renders the effective OG card.
- API + dashboard + validation + cache-coherent.

Non-goals (v1):
- No image upload/hosting — URL only (validate reachable, content-type, size via HEAD).
- No auto-scraping destination OG tags (spoofing/SSRF risk); explicit fallback chain only.
- No per-domain default OG templates (later).

## 3. Schema

Migration `add_social_preview_to_links_table`:
- `og_title varchar(120) nullable`
- `og_description varchar(300) nullable`
- `og_image_url varchar(2048) nullable`
- Index: none needed (read via slug PK path).

Validation (shared helper `SocialPreviewValidator`):
- `og_title`: nullable|string|max:120, strip tags, trim.
- `og_description`: nullable|string|max:300.
- `og_image_url`: nullable|url|starts_with:https|max:2048; HEAD-check content-type `image/{jpeg,png,webp,gif}` and `content-length ≤5MB`; reject intranet IPs via existing `UnsafeUrlValidator`.
- Guests: all three forbidden (422 `prohibited`) — same rule as custom slugs.

## 4. API Surface (`links.t-api.de/v1`)

- `POST /v1/links`: accept `og_title, og_description, og_image_url` (same rules).
- `PUT /v1/links/{link}`: same three, nullable to clear.
- Responses include the three fields + computed `og_effective {title,description,image}`.
- OpenAPI `docs/api-v1-openapi.yaml`: extend `Link` schema; `docs/api.md` + `docs/links.md` examples.

## 5. Redirect Behavior

New `CrawlerDetector` service (UA regex: `Slackbot|Twitterbot|facebookexternalhit|LinkedInBot|WhatsApp|TelegramBot|Discordbot|Embedly|Quora|Pinterest` + `?_escaped_fragment_`):
- `RedirectController::resolve`: if crawler AND link has any OG field → return `view('redirect.preview-stub', 200)` with:
  ```html
  <meta property="og:title"> <meta property="og:description">
  <meta property="og:image"> <link rel="canonical" href="destination">
  <meta http-equiv="refresh" content="0;url=destination"> + <noscript><a>
  ```
  `Cache-Control: public, max-age=300`, `Vary: User-Agent`.
- Else: existing 302 + `ClickTrackerService::track` (do NOT count crawler hits as clicks — skip `track()` when crawler stub served).
- `LinkService::resolveSlug` cache must include OG columns (already caches full row minus IP fields — no change, just ensure new columns survive `Arr::except`).

## 6. Dashboard / Livewire

- `LinkForm` + link edit form: collapsed "Social preview" fieldset: title/description/image inputs, live card preview (server-rendered Blade partial), HEAD-check status line, char counters.
- `Link detail` page: OG card section + "Copy debug URL" (`/{slug}?debug=og` forces stub for testing).
- Validation messages mirror API.

## 7. Preview Sandbox

- `PreviewController::show` + `resources/views/preview/show.blade.php`: show effective OG (owner values → destination host fallback → short URL fallback), image dimensions if fetchable, warning when image fails HEAD.

## 8. Security / Privacy

- Image URL runs through `UnsafeUrlValidator::rejectIfUnsafe` (no intranet/metadata IPs).
- Escape all OG output (`{{ }}`); image URL validated scheme=https only.
- No fetching of destination HTML in request path (keeps redirect <50ms).

## 9. Tests

- `SocialPreviewValidationTest`: max-lengths, http-rejected, intranet-rejected, guest-prohibited.
- `CrawlerStubTest`: crawler UA gets 200 stub with tags; human UA gets 302; crawler hit does not increment `click_count`.
- `LinkApiTest` additions: create/update/clear OG fields; `PreviewTest`: effective card.
- `RedirectCacheTest`: OG update invalidates stub.

## 10. Milestones

- M1: migration + validator + `LinkService::create/update` + API requests + OpenAPI/docs.
- M2: `CrawlerDetector` + stub view + `?debug=og` + click-skip.
- M3: Livewire forms + preview sandbox + tests green (`php artisan test`).

## 11. Risks

- Crawler UA list drift → keep regex in config, easy to extend.
- Oversized/slow image HEAD → 3s timeout, fail-open (accept URL, warn in UI).
