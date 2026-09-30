---
title: Short links catalog export, API key management, and destination safety
date: 2026-09-30
description: Export your full links inventory to CSV, manage API keys with inline renaming and dedicated endpoints, and stay protected with SSRF destination validation on updates.
---

Today's update brings expanded data portability, more flexible API key management, and hardened security for short link destinations:

- **Links catalog CSV export**: Members can now download their entire short link inventory as a streamed CSV file directly from the dashboard (under Your Links) or filtered by integration on any API key's dedicated detail page. The export includes slug, full short URL, destination URL, domain, creator key, click count, status, tags, description, and timestamps.
- **API key show endpoint (`GET /v1/api-keys/{id}`)**: Added dedicated endpoint to inspect individual API keys and their metadata via `links.t-api.de/v1`, synchronized with our OpenAPI 3.1 contract and machine-readable site files.
- **Inline API key renaming**: Added in-place label editing on the dashboard API keys table so you can rename keys immediately without recreating them or using the CLI.
- **Destination update safety**: Enforced structural SSRF protection (`UnsafeUrlValidator`) on link updates across both dashboard and API channels, ensuring destination URLs cannot target internal networks, non-public IP literals, or unsupported schemes.
- **Dashboard filter UX**: Improved empty state handling on filtered link tables with a one-click action to clear active search and tag filters.
