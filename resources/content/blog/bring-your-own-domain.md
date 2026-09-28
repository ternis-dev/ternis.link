---
title: Bring your own domain
date: 2026-09-28
description: Short links on your own hostname — how custom domain registration, DNS verification, and deactivation work.
---

A short link borrows trust from its domain. `links.example.com/launch` tells recipients who you are before they click; a generic shortener domain asks for faith. On eligible plans you can register any hostname you control and serve short links from it within minutes.

## Register, verify, link

1. **Register** the hostname in your dashboard under Domains (or `POST /v1/domains`). It starts unverified — it exists, but serves nothing yet.
2. **Publish the DNS TXT record** shown next to it. One record, no nameserver moves, no downtime on whatever already lives there.
3. **Verify.** The app checks the record and activates the domain; from then on it behaves like any system domain — custom slugs, analytics, QR codes, API, everything.

Verification exists for a reason: without it, anyone could mint links that look like they come from your domain. The TXT handshake proves control without handing over keys.

## The fine print, up front

Custom domains are plan-gated, and system hostnames (plus anything that collides with them) can never be claimed. Slugs stay unique per domain, exactly as on shared hosts. And if you ever retire a hostname, deactivating it preserves every link and its analytics — but the links stop resolving, since an inactive domain answers nothing. So point long-lived print material at domains you intend to keep, and treat retired hostnames like expired certificates: honored in history, gone from service.
