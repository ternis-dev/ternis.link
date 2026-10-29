---
title: Defending Against High-Velocity Link Attacks and Rogue User-Agents
date: 2026-10-29
description: How sliding-window velocity tracking, edge bot gating on protected.ternis.link, and TypeSafe AI client signature triage protect your short links and click metrics from automated abuse.
---

When malicious actors attempt to overwhelm a short link network, they rarely operate manually. Instead, they deploy automated bot syndicates: script swarms designed to generate hundreds of redirect links in seconds, fuzz random slugs, or scrape target sites through link hops.

Unchecked bot traffic causes real damage:
- It inflates click statistics with non-human noise, corrupting conversion funnels.
- It exhausts origin server computing resources.
- It risks burning public domain reputation across global spam and search engine filters.

To defend our users and partners, ternis.link combines **sliding-window burst rate tracking**, **automated client signature triage**, and **dedicated Turnstile verification gateways**.

---

## 1. Sliding-Window Burst Velocity Defense

Spammers frequently launch burst attacks — firing automated HTTP requests to create dozens of links in quick succession across varied disposable email addresses or anonymous proxies.

Our edge pipeline tracks link creation velocity in sliding 60-second windows per hashed creator IP (`threat:burst:{ip_hash}`). If link generation velocity exceeds our burst threshold (> 10 links per minute):
1. **Attack Recognition**: The incident is flagged as an active burst campaign (`spam_burst`).
2. **Bulk Link Containment**: The links generated in the burst are immediately deactivated.
3. **Audit Log Persistence**: The incident is recorded with confidence metadata in `threat_evaluations`.

By catching high-velocity generation at the network edge, automated link flooding is stopped cold before spammers can distribute their links.

---

## 2. Behavioral User-Agent Triage with TypeSafe AI

Rogue crawlers, scrapers, and exploit scripts frequently disguise their activity behind modified User-Agent headers, or conversely, forget to conceal their automation libraries (`sqlmap`, `python-requests`, `Go-http-client`, custom crawler binaries).

Using TypeSafe AI SystemOne, we classify client signatures into distinct behavioral classes:

- **`legitimate_browser`**: Verified consumer desktop and mobile browsers.
- **`verified_crawler`**: Benign search engine unfurlers (Googlebot, Bingbot, Slackbot, Twitterbot).
- **`benign_tool`**: Legitimate developer utilities, curl testing, and authorized API clients.
- **`aggressive_scraper`**: Automated link harvesters and content extractors.
- **`exploit_scanner`**: Hostile vulnerability probes targeting hidden endpoints.

When a client signature exhibits high bot confidence or hostile behavior, traffic is gracefully routed to our verification gateway.

---

## 3. The Role of `protected.ternis.link`

Rather than dropping requests abruptly with a harsh HTTP 403 error, suspicious traffic is redirected to our specialized gateway:

```
https://protected.ternis.link/user-agent?rid={request_id}
```

On `protected.ternis.link`:
- **Transparent Request Tracing**: Visitors receive a unique Request ID (`rid`) explaining why verification is required.
- **Cloudflare Turnstile Verification**: A non-intrusive challenge confirms whether a human is present without requiring annoying CAPTCHA puzzle-solving.
- **Cross-Domain Verification Pass**: Once verified, the visitor receives a signed, time-limited token that grants clean redirects across all ternis domains for two hours.

---

## Clean Analytics, Protected Destinations

By combining automated AI triage with dedicated verification gateways:
- **Creators & Marketers**: Your click counters reflect genuine human engagement, free from scraper noise.
- **Engineers & Developers**: Your destination APIs and web servers are shielded from bot traffic spikes.
- **Network Integrity**: Our shared platform domains (`href.nz`, `ternis.link`, `href.re`, `meinlink.at`) maintain stellar reputation scores across global web filters.

Security on ternis.link is never an afterthought — it is woven directly into every link you shorten.
