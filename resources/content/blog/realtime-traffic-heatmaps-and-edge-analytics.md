---
title: Traffic Heatmaps and Hourly Click Insights: Seeing When Your Links Peak
date: 2026-10-28
description: Inside our 7x24 weekday and hourly activity heatmaps: aggregate visual intelligence for newsletter editors and creators with zero invasive tracking.
---

Knowing *how many* people clicked your link is table stakes. Knowing *when* they clicked — and spotting the recurring temporal patterns across your distribution channels — is what actually informs your publishing schedule.

If you dispatch an email newsletter every Tuesday morning, does engagement peak within ten minutes of delivery, or do recipients read through lunch and into the evening commute? If you share a technical article on social media, what timezones drive the second wave of readership?

To answer these questions without harvesting personal data, we shipped **7x24 weekday and hourly traffic heatmaps** across our analytics suite.

## Temporal Aggregation Without Surveillance

Most modern analytics platforms answer timing questions by tracking individual user journeys: they assign persistent client IDs, log full IP addresses, and record exact timestamps linked to user profiles.

We believe that approach is fundamentally broken. Short link analytics should quantify audience engagement without cataloging human behavior.

Here is how our heatmap engine works under the hood:

- **Asynchronous Click Buffering**: When a redirect executes over Caddy and Redis, only the ISO timestamp, referring origin, and general country code are recorded in the event queue.
- **Hour & Day Bucketing**: During analytics rollups, timestamps are mapped into a matrix of 168 discrete buckets (7 days &times; 24 hourly intervals, normalized to UTC with localized browser offset rendering).
- **Transient IP Hashing**: IP addresses are run through a salted, rotating cryptographic hash strictly to filter bot bursts, then discarded immediately. No raw IP addresses ever enter the database.
- **Client-Side Scalable SVG**: Rather than pulling heavyweight third-party charting libraries from external CDNs, the heatmaps render through lightweight, responsive SVG elements designed directly into our Tailwind grayscale theme.

## Making Actionable Decisions

With the 7x24 heatmap grid, patterns emerge instantly:

1. **Optimal Dispatch Windows**: Newsletter authors can pinpoint the exact hour when previous campaign links experienced peak conversion.
2. **Global Spillover**: B2B publishers can observe when international audiences take over after European or American business hours conclude.
3. **Bot Wave Isolation**: Abrupt, unnatural volume spikes concentrated within single, off-peak hours stand out clearly against organic human curves.

The feature is available today across all authenticated dashboards on both `dash.ternis.link` and `my.href.nz`.
