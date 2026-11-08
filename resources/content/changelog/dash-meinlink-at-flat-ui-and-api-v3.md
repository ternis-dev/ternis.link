---
title: dash.meinlink.at Flat UI & API v3 Launch
date: 2026-11-08
description: Launched bespoke Flat UI design system for dash.meinlink.at, introduced API v3 with intuitive aliases, bulk shortening, and preferred domain resolution.
---

Today we are rolling out a major update featuring a dedicated visual redesign for `dash.meinlink.at` alongside the general release of **API v3**.

### dash.meinlink.at Flat UI & New Tools
- **Distinct Flat Design System**: Rebuilt the Austrian shortener dashboard with crisp 1.5px solid borders, zero drop shadows, Austrian red palette, and pure SVGs.
- **Inline Quick-Shortener**: Create links immediately from the dashboard overview without opening forms.
- **Vector SVG QR Codes**: Download vector SVG QR codes directly from link detail pages.
- **CSV Link Export**: Export full link catalog with targets and metrics.
- **Campaign / UTM Builder**: Build and save tracking parameters directly into short links.

### API v3
- **Preferred Domain Resolution**: API keys now support assigned preferred domains with automatic fallback to `href.nz`.
- **Intuitive Aliases**: Support for `url`, hostname `domain`, human duration `expires_in` (`7d`, `24h`), and nested `utm`.
- **Bulk Creation**: `POST /v3/links/bulk` for batch shortening up to 50 links per request.
- **Direct QR Endpoint**: `GET /v3/links/{link}/qr` supporting vector SVG and PNG downloads.
- **Rich Analytics**: `GET /v3/links/{link}/analytics` for pre-aggregated stats.
- **Extension Sync**: Chrome extension now auto-syncs with API key preferred domains.
