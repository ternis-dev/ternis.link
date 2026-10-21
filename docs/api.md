# API guide

Create and manage short links with the ternis.link HTTP API. This page is the canonical, standalone guide for LLMs and integration developers.

- Recommended base URL: `https://links.t-api.de/v2`
- Authentication: `Authorization: Bearer tl_YOUR_API_KEY`
- Request and response format: JSON. Send `Content-Type: application/json` with JSON bodies; `Accept: application/json` is recommended.
- OpenAPI 3.1 contract: https://docs.ternis.link/api-v2-openapi.yaml
- Supported legacy API: `https://links.t-api.de/v1`; full reference: https://docs.ternis.link/api-v1.md; contract: https://docs.ternis.link/api-v1-openapi.yaml
- Obtain a key at https://dash.ternis.link/api-keys. The raw key is shown once. Existing v1 keys also work with v2.

## Authentication and daily limits: read this first

**An API key identifies your account. It does not upgrade your plan or remove its quota.** The free plan allows 50 link creations per calendar day, shared across the dashboard, v1, v2, and every key belonging to that account. Creating or switching keys does not reset the quota. Deactivating a link does not refund a creation. Unlimited plans report `limit: null` and `remaining: null`.

Use `GET /v2/me` to verify the authenticated account, key, plan, usage, and exact reset timestamp. The day resets at midnight in the application's configured timezone; use `resets_at` with its timezone offset instead of assuming your local midnight. A daily-limit error on an authenticated endpoint does **not** mean the key was ignored.

Send the key as a Bearer token, including the `Bearer ` prefix. Do not send it as `api_key` in a JSON body, query string, or `X-API-Key` header. Missing, malformed, expired, or revoked credentials return HTTP 401; they never fall back to guest creation on v2. SSO access tokens are also accepted after the account has logged in through the dashboard.

Cross-origin clients (a web app on another origin) cannot read the dashboard session cookie. They use the [auth API](./auth-api.md) of the public dashboard instead: it reports whether the visitor is signed in and mints a short-lived `ts_…` session token, which is accepted as a Bearer token just like an API key.

V2 has no guest creation endpoint. For anonymous creation only, use `POST https://links.t-api.de/v1/links/public` without Authorization. That endpoint applies a separate guest quota per IP and restricted guest fields. If you supply Authorization there, credentials are validated and the authenticated creation rules apply.

## Minimal workflow

1. Call `GET /v2/me` with the key. Check `data.daily_link_quota.remaining`; null means unlimited.
2. Optionally call `GET /v2/domains`. It returns usable domains as `{id, hostname}` objects. Reuse an actual ID; do not invent a domain ID.
3. Call `POST /v2/links` with `destination_url`. Omit `domain_id` to use the account's default; if no usable default exists, choose one from `/domains`.
4. Read `data.id` for later API calls and `data.short_url` for sharing. A link's ULID ID and its public slug are different values.

```bash
# Set API_KEY to your key locally. Keep it secret.
curl --fail-with-body https://links.t-api.de/v2/me \
  -H "Authorization: Bearer $API_KEY" -H 'Accept: application/json'

curl --fail-with-body https://links.t-api.de/v2/domains \
  -H "Authorization: Bearer $API_KEY"

curl --fail-with-body https://links.t-api.de/v2/links \
  -H "Authorization: Bearer $API_KEY" \
  -H 'Content-Type: application/json' \
  -d '{"destination_url":"https://example.com/launch"}'
```

Illustrative creation response (IDs and timestamps vary; additional link fields may be present):

```json
{
  "data": {
    "id": "01K80000000000000000000000",
    "slug": "launch2026",
    "destination_url": "https://example.com/launch",
    "short_url": "https://href.nz/launch2026",
    "is_active": true,
    "domain": {"id": "01K80000000000000000000001", "hostname": "href.nz"}
  },
  "error": null,
  "meta": {"api_version": 2, "request_id": "9dd708b2-07c4-4277-a9a3-459ea573e2d3"}
}
```

## V2 endpoint reference

All paths below are relative to `https://links.t-api.de`. Only `GET /v2/` is public. All other listed endpoints require Bearer authentication.

