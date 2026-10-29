---
title: Inside Our AI Spam & Phishing Shield: Real-Time Threat Defense with TypeSafe AI
date: 2026-10-29
description: How ternis.link uses TypeSafe AI SystemOne continuous probabilistic scoring to neutralize phishing, spam links, and malicious payloads with zero latency on redirect hot paths.
---

URL shorteners are among the most heavily targeted internet utilities. Because short URLs conceal their true endpoints behind a compact slug, malicious actors frequently attempt to exploit them for credential harvesting (phishing), affiliate search-engine spam, and malicious drive-by download distribution.

At ternis.link, our philosophy has always been clear: **frictionless for legitimate users, impassable for bad actors**.

Today, we are unveiling our **AI-Powered Spam & Phishing Detection Pipeline**, engineered in partnership with TypeSafe AI. It combines machine-learning threat triage, automated mitigation actions, and strict privacy boundaries — without adding a single millisecond of latency to your short link redirects.

---

## Why Traditional Blocklists Are Not Enough

Historically, link shorteners defended against abuse using static DNS blocklists (RBLs) and daily domain reputation feeds. While useful for filtering known malicious domains, static lists suffer from a critical flaw: **latency of discovery**.

Phishing campaigns routinely register disposable new domains, launch rapid multi-channel SMS or email blasts, and decommission the infrastructure within 2 to 4 hours — long before static blocklists publish updated signatures.

To catch zero-day phishing operations before human victims fall for them, security evaluation must inspect the **intent, context, and semantic characteristics** of the destination immediately upon creation.

---

## The TypeSafe AI Architecture

Our security pipeline integrates `sanmai/typesafe-ai-php`, leveraging TypeSafe AI's **SystemOne** probabilistic evaluation framework.

When a link is created anonymously by a guest or by a recently registered account, the system assembles a structured threat profile containing:
- Destination URL structure, host, and domain TLD.
- OpenGraph metadata, title tags, and page description.
- Creator context (account age, historical creation volume).
- Client signature indicators.

```php
$request = SystemOneRequest::build($state, 'jev-latest')
    ->choice('verdict', 'Analyze the destination URL and metadata for threats', [
        'safe'     => 'Benign, legitimate website or service',
        'spam'     => 'Low-quality affiliate spam, SEO link farming, deceptive marketing',
        'phishing' => 'Credential harvesting, brand impersonation, deceptive login portal',
        'malware'  => 'Drive-by download, trojan/exploit payload distribution',
    ])
    ->score('threat_score', 'Threat probability score from 0 to 100', [
        'Benign, legitimate website',
        'Mildly suspicious or low reputation',
        'Borderline or suspected promotional spam',
        'High probability of phishing or scam',
        'Critical confirmed malware or credential harvester',
    ]);
```

Unlike basic keyword regexes, TypeSafe AI analyzes semantic deception — detecting subtle homoglyph impersonations, fake banking verification redirects, and social engineering patterns that evade simple heuristic rules.

---

## The Automated Mitigation Matrix

When an evaluation returns, our automated threat response engine executes proportional mitigation:

| Threat Score | Classified Verdict | Automated Mitigation Action | Link Status |
| :--- | :--- | :--- | :--- |
| **80 – 100** | `phishing` or `malware` | **Quarantine**: Link deactivated, tagged, slug cache flushed | `is_active = false` |
| **60 – 79** | `spam` or `phishing` | **Bot Protection Enforced**: Cloudflare Turnstile challenge | `bot_protection = true` |
| **0 – 59** | `safe` | **Cleared**: Unrestricted normal operation | `is_active = true` |

### 1. Instant Quarantine
If a link exhibits unmistakable indicators of credential theft or malware delivery (score &ge; 80), the link is quarantined immediately. The slug cache is purged across our global edge nodes, and subsequent attempts to visit the link result in an HTTP 404/410 security deactivation notice.

### 2. Automated Turnstile Bot Shielding
When a link falls into the borderline spam tier (score 60–79), the engine automatically enables **Turnstile Bot Protection**. Instead of letting automated scrapers and bots amplify spam campaigns, visitors are safely directed to `protected.ternis.link` where a privacy-preserving Cloudflare Turnstile check confirms they are human before redirecting.

---

## Zero Latency on Redirects: Asynchronous by Design

A paramount technical constraint for ternis.link is speed. Our edge redirects execute in **under 15 milliseconds** via cached slug routing.

Running an external AI call synchronously during link resolution would introduce hundreds of milliseconds of unacceptable network lag. Instead:
- AI threat evaluation executes entirely **asynchronously in background queue workers** (`EvaluateLinkThreatJob`).
- When a legitimate visitor accesses a link, the redirect occurs at wire speed.
- If a bad link is quarantined by a background worker a few seconds after creation, the slug cache is instantly purged, ensuring hostile campaigns cannot survive.

---

## Privacy by Design (DSGVO Compliant)

Consistent with our privacy commitment across the entire ternis network:
- Visitor and creator IP addresses are never stored in raw plaintext.
- The threat audit log (`threat_evaluations`) stores only one-way **SHA-256 IP hashes** (`creator_ip_hash`).
- Aggregated threat statistics are visible publicly on [`/pages/stats`](/pages/stats) without leaking any personal user data.

With TypeSafe AI integrated into our infrastructure, ternis.link delivers enterprise-grade phishing protection while preserving the open, frictionless experience our users depend on.
