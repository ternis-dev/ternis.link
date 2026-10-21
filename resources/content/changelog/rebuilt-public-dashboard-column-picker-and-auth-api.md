---
title: Rebuilt public dashboard, second host, per-table column picker
date: 2026-10-21
description: The public dashboard gets its own app shell and a restructured navigation, my.href.nz serves the same dashboard under its own brand, every table offers per-user column customization, and a CORS-protected auth API lands for cross-origin clients.
---

Highlights:

- **Public dashboard rebuilt**: own app shell (sticky header, host branding, account menu) instead of the generic dashboard chrome; new hero band and panel grid on the overview.
- **Restructured navigation**: API keys, domains and settings are reachable from the public dashboard — the public-links workflow no longer requires a trip to `dash.ternis.link`.
- **`my.href.nz` is a first-class host**: same app, same session, same data, its own branding. Every generated URL stays on the host the visitor is on.
- **Column customizer everywhere**: toggle visibility and reorder columns on the links table, activity log, click analytics, API keys, both domain tables and the admin console. Persisted per account in `users.table_columns`.
- **CORS-protected auth API** for cross-origin clients: `GET /auth/api/session` reports the signed-in visitor, `POST /auth/api/token` mints a short-lived bearer token, `DELETE /auth/api/token` revokes it. Disabled by default; `CORS_ALLOWED_ORIGINS` lists the allowed origins and a request from anywhere else is rejected server-side.
- **Session token hygiene**: at most five live tokens per account (the oldest is revoked when a new one is minted), and `privacy:prune-session-tokens` removes dead rows.
