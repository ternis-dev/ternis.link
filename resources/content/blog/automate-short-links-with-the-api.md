---
title: Automate short links with the API
date: 2026-09-28
description: From curl to CI — create links, pull analytics, and manage keys programmatically against the versioned REST API.
---

Everything the dashboard does, a script can do. The API lives at `https://links.t-api.de/v1`, speaks JSON, versions in the path, and authenticates with personal keys — the same API the macOS app and the browser extension are built on, so it covers real workflows, not a demo subset.

## First link in thirty seconds

Create a key in the dashboard under API keys (the full `tl_…` token shows exactly once — store it like a password), then:

```bash
curl -X POST https://links.t-api.de/v1/links \
  -H "Authorization: Bearer tl_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{"destination_url": "https://example.com/release-notes", "slug": "v42"}'
```

List with `GET /v1/links`, read analytics with `GET /v1/links/{link}/clicks/summary`, rotate keys with `POST /v1/api-keys` and `DELETE /v1/api-keys/{key}`. The complete reference with copy-paste examples is the [API guide](https://docs.ternis.link/api); the machine-readable contract is OpenAPI 3.1.

## Playing nice with automation

A few conventions keep scripts reliable. Every response carries `API-Version` and `API-Latest-Version` headers — check them on startup and fail loudly on major drift. Rate limits answer `429` with `Retry-After`: back off and retry instead of hammering. Errors are `{ message }` (plus `errors` for validation), so log the message and move on.

Scope keys per use: one named key per script or CI job, revoked when the job dies. Creation and revocation both land in your activity feed and trigger a security notification, so a key minted at 3 a.m. by nobody you know is visible by breakfast. That is the whole security model — boring, explicit, and auditable — which is exactly what you want from infrastructure your deploys depend on.
