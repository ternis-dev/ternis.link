---
title: Browser extension Manage button follows the dashboard split
date: 2026-10-05
description: Extension result row now links key-mode links to their owning dashboard (my.href.nz for public hosts, dash.ternis.link otherwise) and hides the button for guest links.
---

The browser extension's result row pointed every created link at `dash.ternis.link` — wrong for `href.nz`, `meinlink.at`, and `href.yt` links, which live on `my.href.nz`.

What changed:

- After shortening with an API key, the **Manage →** button deep-links to the created link's analytics page on whichever dashboard owns its domain (`my.href.nz/links/{id}` for public hosts, `dash.ternis.link/links/{id}` otherwise).
- Guest-mode links belong to no account, so the button stays hidden instead of pointing at a dashboard that can't show the link.
