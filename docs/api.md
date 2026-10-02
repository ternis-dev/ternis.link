# API guide

Everything the dashboard does is available over HTTPS at `https://links.t-api.de/v1` — the macOS app and the browser extension are thin clients on this same API. Authenticated endpoints live on that host only; requests on other hosts return `404` before auth runs.

The machine-readable contract is [OpenAPI 3.1](/api-v1-openapi.yaml). This guide is the human version, with copy-paste examples.

## Authentication

Pass a personal API key (starts with `tl_`, created under [API keys](https://dash.ternis.link/api-keys) in the dashboard) or a Ternis Auth SSO access token as a Bearer token:

```bash
curl https://links.t-api.de/v1/links \
  -H "Authorization: Bearer tl_your_key_here"
```

Keys are stored as hashes — the full key is shown **once** at creation. Revoke keys you no longer use, via the dashboard or `DELETE /v1/api-keys/{key}` below.

Check compatibility first: `GET /v1/` is public (no auth) and reports the active version:

```bash
curl https://links.t-api.de/v1/
# {"version":1,"status":"active","latest_version":1,...}
```

## Conventions

- **Versioning:** every response carries `API-Version` and `API-Latest-Version` headers. Deprecated versions add `Deprecation: true` plus `Sunset`; retired versions answer `410` with `{ message, version, latest_version }`.
- **Errors** are `{ "message": "…" }`, or `{ "message": "…", "errors": { … } }` for validation failures. Rate limits answer `429` with a `Retry-After` header — back off and retry.
- **Pagination:** list endpoints return Laravel paginators (`data`, `current_page`, `last_page`, `total`).
- **IDs** are ULID strings. Timestamps are ISO-8601.

## Links

```bash
# List your links (admins see everything; ?scope=mine restricts to own; ?tag= filters)
curl "https://links.t-api.de/v1/links?tag=launch" \
  -H "Authorization: Bearer tl_your_key_here"

# Only links made with one API key (?api_key_id=<ulid>; ?api_key_id=none = dashboard-created)
curl "https://links.t-api.de/v1/links?api_key_id=<key-ulid>" \
  -H "Authorization: Bearer tl_your_key_here"

# Create (custom slug optional; plan minimum length applies; domains must be verified)
curl -X POST https://links.t-api.de/v1/links \
  -H "Authorization: Bearer tl_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{"destination_url": "https://example.com/very-long-page", "domain_id": "<ulid>", "slug": "my-launch", "tags": ["launch"], "og_title": "Launch day", "og_description": "Our new thing", "og_image_url": "https://example.com/og.png"}'
```

Every link created with a personal key stores that key (`api_key_id`, exposed as `api_key` with name/prefix on responses) and logs it in the activity history (`link.created` with `api_key_id`, `api_key_name`, `api_key_prefix`, `auth_via`). SSO-token calls leave `api_key_id` empty. Filter the dashboard list by origin (All origins / Dashboard only / one key) or open a key's dedicated page under API keys.

```bash
# Show / update / deactivate (deleting stops resolution; stats stay)
curl https://links.t-api.de/v1/links/<ulid> -H "Authorization: Bearer tl_your_key_here"
curl -X PUT https://links.t-api.de/v1/links/<ulid> \
  -H "Authorization: Bearer tl_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{"description": "Launch page", "og_title": "Launch day"}'
curl -X DELETE https://links.t-api.de/v1/links/<ulid> -H "Authorization: Bearer tl_your_key_here"
```

Social previews (members only): `og_title` (≤120), `og_description` (≤300), `og_image_url` (https image). Crawlers get a 200 HTML stub with OG tags (`?debug=og` forces it for testing); humans keep the 302 and crawler hits are not counted. Guests get 422 `prohibited` for any `og_*` field.

No account and just scripting something quick? `POST /v1/links/public` creates guest links without any key — auto-generated slugs, fair-use limits, public system domains only:

```bash
curl -X POST https://links.t-api.de/v1/links/public \
  -H "Content-Type: application/json" \
  -d '{"destination_url": "https://example.com/quick-share"}'
```

## Click analytics

```bash
# Raw click rows (paginated)
curl https://links.t-api.de/v1/links/<ulid>/clicks -H "Authorization: Bearer tl_your_key_here"

# Aggregates: totals, unique visitors, top referrers/countries, per-day counts
curl https://links.t-api.de/v1/links/<ulid>/clicks/summary -H "Authorization: Bearer tl_your_key_here"
```

These are the same numbers the dashboard charts are drawn from.

## QR codes

```bash
# Any public URL, no auth (SVG default, ?format=png for PNG)
curl "https://links.t-api.de/v1/qr?url=https%3A%2F%2Fexample.com&format=png"

# QR for one of your links (auth; encodes the short URL)
curl "https://links.t-api.de/v1/links/<ulid>/qr?format=png" \
  -H "Authorization: Bearer tl_your_key_here"
```

## Domains

```bash
# Active system domains plus your own
curl https://links.t-api.de/v1/domains -H "Authorization: Bearer tl_your_key_here"

# Register a custom hostname (eligible plans only; starts unverified)
curl -X POST https://links.t-api.de/v1/domains \
  -H "Authorization: Bearer tl_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{"hostname": "links.example.com"}'
# → 201 with DNS TXT verification instructions until verified

# Publish the TXT record, then verify (200 {verified:true} or 422 {verified:false})
curl -X POST https://links.t-api.de/v1/domains/<ulid>/verify \
  -H "Authorization: Bearer tl_your_key_here"

# Deactivate your domain (links and analytics are preserved)
curl -X DELETE https://links.t-api.de/v1/domains/<ulid> -H "Authorization: Bearer tl_your_key_here"
```

## API keys

```bash
# List your keys (newest first; digests are never exposed)
curl https://links.t-api.de/v1/api-keys -H "Authorization: Bearer tl_your_key_here"

# Create — the raw token comes back as api_key exactly once
# show_on_dashboard=false hides the key's links from the main
# dashboard list (they stay on the key's own page)
curl -X POST https://links.t-api.de/v1/api-keys \
  -H "Authorization: Bearer tl_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{"name": "ci-runner", "show_on_dashboard": false}'

# Show a key (prefix + metadata, digests never exposed)
curl https://links.t-api.de/v1/api-keys/<ulid> -H "Authorization: Bearer tl_your_key_here"

# Rename or toggle dashboard visibility (nothing is moved or deleted)
curl -X PATCH https://links.t-api.de/v1/api-keys/<ulid> \
  -H "Authorization: Bearer tl_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{"show_on_dashboard": true}'

# Revoke
curl -X DELETE https://links.t-api.de/v1/api-keys/<ulid> -H "Authorization: Bearer tl_your_key_here"
```

## Request logging

Every `/v1/*` request (public or authenticated, success or error) writes one row to the API request log: method, host, path (no query string), status, duration, IP hash, truncated user-agent, plus user and key IDs when authenticated. Bodies, tokens and raw IPs are never stored; rows are kept indefinitely and are not deleted on request (see Privacy Policy).

## Notifications

Your in-app inbox (security events, moderation decisions), newest first:

```bash
curl https://links.t-api.de/v1/notifications -H "Authorization: Bearer tl_your_key_here"

# Mark one read, or everything at once
curl -X POST https://links.t-api.de/v1/notifications/<id>/read \
  -H "Authorization: Bearer tl_your_key_here"
curl -X POST https://links.t-api.de/v1/notifications/read \
  -H "Authorization: Bearer tl_your_key_here"
```

## Activity

Your personal history — actions you performed plus actions others (admins, system) performed on your stuff:

```bash
curl https://links.t-api.de/v1/activity -H "Authorization: Bearer tl_your_key_here"
```

## Settings

Theme/layout plus email notification preferences (same rules as the dashboard settings form):

```bash
curl https://links.t-api.de/v1/settings -H "Authorization: Bearer tl_your_key_here"

curl -X PATCH https://links.t-api.de/v1/settings \
  -H "Authorization: Bearer tl_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{"theme": "dark", "nav_layout": "top", "notify_security_email": true}'
```

## Bio pages

Link-in-bio pages on your own verified domain (eligible plans only; system domains never host bio in v1). One domain hosts one root page at `/`, plus sub-pages at `/{sub}` (first-write-wins against short-link slugs both ways).

```bash
# Create a root page on your domain
curl -X POST https://links.t-api.de/v1/bio-pages \
  -H "Authorization: Bearer tl_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{"domain_id": "<ulid>", "title": "My links", "bio": "All my things", "theme": "minimal"}'

# Add a sub-page, then set buttons (full replace, max 25)
curl -X POST https://links.t-api.de/v1/bio-pages \
  -H "Authorization: Bearer tl_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{"domain_id": "<ulid>", "parent_id": "<page-ulid>", "slug": "socials", "title": "Socials"}'

curl -X PUT https://links.t-api.de/v1/bio-pages/<ulid>/buttons \
  -H "Authorization: Bearer tl_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{"buttons": [{"label": "Shop", "kind": "link", "destination_url": "https://example.com/shop"}]}'

# Add a contact card + duplicate the page
curl -X PUT https://links.t-api.de/v1/bio-pages/<ulid>/buttons \
  -H "Authorization: Bearer tl_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{"buttons": [{"label": "Jane Doe", "kind": "contact", "contact_email": "jane@example.com"}]}'

curl -X POST https://links.t-api.de/v1/bio-pages/<ulid>/duplicate \
  -H "Authorization: Bearer tl_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{"domain_id": "<other-ulid>"}'

# Quote, coupon, video, image, countdown, RSVP, location, audio blocks
curl -X PUT https://links.t-api.de/v1/bio-pages/<ulid>/buttons \
  -H "Authorization: Bearer tl_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{"buttons": [{"label": "Ship fast", "sublabel": "A founder", "kind": "quote"}, {"label": "Deal", "sublabel": "SHIP20", "kind": "coupon"}, {"label": "Talk", "kind": "video", "destination_url": "https://www.youtube.com/watch?v=…"}, {"label": "Party", "sublabel": "Aug 1", "kind": "rsvp"}, {"label": "Studio", "sublabel": "123 Main St", "kind": "location"}, {"label": "Ep 1", "kind": "audio", "destination_url": "https://example.com/ep1.mp3"}]}'

# Starter templates (creator, business, event)
curl -X POST https://links.t-api.de/v1/bio-pages/from-template \
  -H "Authorization: Bearer tl_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{"domain_id": "<ulid>", "template": "event"}'

# Per-button analytics + CSV export
curl "https://links.t-api.de/v1/bio-pages/<ulid>/stats?days=30" -H "Authorization: Bearer tl_your_key_here"
curl "https://links.t-api.de/v1/bio-pages/<ulid>/events/export" -H "Authorization: Bearer tl_your_key_here"
```

Button taps redirect via `/t/{button}` and count separately from page views (`ctr = taps/views`, plus `unique_visitors`); crawler renders are not counted. Pages support cover banners, custom footers, announcement bars, thumbnails, social icon rows, button styles, list/grid layouts, countdowns, image blocks, vCard contacts, schedule windows, and future `published_at`; expiring pages stop resolving. Branding removal is an eligible-plans perk. Buttons support schedule windows (`starts_at`/`ends_at`), pause toggles, and pages support future `published_at` (hidden until then).

Button actions: `url` opens `destination_url`, `subpage` links to another page in the same bio family (`target_page_id`, resolved through `/t/{button}` so taps still count), `modal` opens a pop-up (`modal_title`/`modal_body`/`modal_image_url`, opens tracked via `/t/{button}/open.gif`). Button edits preserve ids and tap counts.

Unpublished work can be previewed in-action: mint a 30-minute signed link (dashboard “Preview draft link”, `GET /draft/{page}` on the page's own domain) — no login needed, never tracked or indexed.

Pages carry `locale` (`en/de/fr/es/it`, rendered as `<html lang>`), `theme_color` (browser bar), `button_style` (`filled/outline/soft`), and full OG meta tags automatically. Pages can be password-protected (`password`, min 8 chars, bcrypt-hashed; `remove_password` clears) — locked pages show an interstitial, leak no destinations, count nothing, and unlock attempts are throttled. Image uploads via static.re — soon; paste image URLs for now (avatar, thumbnails, modal images).
