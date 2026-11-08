---
title: Introducing API v3: Intuitive Shortening and Preferred Domains
date: 2026-11-08
description: API v3 is here — shorten links without setting domain_id, use familiar field names like url, batch process up to 50 links, and fetch vector QR codes on the fly.
---

Building integrations should feel effortless. In API v1 and v2, developers had to query available domains, extract internal ULIDs, and pass verbose payloads. With **API v3**, we have reimagined the developer experience from the ground up: making link shortening more intuitive, resilient, and concise.

## 1. No domain_id Required: Preferred Domains & Fallback

Every API key can now have an assigned **preferred domain** (with automatic fallback to `href.nz`). When shortening a link via `POST /v3/links`, you no longer need to pass `domain_id`:

```json
POST /v3/links
Authorization: Bearer tl_...
Content-Type: application/json

{
  "url": "https://example.com/product-launch"
}
```

The system automatically resolves the target domain according to your preference — whether that is `meinlink.at`, `href.nz`, or your own custom domain. Prefer to specify a domain inline? Simply pass the hostname string directly: `"domain": "meinlink.at"`.

## 2. Intuitive Field Aliases & Shorthands

API v3 adopts natural naming conventions:

- **`url`**: An intuitive alias for `destination_url`.
- **`expires_in`**: Human-friendly duration strings like `"30d"`, `"24h"`, `"7d"`, or `"3600"` seconds instead of having to calculate ISO-8601 timestamps.
- **Nested `utm` Object**: Send `{ "source": "github", "medium": "docs", "campaign": "v3" }` directly in your request payload.
- **Comma-Separated Tags**: Pass `"tags": "api, automation"` or an array.

## 3. High-Velocity Bulk Shortening

Need to shorten campaign links in batches? API v3 introduces `POST /v3/links/bulk`, allowing up to 50 links per single HTTP request:

```json
POST /v3/links/bulk
{
  "links": [
    { "url": "https://example.com/page-1", "slug": "spring-1" },
    { "url": "https://example.com/page-2", "domain": "meinlink.at" }
  ]
}
```

The endpoint returns individual per-link statuses alongside overall batch summary counters.

## 4. Vector QR Codes and Deep Analytics Endpoints

API v3 also exposes first-class endpoints for QR codes and analytics:

- **Dynamic QR Codes (`GET /v3/links/{link}/qr`)**: Request infinite-resolution SVG (`?format=svg`) or raster PNG (`?format=png&size=500`) directly from the API for automated collateral generation.
- **Comprehensive Analytics (`GET /v3/links/{link}/analytics`)**: Query clean, pre-aggregated metrics including 24-hour, 7-day, and 30-day click counts, top referrers, and country breakdowns.

Explore the complete API v3 schema and endpoint discovery at `GET /v3`.
