---
title: Why we self-host everything
date: 2026-09-26
description: Fonts, bot protection, analytics, error pages — no third parties. Fewer dependencies, fewer leaks, fewer 3 a.m. pages.
---

Every external dependency is a privacy disclosure and a reliability bet someone else controls. So ternis.link keeps the list at zero:

- **Fonts** live in `public/fonts` (Inter + Space Grotesk + Caveat for the sketchbook) — no Google Fonts ping on every page view.
- **Bot protection** is Altcha proof-of-work, minted and verified locally — no Cloudflare challenge host that ad-blockers eat.
- **Analytics** is our own queued click pipeline with hashed IPs — no Matomo, no Plausible, no pixel.
- **Charts** render from server-provided aggregates with a bundled Chart.js — no dashboard calling home.

The pattern: if it can run on our servers, it runs on our servers. What remains is the SSO provider (authentication has to live somewhere trustworthy) and the payment rails behind plans — both explicit, both documented in the [privacy policy](/pages/legal/privacy). Everything else is us, which means when something breaks at 3 a.m., there is exactly one party to blame, and it has our pager number.
