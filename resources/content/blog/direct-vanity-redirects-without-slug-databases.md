---
title: Direct vanity redirects: bypassing slug lookups for instant forwarding
date: 2026-09-29
description: How direct URL endpoints like /url/{url} and /go/{url} eliminate database setup for quick vanity links and outbound tracking.
---

Not every redirect requires creating a stored short slug in a database. Sometimes you need a quick outbound redirect to mask a destination, track outbound marketing clicks from a public site, or generate an instant QR code for an existing URL without logging into a dashboard.

In our network, direct URL endpoints provide zero-configuration forwarding and instant utilities on public hosts like [href.nz](https://href.nz).

## The direct URL format

You can prefix any web address with our direct redirect paths:

- `https://href.nz/url/https://example.com/long/campaign/path`
- `https://href.nz/go/https://example.com/another/resource`
- Or on href.nz directly: `https://href.nz/https://example.com/quick-link`

The application's deterministic classifier inspects the input: if the path segment contains dots, colons, or slashes, it is immediately recognized as a full URL rather than a short slug. The server issues a clean `302 Found` response to the destination without requiring prior database registration.

## Instant QR codes for any address

Direct URL routing pairs directly with our dynamic QR code generator. Need a high-resolution, print-ready QR code for an arbitrary website on the fly? Simply request the `/qr/` endpoint:

- `https://href.nz/qr/https://example.com/flyer` (returns a PNG image)
- `https://href.nz/qr/https://example.com/flyer/svg` (returns a vector SVG)

No database records are created and no API tokens are required. You get a lightweight, performant asset that you can embed in documentation, print on physical flyers, or drop into automated design templates.

## Sandboxed preview before jumping

Direct URLs can also be previewed before visiting using the sandbox previewer:

- `https://href.nz/preview/https://example.com/target`

The preview page parses the destination safely, reveals the full URL, checks for known malicious URL patterns or scan probe signatures, and lets visitors inspect where the link leads before their browser makes a network connection to the target.

Learn more about safety features in our guide on [previewing links before clicking](https://ternis.link/pages/blog/2026-09-28-preview-before-you-click) and explore [QR codes for short links](https://ternis.link/pages/blog/2026-09-28-qr-codes-for-every-short-link).
