---
title: Find and fix dead links before your visitors do
date: 2026-09-29
description: Links rot. Combine click analytics, expiry dates, and one-click deactivation to retire dead links without losing history.
---

Every link collection rots: campaigns end, products get renamed, docs move. A short link pointing at a 404 (or worse, at content that changed meaning) erodes trust with every click. The fix is a routine, not a rescue mission — and three built-in behaviors make it cheap.

## Let the numbers point at suspects

Per-link analytics show totals, per-day counts, top referrers, and unique visitors. A link whose daily clicks decayed to zero months ago is either retired content or a candidate for it; a sudden spike on an ancient slug deserves a look at where it now points. The same aggregates are available over the API (`GET /v1/links/{link}/clicks/summary`), so a scheduled script can flag dormant links automatically, and CSV export keeps a paper trail for audits.

## Retire without destroying evidence

Deactivating a link stops resolution immediately — visitors get a clean not-found page instead of a stale destination — while every click stays counted. This is the correct default for dead links: deletion would erase the analytics that prove the campaign happened. Pair deactivation with [tags](/pages/blog/2026-09-29-organize-links-with-tags) like `retired-2026` and the archive documents its own lifecycle.

## Stop rot before it starts

The cheapest dead link is the one that switches itself off. Set an expiry date on anything time-bound — job ads, event registrations, seasonal offers — and resolution ends on schedule with stats intact (see [links that expire](/pages/blog/2026-09-29-links-that-expire-for-events-and-listings)). Quarterly, sort the dashboard by clicks, skim the bottom, and deactivate what's done. Fifteen minutes, four times a year, zero embarrassing 404s.
