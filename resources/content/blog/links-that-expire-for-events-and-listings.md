---
title: Links that expire for events, listings, and limited offers
date: 2026-09-29
description: Job ads, event tickets, seasonal deals — expiring short links that switch off by themselves, with stats intact for the retro.
---

Some links have a natural death date. A job ad closes, early-bird pricing ends, the event is over — but the short link printed on flyers and posted across social feeds lives on, sending latecomers somewhere stale. Expiry dates fix this at creation: pick the date, and the link switches itself off on schedule. No calendar reminder, no cleanup sprint.

## Set it and forget it

Expiry is one field on the create form and one `expires_at` parameter on the API, alongside slug, description, and [tags](/pages/blog/2026-09-29-organize-links-with-tags). An expired link stops resolving with a clean not-found page — never a redirect to something unrelated — while its analytics stay intact for the retrospective: how many scans did the poster drive, which day peaked, where did visitors come from.

Guest links cap out at a year, which covers nearly every time-bound use case; signed-in links take any future date. If plans change, edit the date or clear it — expiry is a setting, not a sentence.

## Made for the pattern

- **Hiring:** one short link per posting. It dies with the role; the hiring retro keeps its numbers.
- **Events:** early-bird, regular, and door-sale links with staggered expiries on the same destination, tagged per phase for per-phase stats.
- **Limited offers:** the QR code on packaging outlives the promotion — expiry turns it into a polite dead end instead of a misleading one.

Expired links sort and filter like any other, so the quarterly [dead-link review](/pages/blog/2026-09-29-find-and-fix-dead-links) finds them already switched off, history preserved.