| Method | Path | Purpose | Success |
| --- | --- | --- | --- |
| GET | `/v2/` | Version, documentation URLs, endpoint discovery | 200 |
| GET | `/v2/me` | Account ID, authenticated key, default domain, quota and rate limits | 200 |
| GET | `/v2/domains` | Usable domains for this account, unpaginated | 200 |
| GET | `/v2/links` | List links, newest first, 25 per page | 200 |
| POST | `/v2/links` | Create an owned short link | 201 |
| GET | `/v2/links/{link}` | Retrieve link by ULID ID | 200 |
| PATCH or PUT | `/v2/links/{link}` | Update supplied fields | 200 |
| DELETE | `/v2/links/{link}` | Deactivate; preserve record and analytics | 200 |
| GET | `/v2/links/{link}/clicks` | Paginated click records | 200 |
| GET | `/v2/links/{link}/clicks/summary` | Aggregated click statistics | 200 |

Deletion responds with `{"data":null,"error":null,"meta":{...}}`. It stops redirects; it does not permanently erase the link. Link reads, updates, deletes, and analytics require ownership or an admin account. Removed links return 404. Administrators list all links by default; use `scope=mine` when listing only the authenticated account's links.

### Create fields

Unknown fields are not persisted. Send the documented names exactly; there are no `url` or `custom_alias` aliases.

| Field | Type | Rules |
| --- | --- | --- |
| `destination_url` | string, required | Absolute URL, maximum 2048 characters |
| `domain_id` | string | ULID of an available active, verified domain; defaults to account preference |
| `slug` | string or null | Optional custom path; letters, digits, `_`, `-`; maximum 255; plan minimum length; unique per domain |
| `slug_length` | integer or null | Generated slug length for eligible plans/admins only; ignored for free users and when a custom slug is supplied |
| `description` | string or null | Maximum 500 characters |
| `expires_at` | string or null | Future timestamp; use ISO-8601 with timezone |
| `tags` | array of strings or null | Maximum 10; each 2–30 lowercase letters/digits/dashes, beginning and ending with a letter or digit |
| `og_title` | string or null | Social preview title, maximum 120 characters |
| `og_description` | string or null | Social preview description, maximum 300 characters |
| `og_image_url` | string or null | HTTPS image URL, maximum 2048 characters |
| `password` | string or null | 8–72 characters; stored as a hash, never returned |
| `utm_source`, `utm_medium`, `utm_campaign` | string or null | Maximum 100 each; letters/digits/spaces/dots/dashes/underscores |
| `user_tracking_enabled` | boolean or null | Default false; opt in to subscriber analytics |

Omit `slug` for automatic generation. Free-plan generated slugs have 5–9 characters. For eligible plans the selected length is bounded by the plan minimum (at least 3) and maximum (normally 64); administrators may choose 3–64.

### Update fields

PATCH and PUT both update supplied fields; neither replaces the entire resource. Mutable fields are `destination_url`, `description`, `expires_at`, `tags`, `og_title`, `og_description`, `og_image_url`, `password`, the three UTM fields, and `user_tracking_enabled`, with the same validation as creation. Also supported: `is_active` (boolean), `remove_password` (boolean), and `targets` (up to 20 targeting rules; see the legacy contract for nested rule fields). Slug and domain cannot be changed through this API. Clearing semantics vary by field; use `remove_password: true` to remove a password.

```bash
curl --fail-with-body -X PATCH "https://links.t-api.de/v2/links/$LINK_ID" \
  -H "Authorization: Bearer $API_KEY" -H 'Content-Type: application/json' \
  -d '{"description":"Launch campaign","tags":["launch"],"is_active":true}'
```

### List filters and pagination

Links: `page` (default 1), `scope=mine` for admins, `tag` (exact stored tag), `api_key_id` (key ULID, or `none` for links without a personal key, including SSO/dashboard creation), `user_tracking_enabled=1` or `0`. Keys attribute link origin; they do not isolate ownership. An account can access links created by its other keys.

