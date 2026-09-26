---
title: Self-hosted bot protection replaces Cloudflare Turnstile
date: 2026-09-26
description: Guest forms now use Altcha proof-of-work, minted and verified on our own servers. No third party involved.
---

Guest link creation used to depend on Cloudflare Turnstile — a script from `challenges.cloudflare.com` with per-domain keys. That broke too often: ad-blockers, enhanced tracking protection, and filtered DNS all block the challenge host, leaving real guests staring at a form that could never submit.

As of this release the guest shortener uses [Altcha](https://altcha.org) proof-of-work instead:

- The widget ships **bundled with our own JS** — no external script, no external request.
- Challenges are **minted and verified server-side** (HMAC-signed, expiring, re-derived on verify).
- Solved payloads are **single-use** (cache-marked) so solutions can't be replayed.
- Verification is **fail-closed**: no solution, no link.

What this means for guests: shortening a link costs your browser a fraction of a second of hash computation instead of a third-party round trip. What it means for privacy: bot protection no longer sends anything anywhere — consistent with the [privacy policy](/pages/legal/privacy), which now states zero third-party involvement.
