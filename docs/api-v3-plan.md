# API v3 contract and rollout plan

Status: proposal, 2026-11-10. This document plans changes; it does not change live API behavior or grant new privileges. V3 already exists in `routes/api/v3.php`; evolve that implementation rather than introducing a second v3. Breaking changes require a new version if v3 already has external consumers.

## Findings in the current implementation

- V2 `/me` exposes `default_domain_id` and plan-derived quota/throttle settings. Its request throttle value is nominal rather than a live counter.
- V3 already accepts `url` and hostname `domain` for link creation and filters; its `/me` returns nested domain objects, but omits effective limits. API keys still expose database IDs for domain preferences in inherited management code.
- `DailyLinkQuota::forUser` counts today's account links. `LinkService::ensureWithinDailyQuota` and `ensureWithinRateLimit` have no admin exemption. An admin on the free plan can therefore have a 50-link daily quota and 10 creations/minute. Admin permissions and billing plan are distinct.
- `AppServiceProvider` applies 60 API requests/minute per authenticated account, with an `AllowedClient` exemption. API keys belonging to the same account share that request bucket. Creation throttles are a separate account bucket.
- V3 link collection defaults to `scope=mine`, including for admins. Keep that safe default. Authorization must always use the actual principal and resource permissions.

## Canonical names and domain selection

Use `url`, `domain` (a hostname string), `slug`, `expires_at` (RFC 3339), `tags` (string array), and `description`. Use the same names in input, output, validation errors, examples and filters. Keep `expires_in` as optional documented convenience; reject requests supplying both expiry fields. PATCH changes supplied fields only; null clears nullable fields. Reject conflicting aliases instead of silently choosing one.

```http
POST /v3/links
Authorization: Bearer tl_…
Content-Type: application/json

{"url":"https://example.com/article","domain":"href.nz","slug":"article"}
```

```json
{"data":{"id":"link_opaque_id","url":"https://example.com/article","domain":"href.nz","slug":"article","short_url":"https://href.nz/article"},"meta":{"request_id":"request_opaque_id"}}
```

Explicit hostname > usable API-key preference > usable account default > usable `href.nz` fallback. Explicit unavailable domains fail with a `domain` error; never silently replace them. Normalize case and IDNA using the existing domain service; accept hostnames only, not arbitrary URLs, paths or ports. Exclude inactive, unverified or unauthorized choices. Report the effective hostname and selection source from the same resolver used by creation.

`GET /domains` returns `data` as an array of `{domain, type, is_default, is_preferred}`. Domain IDs remain internal implementation details. Key create/update accepts `preferred_domain: "href.nz"`; null clears the preference. `GET /links?domain=href.nz` uses exact normalized hostname matching. Retain `domain_id` only as a deprecated compatibility input where already supported, never require it for a new workflow.

## Proposed `/me`

Separate identity, authentication context, defaults, capabilities and effective limits. Expose the account role explicitly; do not infer it from plan or API-key name. Do not include email by default. The following example describes a proposed admin daily-quota exemption with request and creation throttles still enforced, not today's behavior:

```json
{
  "data": {
    "user": {"id":"user_opaque_id","role":"admin","plan":"free"},
    "authentication": {
      "method":"api_key",
      "api_key":{"id":"key_opaque_id","name":"twinsonice-fans","prefix":"tl_qFQpY"}
    },
    "defaults":{"domain":"href.nz","domain_source":"api_key"},
    "capabilities":{"create_links":true,"manage_all_links":true},
    "limits": {
      "daily_links": {
        "enforced":false,"reason":"admin_exemption","scope":"account",
        "limit":null,"used":0,"remaining":null,
        "resets_at":null,"timezone":"UTC"
      },
      "link_creation": {
        "enforced":true,"reason":"plan","scope":"account",
        "limit":10,"window_seconds":60
      },
      "api_requests": {
        "enforced":true,"reason":"default","scope":"account",
        "limit":60,"window_seconds":60
      }
    }
  },
  "meta":{"request_id":"request_opaque_id"}
}
```

`null` limit means unlimited, never zero or a sentinel such as -1. Unknown counters are omitted, not represented as unlimited. `used` can remain useful for exempt daily quotas; `remaining` is null when unenforced. For an enforced daily quota, include its real limit, nonnegative remaining and the next reset instant, calculated in the stated quota timezone. Return `Cache-Control: private, no-store` for identity and quota responses.

