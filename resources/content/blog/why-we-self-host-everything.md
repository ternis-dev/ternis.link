---
title: Why we self-host everything
date: 2026-09-26
description: Fonts, bot protection, analytics, error pages — no third parties. Fewer dependencies, fewer leaks, fewer 3 a.m. pages.
---

Every external dependency is a privacy disclosure and a reliability bet someone else controls. So ternis.link keeps the list as close to zero as honestly possible:

- **Fonts** live in `public/fonts` (Inter + Space Grotesk + Caveat for the sketchbook) — no Google Fonts ping on every page view.
- **Analytics** is our own queued click pipeline with hashed IPs — no Matomo, no Plausible, no pixel.
- **Charts** render from server-provided aggregates with a bundled Chart.js — no dashboard calling home.
- **Error pages** are our own Blade views — no default framework stack traces leaking paths.

The one deliberate exception is **bot protection**: the public guest forms use a Cloudflare Turnstile widget, because proof-of-work you run yourself just moves the abuse somewhere cheaper. It loads only where guests can post, never on dashboards or docs.

The pattern: if it can run on our servers, it runs on our servers. What remains is the SSO provider (authentication has to live somewhere trustworthy) plus that single bot-check widget — both explicit, both documented in the [privacy policy](/pages/legal/privacy). Everything else is us, which means when something breaks at 3 a.m., there is exactly one party to blame, and it has our pager number.
