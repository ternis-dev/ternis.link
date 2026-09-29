---
title: Privacy-first link analytics without cookies or tracking walls
date: 2026-09-29
description: Measure campaign reach, referrers, and geographic distribution with SHA-256 IP hashing and zero persistent cookies.
---

Most commercial link shorteners are disguised ad-tech platforms. When a user clicks a link, they are bounced through multiple tracking domains, subjected to fingerprinting scripts, tagged with cross-site third-party cookies, and profiled across advertising networks. For privacy-conscious organizations and GDPR compliance, this creates a regulatory nightmare and damages audience trust.

We took the opposite approach: actionable campaign insights without collecting personal identifiable information (PII) or storing raw visitor data.

## Cryptographic hashing instead of raw IP storage

Web servers receive a visitor's IP address by technical necessity to deliver the response packet. What happens to that IP address afterward defines a service's privacy stance:

- **We never store raw IP addresses in click logs**: The moment a click is registered, the IP address is salted and hashed using SHA-256 (`IpHash::make`).
- **Unique visitor calculation**: The daily and monthly unique visitor counts shown in your link analytics are derived from counting distinct SHA-256 hashes within a given time window. We can tell you that 450 unique individuals clicked your link today without ever knowing who they were or where they go next.
- **Automatic IP retention bounds**: For any administrative diagnostic logs where IP preservation is required for automated abuse containment, retention is strictly bounded and automatically pruned by our daily cleanup routines.

## Zero tracking cookies or consent banners

Because ternis.link redirects contain no persistent tracking cookies, advertising pixels, or cross-domain fingerprinting routines:

- Visitors are never forced to navigate cookie consent banners or intermediary "wait 5 seconds" screens.
- Redirects comply out of the box with the EU GDPR, the ePrivacy Directive, and global privacy standards.
- Privacy-conscious browser configurations, such as Safari's Intelligent Tracking Prevention and Firefox's Enhanced Tracking Protection, do not block or flag the redirects.

## What you actually see in analytics

Privacy-first does not mean blind. Your dashboard and API analytics provide everything necessary to measure campaign effectiveness:

- **Referrer domains**: Understand which newsletters, social feeds, or discussion boards drive your traffic.
- **Top countries and cities**: Geographic metrics derived from privacy-preserving GeoIP lookups.
- **Browser families**: Aggregated breakdowns (Chrome, Safari, Firefox, Edge, Scripts, Bots) to evaluate device trends.
- **Clicks over time**: Hourly and daily trend graphs with selectable 7-day, 30-day, and 90-day analytics windows.

For an in-depth breakdown of the exact fields recorded during link resolution, see our guide on [what we count and what we don't](https://ternis.link/pages/blog/2026-09-28-what-we-count-and-what-we-dont).
