---
title: UTM parameters and short links for newsletters
date: 2026-10-04
description: How to combine clicked.at's per-link stats with UTM tags so your analytics platform and your link dashboard tell the same story.
---

If you run a newsletter and a website with analytics, you probably want two things from your links: the click data that clicked.at gives you, and the session data that Google Analytics / Plausible / Fathom records when readers land on your site. UTM parameters bridge the two.

This post shows how to use them together without creating a mess.

## What UTMs do and why they matter

UTM parameters are query string tags appended to a destination URL:

```
https://yoursite.com/article?utm_source=newsletter&utm_medium=email&utm_campaign=oct-2026
```

When a reader clicks this link and lands on your site, your analytics tool records the session as coming from `newsletter` via `email` for campaign `oct-2026`. Without the UTM tags, the session shows up as "direct" — origin unknown.

The five standard UTM parameters:

| Parameter | Purpose | Example |
|---|---|---|
| `utm_source` | Where the traffic comes from | `newsletter`, `clicked-at` |
| `utm_medium` | The channel | `email` |
| `utm_campaign` | The specific campaign | `oct-2026-issue-47` |
| `utm_content` | Which link in the email | `main-cta`, `header-link` |
| `utm_term` | Keywords (rarely used in email) | — |

## How clicked.at and UTMs work together

**clicked.at tracks the click.** Your destination URL's UTM parameters are your business — clicked.at passes them through untouched. The short link points to the full UTM-tagged URL.

Create your tracked link like this:

1. Build the destination URL with UTM tags:
   ```
   https://yoursite.com/article?utm_source=newsletter&utm_medium=email&utm_campaign=oct-2026&utm_content=main-article
   ```

2. Create a clicked.at short link pointing to that full URL:
   ```
   clicked.at/oct-article
   ```

3. Paste `clicked.at/oct-article` into your newsletter.

When a reader clicks, clicked.at records the click (its own analytics), then redirects to the UTM-tagged URL. Your site analytics records the session with full campaign attribution.

**Result:** You have two data sources telling you the same story from different angles:

- clicked.at: "This link got 412 clicks in 6 hours. 73% from mobile. Top referrer: Gmail."
- Plausible: "oct-2026-issue-47 / email / newsletter drove 389 sessions, 4m avg. duration, 2.3 pages/session."

The numbers won't be identical (bot traffic, redirect failures, analytics blockers) but they'll agree directionally.

## Naming your UTM campaigns consistently

Pick a naming convention and stick to it. A workable scheme for newsletters:

```
utm_source=newsletter
utm_medium=email
utm_campaign=YYYY-MM-issue-N    (e.g. 2026-10-issue-47)
utm_content=SLOT                (e.g. header-cta, article-1, sponsor)
```

This lets you filter in your analytics tool by month, by issue, or by slot type across all issues.

## The `utm_content` parameter is your best friend

Most newsletter operators set the same UTM campaign tag on every link in an issue. Then they can't distinguish which link drove the conversion.

`utm_content` solves this. Tag each link differently:

| clicked.at link | Destination |
|---|---|
| `clicked.at/oct-article-top` | `…?utm_content=article-top` |
| `clicked.at/oct-article-bottom` | `…?utm_content=article-bottom` |
| `clicked.at/oct-sponsor` | `…?utm_content=sponsor` |

Now your analytics tool shows which specific link drove sessions, and clicked.at shows click counts per link. Both stories line up.

## A note on duplicate tracking

Some newsletter platforms (Mailchimp, ConvertKit, etc.) also rewrite links with their own tracking and add their own UTM parameters. If you're using clicked.at plus a platform that adds UTMs automatically, you may end up with duplicate or conflicting UTM tags.

The practical fix: **disable the platform's UTM auto-tagging** (most have a checkbox for this under campaign settings) and manage all UTMs yourself via clicked.at destination URLs. You get cleaner data and more control.

---

Short links and UTM tags solve different problems. clicked.at answers "which link got clicked and when." UTM tags answer "what did readers do after they arrived." Used together, they give you the full picture from newsletter to conversion.
