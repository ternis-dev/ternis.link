---
title: Defending Short Links: Smart Bot Protection and Edge Gateways
date: 2026-10-29
description: Introducing configurable bot protection, suspicious user-agent screening, and dedicated verification gateways on protected.ternis.link powered by Cloudflare Turnstile.
---

Short links are attractive targets for web scrapers, automated vulnerability probes, and rogue crawlers. When an unmonitored crawler hammers a link, two bad things happen:

1. **Analytics Pollution**: Click counters balloon artificially, distorting conversion ratios and misrepresenting genuine campaign engagement.
2. **Origin Overload**: If your short link points to a resource-intensive endpoint — such as a dynamic PDF download, a staging application, or a transactional billing receipt — automated bot swarms can exhaust downstream server capacity.

To solve this, we introduced **automated bot protection** with an isolated verification gateway: **`protected.ternis.link`**.

## The Architecture of `protected.ternis.link`

Rather than forcing complex challenge puzzles onto every single visitor, our protection system operates in layered defense rings:

### 1. Transparent Human Passes
Legitimate human visitors browsing from standard desktop and mobile browsers continue to experience instantaneous sub-millisecond 302 redirects. Once a visitor successfully solves a challenge, a cryptographically signed human verification token remembers their status across the network for two hours.

### 2. Suspicious User-Agent Screening
Automated scraping scripts frequently announce themselves with default HTTP client identifiers (`curl`, `python-requests`, `aiohttp`, `Go-http-client`, headless automation binaries, or custom scraper signatures).

When our edge router detects a suspicious client signature, it does not drop the request unceremoniously — instead, it issues a signed redirect to:

```
https://protected.ternis.link/user-agent?rid={request_id}
```

The visitor or script operator is given a transparent cryptographic Request ID (`rid`), an explanation of the automated signature detected, and an opportunity to complete a non-intrusive Turnstile verification.

### 3. Per-Link Bot Protection
Link owners who share sensitive destinations or private documents can toggle **Bot Protection** directly when creating or editing a link. When enabled, all non-verified traffic is routed through `protected.ternis.link` where Cloudflare Turnstile validates browser authenticity before permitting the redirect.

### 4. Unified Security Gates: Turnstile + Password Protection
For links that require both bot mitigation and access control, `protected.ternis.link` serves as a unified security gateway. Visitors complete the Turnstile challenge and enter the link password in a single, rate-limited, hardened checkpoint before being forwarded safely to the destination.

By moving verification logic to a dedicated gateway host, your destination applications stay shielded from automated crawlers while your visitors enjoy a trustworthy, transparent navigation experience.
