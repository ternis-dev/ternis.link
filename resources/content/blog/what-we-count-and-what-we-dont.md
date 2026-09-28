---
title: What we count, and what we don't
date: 2026-09-27
description: Referrers, browsers, approximate regions, per-day charts — and the personal data we deliberately never store.
---

Click analytics is the reason many people shorten links here instead of pasting raw URLs. So here is the full inventory — no asterisks.

## What we count

Each redirect records a timestamp, the referrer, the browser family derived from the user agent, and an approximate region (country, sometimes city). Visitor IPs are stored only as one-way SHA-256 hashes: enough to count unique visitors and enforce fair-use quotas, useless for identifying anyone.

From those rows we build the aggregates you see per link — totals, top referrers, top countries, per-day charts with shares — plus CSV export (`timestamp, referrer, user_agent, country_code, city, ip_hash`) when you want to dig in a spreadsheet. The API serves the same numbers (`/clicks` for rows, `/clicks/summary` for aggregates), so dashboards you build yourself never disagree with ours. Network-wide public stats are aggregate-only; nobody's individual links leak into them.

One special case: direct URL views (`/url/…` links that never became saved short links) are tracked separately and stay visible only to admins in system aggregates — your dashboard shows your links, nothing else.

## What we don't

No advertising trackers, no third-party pixels, no cross-site cookies following your visitors around the web. Authentication is SSO-only, so there isn't even a password database to breach. Guest submissions pass a bot check and a scanner-junk filter, which keeps the shared shortener clean without user accounts or tracking cookies.

Deactivating a link stops it resolving without rewriting history — totals stay truthful instead of quietly shrinking. That cuts both ways by design: you cannot edit the past, but neither can anyone else.

The principle is boring on purpose: collect the smallest dataset that still answers "did anyone open this, and where did they come from," then stop.
