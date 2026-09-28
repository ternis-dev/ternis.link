# Browser Extension

Shorten any tab without leaving it: toolbar popup, right-click menu, or omnibox.

Download it at [ternis.link/pages/extension](https://ternis.link/pages/extension)
(Chrome Web Store listing is coming — until then it installs as an unpacked
zip in Developer Mode). The source lives in `extension/` in the repo
(vanilla Manifest V3, no build step); `manifest.json` is the version source of
truth and `php artisan extension:build` packages the download zip.

## Modes

- **Guest (no key):** `POST /v1/links/public` → `href.nz`, auto-generated
  8-char codes, 50/day per IP. No custom slugs.
- **Key (`tl_…` from dash.ternis.link/api-keys):** `POST /v1/links` with
  `destination_url` + `domain_id` (+ optional `slug`). Domain picker is fed by
  `GET /v1/domains`; custom slugs follow the usual plan rules
  (`[a-zA-Z0-9_-]`, plan minimum length, per-domain uniqueness).

## Endpoints used

- `POST /v1/links/public` — guest shorten (see [Links](./links)).
- `POST /v1/links` — authenticated shorten, `Authorization: Bearer <key>`.
- `GET /v1/domains` — domain picker (active system + own domains).
- `GET /v1/qr?url=…` — QR preview (public, no auth).

Auth failures surface as 401 (bad key), quota/validation as 422, plan
per-minute overages as 429 with `Retry-After` — same shapes as the
[API contract](./api-v1-openapi.yaml).

## Privacy

The key is stored only in `chrome.storage.sync` and sent only to the
configured API base (default `https://links.t-api.de/v1`). History (last 10)
stays in `chrome.storage.local`. Revoke keys anytime at
[dash.ternis.link/api-keys](https://dash.ternis.link/api-keys).
