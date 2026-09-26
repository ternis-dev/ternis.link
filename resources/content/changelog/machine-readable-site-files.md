---
title: Real robots.txt, sitemap.xml, llms.txt — plus Markdown twins for every page
date: 2026-09-26
description: Crawler and agent files are now dynamic routes, and every /pages/* page has a text/markdown twin.
---

Four new machine-readable endpoints, all served as real routes (correct per host) instead of static files:

- **`/robots.txt`** — curated crawl policy per host. Short-link hosts allow `/` and `/pages/` and disallow auth, redirect endpoints, and the infinite `/{slug}` space; dashboard, admin, and API hosts disallow everything.
- **`/sitemap.xml`** — same-host app pages only. User short links never enter a sitemap: unbounded user content would waste crawl budget and leak slugs.
- **`/llms.txt`** — service summary plus an inventory of every public page, following the llmstxt.org shape.
- **`/llms-full.txt`** — everything inline: full legal texts, the API v1 endpoint list, and a live aggregate network snapshot.

On top of that, every ternis-host page now has a **Markdown twin**: `/pages/stats.md`, `/pages/stats/domains.md`, `/pages/stats/links.md`, `/pages/legal/{slug}.md` — same aggregates, no chrome, advertised via `rel="alternate"`. Agents and crawlers get exactly what the HTML shows, including the charts (as data tables) and the legal sources (verbatim).