Do not fabricate rolling-window `remaining` or `resets_at` from a plan. If included, read them from the exact enforcement bucket. `/me` is a snapshot, not a reservation; another key can consume capacity immediately afterward.

## Effective limits: one policy, several consumers

Introduce `EffectiveApiPolicy` shared by discovery, creation, bulk operations, request middleware and dashboards. Inputs: authenticated account, API-key restrictions, plan, explicit account overrides, and allowed-client context. Outputs: resolved capabilities and effective limits with reasons. Resolve exemptions individually: an exemption from daily quotas must not implicitly exempt request throttles, threat checks or authorization.

Proposed policy decision: exempt administrators from the daily plan quota, but retain creation and API request throttles unless explicitly overridden. This requires product approval before enforcement changes. If retaining plan quotas for admins instead, report `enforced:true, reason:plan`; either choice must match actual enforcement. Do not treat the example response as evidence of an existing exemption.

API keys cannot broaden account privileges. If per-key limits/scopes are introduced, enforce both account and key ceilings; revoking a key immediately revokes its capabilities. Model admin-wide access as an explicit capability and retain `scope=mine` by default. Do not add public admin-key creation that can grant arbitrary bypasses.

Reserve daily capacity atomically across concurrent requests and bulk items. Count only committed successful creations, release failed reservations, and retain a durable daily usage counter so deleting a link cannot restore quota. Specify historical treatment before migrating today's row-count implementation. Rate-limit failed attempts too to prevent abuse. Expose each applicable bucket and identify which one rejected a request.

## HTTP contract

