---
title: New public dashboard UI with legacy opt-in
date: 2026-10-06
description: The public dashboard gets a fresh emerald UI system with its own pd components; the previous indigo UI stays available under /_legacy per account preference.
---

The public dashboard has a new look — and the old one isn't going anywhere unless you want it to.

What shipped:

- **New emerald UI system** — flat design language with a dedicated `x-pd.*` component set (button, card, stat, head, chip, empty), a compact topbar shell, and a scoped theme that re-skins shared tables, forms, analytics, and modals. Same links, stats, exports, QR, and per-key pages underneath.
- **Legacy dashboard preserved** — the previous indigo UI now lives under `/_legacy/*` with identical features and partition rules.
- **Per-account switch** — a "Legacy UI" pill in the new topbar opts out (and "Try the new dashboard" opts back in); legacy-preferring users land on `/_legacy` from the domain root. No migration needed, switch any time.
- **Shared scope, zero duplication** — both UIs run off one query scope (`ScopesPublicLinks`) and one controller base; only views, routes, and theme differ.
