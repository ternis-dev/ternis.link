---
title: QR codes for every short link
date: 2026-09-28
description: Print-ready QR codes in SVG and PNG for any short link — from the dashboard, the API, or a plain URL.
---

Every short link on ternis.link doubles as a scannable code. No generator site, no watermark, no account needed to scan — the QR simply encodes the short URL, so it keeps working as long as the link does.

## Where to get the code

- **Dashboard:** open any link and download its QR as PNG, or copy it straight into a flyer layout.
- **API:** `GET /v1/links/{link}/qr` returns SVG by default, `?format=png` for raster — handy when your CI bakes codes into print PDFs.
- **Any public URL:** `GET /v1/qr?url=https://example.com` needs no authentication at all, so you can prototype without a key.

## Why QR + short link beats QR + long URL

A code pointing at `https://db.family-site.example/some/deeply/nested/page` is dense, fiddly to scan from a distance, and frozen forever. A code pointing at a short link stays sparse and scannable — and if the destination moves, you update the link once instead of reprinting the poster. Pair it with a custom slug and people can even type it when their camera app refuses to cooperate.

Print tip: SVG for anything that goes to a print shop (infinite scaling, tiny files), PNG for slides and chat apps. Both are generated server-side from the same source, so they never disagree.

## Codes follow the link lifecycle

Because the code encodes the short URL rather than the destination, it inherits everything the link does. Repoint the destination and every printed code follows along — useful when the event page moves a week before the doors open. Set an expiry date and the code gracefully stops resolving with the link instead of landing scanners on a dead page. Deactivate the link and the code goes dark while its stats stay in your dashboard, so you can audit how many scans it collected before retiring it.

One caveat worth knowing: a QR code is only as permanent as the link behind it. Short links on custom domains keep working as long as the domain stays verified and active — if you ever retire a domain, reprint the codes that point at it. For anything that must survive for years (packaging, signage), prefer a slug on a domain you control outright.