Clicks: `page`, `per_page` (default 50, clamped to 1–100), `tag` (dynamic click tag), `user_identifier`, `has_user=1|0`, `has_params=1|0`, `from`, `to`. Summary accepts the same filters except pagination. Use ISO-8601 timestamps for date boundaries; `to` is inclusive, so a date-only value denotes midnight rather than the end of that day.

Paginated responses put the array in `data` and navigation in `meta.pagination`:

```json
{"data":[],"error":null,"meta":{"api_version":2,"request_id":"...","pagination":{"current_page":1,"last_page":1,"per_page":25,"total":0,"next_page":null}}}
```

Request `?page=<next_page>` with the same filters until `next_page` is null. `/domains` returns a plain array in `data` with no pagination metadata.

## Responses, errors, and retry decisions

Every v2 JSON response uses `{data, error, meta}`. Success has `error: null`; failure has `data: null`. `meta.request_id` matches `X-Request-Id`. IDs are ULID strings. Datetimes use ISO-8601. Version lifecycle headers are `API-Version`, `API-Latest-Version`, and, when applicable, `Deprecation` and `Sunset`.

```json
{
  "data": null,
  "error": {
    "code": "daily_link_limit_reached",
    "message": "Daily link limit reached (50 per day on the free plan). This quota is shared across your account, including all API keys. Try again after the reset timestamp or change your plan.",
    "fields": {"destination_url":["Daily link limit reached..."]},
    "retry_after_seconds": 3600,
    "quota": {"scope":"account","plan":"free","limit":50,"used":50,"remaining":0,"resets_at":"2026-10-21T00:00:00+00:00"}
  },
  "meta": {"api_version":2,"request_id":"..."}
}
```

| HTTP | `error.code` | What the client should do |
| --- | --- | --- |
| 401 | `authentication_required` | Check Bearer syntax, key validity, or SSO login; do not retry unchanged |
| 403 | `forbidden` | Use a resource the account owns or a permitted domain |
| 404 | `not_found` | Check host, endpoint, and resource ID |
| 405 | `method_not_allowed` | Use a documented HTTP method |
| 410 | `api_version_retired` | Move to the latest active version |
| 422 | `validation_failed` | Correct fields listed in `error.fields` before retrying |
| 429 | `daily_link_limit_reached` | Wait until `error.quota.resets_at` / `Retry-After`, or change plan; switching keys will not help |
| 429 | `rate_limited` | Wait at least `Retry-After` seconds, then retry with backoff |
| 5xx | `internal_error` | Back off; record the request ID for diagnosis |

`fields` is always an object; `quota` is null except for daily account limits. `retry_after_seconds` is null when no numeric Retry-After header exists. The API permits 60 requests/minute per account plus the plan's shared link-creation rate limit; discovery is limited per IP. Daily quota and minute throttles are separate limits.

POST creation has no idempotency-key support. After a network timeout or ambiguous server failure, inspect recent links before repeating a creation: the original request may already have succeeded. Never retry validation/authentication errors in a loop. Never log or expose API keys.

## V1 compatibility and additional features

V1 stays supported with its original response shapes: resource objects or Laravel paginators; errors `{message}` or `{message, errors}`; deletion 204. Its daily account quota error remains 422 for compatibility. V2 daily quota errors are 429 with structured reset metadata. Both versions share authentication, ownership, storage, and plan limits. No new key or link migration is required.

V2 currently covers link management, usable domains, and click analytics. Use v1 for QR generation (`/v1/qr`, `/v1/links/{link}/qr`), custom-domain registration/verification, API-key management (`/v1/api-keys`), notifications (`/v1/notifications`), activity, settings, account privacy operations, async bulk operations, and bio pages. Do not assume these paths exist under `/v2`.

The complete v1 endpoint examples, QR styling, dynamic tracking/privacy behavior, and bio-page workflows are available at https://docs.ternis.link/api-v1.md. The detailed machine contract is https://docs.ternis.link/api-v1-openapi.yaml.

## Request logging

Both versions log request method, host, path without query string, status, duration, IP hash, truncated user agent, and authenticated user/key IDs. Request bodies and Bearer tokens are not stored in these API request logs. See the service privacy policy for retention and analytics details.
