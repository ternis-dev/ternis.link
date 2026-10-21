---
title: A rebuilt public dashboard, a second host, and a new column picker
date: 2026-10-21
description: my.ternis.link gets a fresh app shell and a restructured navigation, my.href.nz now serves the same dashboard under its own name, and every table lets you pick the columns you want to see.
---

**[my.ternis.link](https://my.ternis.link/)** has been rebuilt. The dashboard no longer wears the generic ternis.link header — it has its own app shell now: a sticky bar with the host brand, the navigation and your account menu in one place, plus a calmer overview with a proper hero band and the panels laid out in a readable grid.

The navigation changed with it. **API keys, domains and settings moved onto the public dashboard.** They used to live only on dash.ternis.link, which meant that managing a key meant leaving the place where your links are. Now the whole public-links workflow — create a link, mint a key for it, claim a domain, adjust your settings — happens in one place.

**[my.href.nz](https://my.href.nz/)** is no longer a redirect. It serves the very same dashboard, with the same session and the same data, under its own name — and every link it renders stays on that host.

Every table on the dashboard now has a **"Columns"** button. Toggle a column off, drag the order around, and the choice sticks: it is saved per account, so your view is the same on every device and every page. It works on the links table, the activity log, the click analytics, your API keys, both domain tables, and the admin console.

One more thing for developers: the public dashboard exposes a small **auth API** (`/auth/api/session` and `/auth/api/token`) for apps running on another origin. Cross-origin access is off by default and has to be enabled explicitly, so nothing changes for existing setups. See the [auth API guide](https://docs.ternis.link/auth-api).
