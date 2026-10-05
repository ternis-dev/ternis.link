---
title: my.href.nz — Public shortener links get their own dashboard
date: 2026-10-05
description: New host my.href.nz (my.href.yt redirects to it) with overview, links, analytics, CSV export, and QR for href.nz, meinlink.at, and href.yt links; dash.ternis.link keeps everything else.
---

Links on the public shorteners moved into a dashboard of their own: **my.href.nz** is live as the authenticated home for `href.nz`, `meinlink.at`, and `href.yt` (plus `qr.href.nz`) links.

What shipped:

- **Overview + link management** — stats (total links/clicks, this month, today), scoped link table, create/edit pages, and per-link analytics, all filtered to public-shortener hostnames.
- **CSV export + QR downloads** — full catalog export and per-link click export, plus print-ready QR PNGs, scoped the same way.
- **Strict hostname partition** — a link lives in exactly one dashboard: public links 404 on `dash.ternis.link`, and `clicked.at`, `ternis.link`, `href.re`, partner, and custom-domain links 404 on `my.href.nz`.
- **Same-host SSO** — guests bounce to the same host's `/login`, and post-login landing resolves per host, so each dashboard's session stays where its flow started. `my.href.yt` 301s to the canonical `my.href.nz`.
- **Scoped creation** — the link form's domain picker and server-side validation enforce the split in both directions.
- **Account pages stay single-homed** — API keys, domains, bio pages, notifications, activity, and settings remain on `dash.ternis.link` only, cross-linked from the public sidebar; per-key link pages stay global.
- **Landing cross-links** — the `href.nz`, `meinlink.at`, and `href.yt` landings point signed-in users at `my.href.nz`.

Coverage: new `PublicDashboardTest` (partition, alias redirect, export/QR scoping); twelve existing suites moved dash-visibility fixtures to `clicked.at`.