Follow [HTTP semantics (RFC 9110)](https://www.rfc-editor.org/rfc/rfc9110.html) and [Problem Details (RFC 9457)](https://www.rfc-editor.org/rfc/rfc9457.html).

- Success: `{data, meta}`; collections use `data: []` and `meta.pagination` with opaque cursor, bounded `limit` (default 25, maximum 100), and next/previous URLs. Sort by `(created_at, id)` for stable traversal. Keep the existing page interface until a compatibility release is possible.
- Errors: `application/problem+json` with `type`, `title`, `status`, safe `detail`, `instance`, stable `code`, `request_id`, and field errors using the public names. Never nest Problem Details in the success envelope. Authenticate before disclosing resources; return 404 for inaccessible objects.
- 201 plus `Location` for create; 200 for read/update; 204 with no body for delete; 401 with `WWW-Authenticate` for missing/invalid credentials; 403 for permissions; 409 for slug/idempotency conflicts; 422 for validation; 429 for quotas/throttles with `Retry-After` matching actual reset. Preserve headers through response middleware.
- PATCH for partial updates; retain existing PUT as a compatibility alias pending a versioned migration. Document deletion as soft removal, its effect on redirects, and whether repeated deletion returns 204 or 404.
- Add an application-defined `Idempotency-Key` contract for POST link creation/bulk: account + key + route scope, normalized body hash, atomic reservation, 24-hour retention, replay the original status/body/Location, 409 for changed payload. Do not claim POST retries are safe before implementation.
- Bulk: bounded batch (proposed 100), per-item errors and explicit partial success, same validation/policy as single create. Preserve existing 207 behavior until migration; consider 200 with item outcomes for a future contract. Idempotency must prevent duplicate successful items on retry.
- Dates use RFC 3339 with offsets; booleans are booleans; examples must use usable hostnames rather than database lookups. CORS exposes request ID, Location and Retry-After to authorized browser clients. Never log credentials or secret key material.

## Delivery sequence and acceptance checks

1. Inventory v3 consumers and freeze an OpenAPI 3.1 baseline matching current routes, auth methods, middleware errors, bulk and QR binary responses. Add `/me`, domains and preferred-domain examples to the API guide. Confirm admin exemption and timezone decisions before implementing policy changes.
2. Extract the shared domain resolver and effective policy without behavior changes. Add tests for free/paid/admin, session/API-key auth, allowed clients, shared account buckets, disabled/unverified/foreign domains, stale preferences and fallback failure.
3. Add effective limits/capabilities to current `/me` as additive fields. Retain existing domain objects initially; introduce canonical hostname fields without changing an existing field's type. Mark legacy names deprecated and publish a migration table.
4. Implement approved exemptions, atomic daily reservations and idempotency together with concurrency tests. Test boundaries (midnight, DST where applicable, exactly at limit), deleted links, partial bulk failure, transient retries and revocation.
5. Introduce uniform public validation names and Problem Details behind a version boundary or explicit negotiated preview. Test 401/403/404/409/422/429 and 5xx through the complete middleware chain, response headers, no secrets, and correct content types. Generate request/response contract tests from OpenAPI and verify all documentation examples.
6. Canary the revised contract, compare observed policy decisions with reported limits, and monitor error/429 rates and latency. Keep v2 and existing v3 behavior available through the documented migration window. Publish deprecation dates only after consumer inventory; rollback the preview on regressions without disabling existing keys.

Migration examples: `default_domain_id` → `defaults.domain`; `auth_via` → `authentication.method`; `daily_link_quota` → `limits.daily_links`; separate per-minute integers → named limit objects. These are target mappings, not claims about currently deployed payloads.

## Permission-based API keys from v3 onward

Proposal: v1/v2 retain their current authorization behavior. V3 and later require explicit key permissions as well as account/resource authorization. Plan features, administrator role, and key permissions are independent constraints; `unlimited` grants no extra permissions.

Start with a small, stable vocabulary:

| Permission | Routes |
|---|---|
| `links:read` | GET links, link details |
| `links:write` | POST links/bulk, PATCH/PUT links |
| `links:delete` | DELETE links |
| `analytics:read` | link analytics, clicks and click summaries |
| `qr:read` | link QR rendering |
| `domains:read` | available domains |
| `keys:read` | list/details of owned keys |
| `keys:write` | create/update/revoke keys |
| `admin:links` | explicit cross-account link access, also requires admin role |

Authenticated `/me` is always accessible for permission discovery; public service discovery requires no key. `/me` includes granted key permissions and effective permissions separately. A session/SSO credential receives its explicitly defined first-party permissions, never an accidental bypass of the policy middleware. Missing permission produces 403 with stable `insufficient_permission` code and required permission names. Resource ownership still applies even when a key has a matching permission.

New v3 keys start with a selected preset (read-only, link creator, analytics, or custom); show the exact permission list before creation. The default link-creator preset grants `links:read`, `links:write`, `domains:read`, `qr:read`, with no key management, deletion or admin access. No implicit wildcard or inheritance from a newer preset. Empty permissions means discovery only; null must never silently mean unrestricted for new v3 keys.

Persist permissions as a validated JSON string array on keys (with an explicit legacy policy marker for existing keys), audit permission changes, and enforce them on every v3+ route including bulk and inherited click/key controllers. Key management is a separate privilege: a child key's permissions cannot exceed the caller key's effective permissions, including on update. Prevent minting a v1/v2 key via a restricted v3 credential to bypass scopes. Prevent changing API version to escape permissions. Do not allow keys to assign plans, administrator roles or exemptions.

Existing v1/v2 keys remain unchanged on v1/v2 routes. Inventory existing v3 keys and assign a frozen compatibility permission set matching their previous access, rather than silently breaking integrations. Migrating a key from v2 to v3 requires an explicit permission selection; switching back cannot become an escalation path. Publish a reviewed route-to-permission matrix and tests proving no v3+ route is unclassified. Unknown or new routes fail closed until assigned a policy.

Acceptance tests must cover every route with missing/empty/valid permissions, owner versus foreign resources, admin keys without `admin:links`, downgraded roles, revocation, child key escalation, version switching, and limits shared across differently scoped keys. Introduce enforcement only with the compatibility rollout above; this section does not claim permission enforcement is already implemented.

## Unlimited plan

The `unlimited` plan removes daily link quotas and per-minute link creation limits (`null` for both), enables custom domains and slug-length selection, and permits one-character custom slugs. It does not exempt API request throttling, threat checks, authentication or permissions. Existing accounts are not reassigned automatically; administrators can assign it using the existing plan selector. V2 `/me` reports a null creation limit for this plan. Reflect the same semantics in future v3 effective-limit objects with reason `plan_unlimited`.
