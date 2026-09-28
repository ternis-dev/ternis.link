---
title: Set an expiry date and forget it
date: 2026-09-28
description: Time-boxed short links for events, campaigns, and classifieds — how expiry works and where the limits are.
---

Some links have a natural shelf life: the event poster, the apartment listing, the conference discount. For those, ternis.link lets you set an expiry date at creation (members can also add or change it later). When the date passes, a daily cleanup deactivates the link — it stops resolving, its stats stay in your dashboard, and you never have to remember to take it down.

## The rules

- Any future date works for members; guest links cap at a year out ("Guest links can live for at most a year" is the actual error message, and it's a policy, not a suggestion).
- Expiry is deactivation, not deletion: the slug stays reserved, the history stays readable, and nothing gets silently recycled.
- QR codes and API consumers follow automatically, since everything resolves through the same link record.

## What expiry is (and isn't) for

Expiry is perfect for the predictable: campaigns with end dates, time-boxed offers, event logistics. It is not access control — anyone with the link can open it until it expires — and it is not a backup strategy: if the destination matters long-term, keep the link alive and let the destination evolve instead.

The pattern that works best: a memorable custom slug plus an expiry date. `links.example.com/spring-fest` reads well on the poster, dies quietly the week after, and its stats tell you exactly how many people it pulled in while it lived. Next year, reactivate the same link rather than minting `spring-fest-2` — your history stays in one place.
