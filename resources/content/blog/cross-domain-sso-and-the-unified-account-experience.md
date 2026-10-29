---
title: Seamless Cross-Domain SSO: One Login Across the Entire Network
date: 2026-10-29
description: How we built cryptographic handoff tokens and silent session sync so you stay signed in across href.nz, clicked.at, and ternis.link without third-party tracking cookies.
---

When your link-shortening ecosystem spans multiple distinct domains — `href.nz` for anonymous quick links, `clicked.at` for transactional newsletter tracking, `href.re` for corporate invoices, and `ternis.link` for personal link spaces — authentication quickly becomes a usability obstacle.

Browsers rightly block cross-site cookies by default. Third-party cookie deprecation, Safari's Intelligent Tracking Prevention (ITP), and Firefox's Total Cookie Protection mean that a session cookie set on `dash.ternis.link` is completely invisible when you browse to `my.href.nz` or `clicked.at`.

The common industry workaround is terrible: embed a third-party tracking iframe, inject persistent fingerprinting scripts, or bounce every single visitor through multiple intrusive redirect chains.

We built a cleaner, privacy-first alternative: **cryptographic single-use SSO handoff tokens**.

## The Architecture of Silent Handoff

Instead of cross-domain cookie leaks, our authentication protocol operates with signed, time-limited cryptographic handoffs:

1. **Centralized Identity**: Authentication credentials never touch edge hosts. Ternis Auth SSO runs on `auth.ternis.net`, issuing secure, host-pinned session cookies.
2. **Ephemeral Exchange Tokens**: When a signed-in user navigates between network properties (such as jumping from `dash.ternis.link` to `my.href.nz`), our backend issues a short-lived (60-second) cryptographic exchange token signed with SHA-256 HMAC.
3. **Single-Use Redemption**: The destination host validates the signature, consumes the token atomically to prevent replay attacks, and immediately establishes a first-party session cookie scoped exclusively to that origin.
4. **Zero Third-Party Pixels**: At no point does any third-party tracker, foreign iframe, or client-side fingerprinting library execute in your browser.

## The Two-Tier Dashboard Experience

With seamless cross-domain session synchronization in place, we were able to fully decouple our dashboard experiences without fragmenting user accounts:

- **The Public Link Workspace (`my.href.nz`)**: Dedicated to creators and guest users who upgrade to managed links. It focuses purely on high-velocity shortening, custom slugs, and immediate click counts with zero organizational overhead.
- **The Central Operations Console (`dash.ternis.link`)**: Tailored for business operations, family subdomains, team API keys, granular webhook notifications, and custom vanity domains.

You log in once via Ternis Auth, and each workspace recognizes you instantly while respecting origin boundaries and zero-trust security invariants.
