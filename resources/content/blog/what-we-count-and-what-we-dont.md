---
title: What we count, and what we don't
date: 2026-09-27
description: Referrers, browsers, approximate regions, per-day charts — and the personal data we deliberately never store.
---

Click analytics is the reason many people shorten links here instead of pasting raw URLs. So here is the full inventory — no asterisks.

## What we count

Each redirect records a timestamp, the referrer, the browser family derived from the user agent, and an approximate region (country, sometimes city). Visitor IPs are stored only as one-way SHA-256 hashes: enough to count unique visitors and enforce fair-use quotas, useless for identifying anyone.

From those rows we build the aggregates you see per link — totals, top referrers, top countries, per-day charts — plus CSV export when you want to dig in a spreadsheet. Network-wide public stats are aggregate-only; nobody's individual links leak into them.

## What we don't

No advertising trackers, no third-party pixels, no cross-site cookies following your visitors around the web. Authentication is SSO-only, so there isn't even a password database to breach. Deactivating a link stops it resolving without rewriting history — totals stay truthful instead of quietly shrinking.

The principle is boring on purpose: collect the smallest dataset that still answers "did anyone open this, and where did they come from," then stop.
