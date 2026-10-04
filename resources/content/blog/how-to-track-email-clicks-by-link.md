---
title: How to track email clicks by link (not just totals)
date: 2026-10-04
description: Most email tools show open rates. clicked.at shows which specific link each reader tapped — a far more useful signal for improving your newsletter.
---

Your email platform probably tells you "this issue had a 34 % click rate." That is a total. It tells you something happened. It doesn't tell you **what** happened — which article teaser got traction, which CTA was ignored, which product link drove the actual conversion.

Per-link click data is the difference between knowing your newsletter worked and knowing **why** it worked.

## What "click rate" hides

A typical issue might contain six links:

- The main article link
- A secondary article teaser
- A product recommendation
- A sponsor CTA
- Your Twitter / social link
- An unsubscribe link (which you don't want people clicking, but they do)

A "34 % click rate" rolls all of these together. If your sponsor CTA got 400 clicks and the main article got 12, you wouldn't know. Both are in the 34 %.

## Per-link tracking with clicked.at

clicked.at gives every link its own identity. You create one clicked.at link per destination, paste them into your newsletter, and the dashboard breaks down clicks per link.

For the example above, your dashboard would show:

| Link | Clicks |
|---|---|
| `clicked.at/main-article` | 12 |
| `clicked.at/secondary-piece` | 87 |
| `clicked.at/product-rec` | 203 |
| `clicked.at/sponsor-cta` | 411 |
| `clicked.at/twitter` | 34 |

Now you have a real signal. Your readers skipped the main article and went straight to the product recommendation. Your sponsor CTA outperformed everything. The secondary piece overperformed its position. None of this was visible in the aggregate number.

## Setting up per-link tracking in practice

**Before you write your issue**, create a clicked.at link for each destination URL you plan to include. Use a meaningful slug: `clicked.at/oct-launch` is far more useful in your dashboard six months later than `clicked.at/a8Fk2zQ1`.

Name your links after what they represent, not just the destination:

- `clicked.at/issue-47-article` (not `clicked.at/thenewsletterbible.com`)
- `clicked.at/issue-47-sponsor` (not `clicked.at/acmecorp-landing`)

When the issue goes out and clicks start arriving, you can see at a glance which section of that specific issue drove engagement. Over multiple issues, patterns emerge: does your product recommendation always outperform editorial? Does position in the email matter more than topic?

## What to do with this data

**Kill underperforming link placements.** If the link in your third paragraph gets 8× fewer clicks than the one in your intro, stop putting important content in the third paragraph.

**Validate your instincts.** You probably have a gut feeling about what your readers care about. Per-link data either confirms it or corrects it. The data wins.

**A/B test via your sending tool + clicked.at.** Send half your list one CTA link, half a different version. The clicked.at counts per link are your results, independent of what your email platform shows.

**Tell sponsors real numbers.** "Your link got 412 clicks in 24 hours, here is the dashboard screenshot" is a better deliverable than "we had a 34 % click rate on this issue."

## One link to rule all the clicks (or not)

If you repeat the same link multiple times in an issue (header CTA + footer CTA pointing to the same destination), you have two choices:

1. Use the **same** clicked.at link both times — total clicks counted together.
2. Use **different** links (`clicked.at/offer-top` and `clicked.at/offer-bottom`) — you can see which position drove more clicks.

Both are valid. The second approach gives you placement data on top of click data.

---

The aggregate click rate is a vanity metric. Per-link data is the actual intelligence. clicked.at exists to give you the second thing.
