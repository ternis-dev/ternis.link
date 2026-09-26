---
title: ternis.link now speaks agent: llms.txt and Markdown everywhere
date: 2026-09-26
description: New /llms.txt and /llms-full.txt endpoints plus text/markdown twins for every public page.
---

AI assistants and crawlers get first-class support from today:

- **`/llms.txt`** on every host — what the service is, where the pages live, where the API lives.
- **`/llms-full.txt`** — the same plus full legal texts, the API endpoint list, and a live network snapshot, all inline. One fetch, full context.
- **Markdown twins** for every public page (`/pages/stats.md`, `/pages/legal/privacy.md`, …), advertised with `rel="alternate"` so agents can discover them from the HTML.
- **Real `/robots.txt` and `/sitemap.xml`** — dynamic per host, pointing crawlers at app pages and away from auth flows, redirect endpoints, and the short-link space.

If you build with agents on top of ternis.link, point them at `/llms-full.txt` first.
