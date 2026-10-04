---
title: Newsletter click tracking without cookies
date: 2026-10-04
description: Why redirect-based tracking beats pixel tracking for privacy and deliverability — and how clicked.at implements it.
---

Every newsletter platform promises you click analytics. Most of them deliver it with a tracking pixel — a tiny invisible image that fires when the email client loads it, or a proprietary link-rewriting layer you have no visibility into. Both approaches have serious problems. There is a better way.

## The pixel problem

The classic tracking pixel is a 1×1 transparent GIF loaded from a remote server when your email renders. It tells you the email was opened (probably). It tells you very little about which links were clicked.

Worse, email clients increasingly **block remote images by default**. Apple Mail's Mail Privacy Protection pre-fetches images through Apple's proxy, making every open look like it came from an Apple data centre in California. Your open rate goes to 90 %. It means nothing.

Pixels are also, at their core, a surveillance mechanism: the sender knows when you opened an email, from what IP, on what device, often before you've read a word. That is increasingly unacceptable under GDPR and similar laws — and readers know it.

## Redirect-based tracking

clicked.at takes a different approach: **we track at the redirect layer, not the render layer**.

When you put a `clicked.at/abc` link in your newsletter, nothing happens when the email loads. Nothing is fetched, no pixel fires, no cookie is set. The tracking only happens when a reader **actively chooses to click a link** — an unambiguous, intentional action.

When that click happens:

1. The reader's client makes a GET request to `clicked.at/abc`
2. We record: timestamp, HTTP `Referer` header, `User-Agent` (browser/device family), and a SHA-256 hash of the IP (never the raw IP itself)
3. We issue a 302 redirect to the destination URL — the reader arrives at your page within milliseconds

No cookie is set. No JavaScript runs on the reader's device. The entire interaction is a single HTTP round-trip.

## Why this is better for deliverability

Tracking pixels add an image tag to your email. Image-heavy emails sometimes hit spam filters. More importantly, platforms like Gmail and Outlook now proxy or block external images — the pixel route has become unreliable.

With clicked.at links, you send plain `<a href="...">` text links. No images, no hidden elements. Your email is cleaner, lighter, and less likely to be flagged.

## What clicked.at records (and what it doesn't)

| Field | Stored |
|---|---|
| Click timestamp | ✅ Yes |
| Referring domain | ✅ Yes |
| Browser family (Chrome, Safari…) | ✅ Yes |
| OS / device type | ✅ Yes |
| Raw IP address | ❌ Never — SHA-256 hash only |
| Reader email address | ❌ Never |
| Cookie / persistent identifier | ❌ Never |

The hash is a one-way function: you cannot reverse it back to an IP. It exists only to deduplicate clicks (so one reader clicking twice doesn't inflate your count to 2). It is never shared, never sold, never used for advertising.

## The practical result

You get per-link click counts, referrer breakdown, device split, and timing data — everything you need to understand which content resonates — without running a surveillance operation on your readers.

That is what `clicked.at` is built to do.
