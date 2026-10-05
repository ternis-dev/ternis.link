---
title: Two dashboards, one account: where your links live now
date: 2026-10-05
description: Public shortener links moved to my.href.nz while personal, business, and newsletter links stay on dash.ternis.link. Here is exactly what lives where — and why the split exists.
---

If you signed in recently and wondered where your `href.nz` links went, nothing is lost — they moved to a dashboard of their own. Public shortener links now live on **[my.href.nz](https://my.href.nz/)**, while `dash.ternis.link` keeps everything else. One account, one login, two focused workspaces.

## What lives where

**[my.href.nz](https://my.href.nz/)** manages links on the no-account shorteners:

- `href.nz` — the general public shortener
- `meinlink.at` — the German-language shortener
- `href.yt` — the video and creator shortener
- `qr.href.nz` — the QR studio host

**dash.ternis.link** manages everything else:

- `clicked.at` — newsletter and email click tracking
- `ternis.link` — family and partner links, including personal subdomains
- `href.re` — official business links
- partner subdomains and your own custom domains

The rule is strict: a link appears in exactly one dashboard. Open an `href.nz` link on `dash.ternis.link` and you get a 404 — not because it is gone, but because it belongs next door.

## Why split at all?

The two audiences barely overlap. Guest shortener users want speed: paste, shorten, copy, done. Family members, partners, and newsletter operators want structure: subdomains, tags, API keys, per-key pages, custom domains. One link table serving both meant filters, columns, and mental overhead nobody asked for.

The public dashboard is deliberately small: overview stats, a link table, create and edit pages, per-link analytics, CSV export, and QR downloads. Account-level sections — API keys, domains, bio pages, notifications, activity, settings — stay single-homed on `dash.ternis.link`, with cross-links from the public sidebar. No duplicated settings, no divergent state.

## How sign-in works across both

Sessions live per host (cookies can't cross `href.nz` ↔ `ternis.link` anyway), so each dashboard runs its own SSO flow and lands you back where you started. Guests hitting `my.href.nz` bounce to its own login page instead of being funneled through the wrong host. `my.href.yt` simply redirects to the canonical `my.href.nz`, so bookmarks on either alias end up in the right place.

If you only ever used `href.nz` as a guest, nothing changes — the [guest workflow](/pages/blog/2026-09-28-shorten-without-an-account) is untouched. But the moment you [manage those links with an account](/pages/blog/2026-10-05-from-guest-link-to-managed-link), `my.href.nz` is where you'll land.
