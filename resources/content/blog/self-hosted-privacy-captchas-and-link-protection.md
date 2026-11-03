---
title: Self-Hosted Privacy CAPTCHAs, Configurable Link Protection, and Zero-Tracking Gateways
date: 2026-11-03
description: How we built an in-house arithmetic CAPTCHA with zero third-party scripts, added fine-grained protection policies for links and groups, and published our complete Cookie Policy.
---

When protecting short links from automated vulnerability probes, credential stuffers, and rogue scraping swarms, most modern services take the easy route: they drop a third-party CAPTCHA widget onto every challenge page.

While third-party solutions can be effective, they come with substantial architectural trade-offs: external JavaScript downloads, third-party CDN dependencies, network telemetry, and third-party cookies that privacy-conscious organizations and airgapped environments simply cannot accept.

Today, we are taking our privacy-by-design commitment further with three major updates:

1. **A Self-Hosted, In-House Privacy CAPTCHA** with zero external scripts.
2. **Configurable Link & Group Protection Policies** with hierarchical inheritance.
3. **Automated Scraper Screening on Public Legal Pages** and a dedicated **[Cookie Policy](/pages/legal/cookies)** detailing every token and cookie across our network.

---

## 1. The Engineering of an In-House Privacy CAPTCHA

Our goal was simple: provide resilient, automated bot mitigation without leaking a single byte of telemetry to external providers.

Instead of calling out to third-party CDNs, our new **Privacy CAPTCHA** runs 100% locally on the application server:

```
+------------------+         +----------------------------+         +----------------------+
|  Client Browser  | <-----> |   protected.ternis.link    | <-----> |   Local App Server   |
| (No 3rd-party JS)|         |   (Dedicated Security Gate)|         |  (Cryptographic HMAC)|
+------------------+         +----------------------------+         +----------------------+
```

### Dynamic Arithmetic SVGs
When a challenge is triggered, the server generates a lightweight arithmetic challenge (such as `12 + 7 = ?` or `8 × 3 = ?`) rendered directly as pure inline SVG markup. The SVG includes randomized anti-OCR distortion:
- Faint multi-angle noise lines and random radii scatter dots.
- Randomized text rotation and baseline shifts.
- Responsive dark-mode and high-contrast support using `currentColor`.

### Cryptographic HMAC & Anti-Replay Nonces
The challenge answer is never stored in plain text and never kept in long-lived databases:
- The expected solution is HMAC-hashed with the application secret key (`APP_KEY`) and bundled with an expiration timestamp (5-minute TTL) and a cryptographic random nonce.
- The entire payload is encrypted with authenticated AES-256-GCM.
- Upon submission, the nonce is reserved atomically in our high-speed cache (`Cache::add`). Even if a bot attempts to replay a solved token, the nonce check rejects the second attempt immediately.

Once verified, the gateway sets a secure, encrypted `tl_human_verified` cookie valid for two hours, allowing human visitors to navigate the short link network without encountering repeated friction.

---

## 2. Configurable Protection Levels for Links and Groups

Not all links require the same defense posture. A marketing link shared on social media needs frictionless sub-15ms speed, whereas an internal preview link or downloadable software asset requires stricter verification.

You can now select the exact protection strength when creating or editing links on your dashboard:

| Protection Level | Mechanism | Best For |
|---|---|---|
| **Off** | Direct 302 redirect with zero security gates | Public campaign flyers, social media announcements |
| **Standard** | Smart challenge screening via Turnstile or AI | General short links prone to automated scraping |
| **Strict** | Mandatory verification challenge on every visit | Internal documents, private staging environments |
| **Custom Privacy CAPTCHA** | Self-hosted arithmetic check (0 external trackers) | High-compliance portals, privacy-first destinations |

### Hierarchical Group Inheritance
If you organize links into [Link Groups](/pages/blog/2026-10-15-introducing-link-groups), you can now configure the protection policy directly on the parent group. All links attached to that group automatically inherit the group's protection tier unless explicitly overridden on the individual link.

### Exempting Legitimate Automated Clients (`allow_bots`)
If your link is targeted at webhooks, RSS readers, uptime monitors, or curl scripts, you can toggle **"Exempt automated crawlers & webhooks"** (`allow_bots = true`). This bypasses all challenges, ensuring machine integrations never get blocked by verification gates.

---

## 3. Protecting Legal Pages from Scraper Swarms

Automated web scrapers frequently harvest public legal pages and impressum listings to build email databases for spam campaigns. 

To mitigate this, requests to `/pages/legal/*` from known scraper client signatures (such as `curl/`, `python-requests`, `scrapy`, or headless browser runners) are now seamlessly routed to the protected gateway for verification. Legitimate desktop and mobile browsers pass straight through with zero overhead.

---

## 4. Full Cookie Transparency: Our New Cookie Policy

In keeping with our radical transparency ethos, we have published a dedicated **[Cookie Policy](/pages/legal/cookies)** alongside our updated **[Privacy Policy](/pages/legal/privacy)**.

The policy provides a comprehensive, structured inventory of every cookie set across the network, including:
- **`{app}_session`**: Essential authenticated session handling (session duration / 120 minutes inactivity).
- **`XSRF-TOKEN`**: Essential CSRF form security (2 hours).
- **`tl_human_verified`**: Essential security cookie remembering verified human sessions (2 hours).
- **`clicked_locale`**: Functional language preference on `clicked.at` (1 year).
- **`tl-theme`**: Client-side appearance setting stored purely in `localStorage`.

We remain proud to operate with **zero advertising cookies, zero cross-site tracking, and zero third-party behavioral profiling**.

---

*Explore the updated policies at [ternis.link/pages/legal/cookies](/pages/legal/cookies) and [ternis.link/pages/legal/privacy](/pages/legal/privacy), or configure protection policies on your links today at [dash.ternis.link](https://dash.ternis.link).*
