---
title: Custom slugs that stick
date: 2026-09-27
description: Memorable short links people can recall and type — how custom slugs work, and how to pick good ones.
---

Auto-generated slugs are fine for throwaway links. But the links you say out loud — on stage, on a podcast, on packaging — deserve a slug people remember. That is what custom slugs are for: `links.example.com/launch` instead of `links.example.com/a8Fk2zQ1`.

## The rules

- Letters, numbers, dashes and underscores only (`[a-zA-Z0-9_-]`), so slugs survive being read aloud and retyped.
- Minimum length depends on your plan — shorter is a privilege, because short namespace is scarce. Guests always get auto-generated 8-character slugs; members can go shorter.
- Slugs are unique per domain: your `launch` and someone else's `launch` can coexist on different hostnames.
- Slugs are set at creation and stay put. The destination, description and tags can evolve — the short address itself is stable, which is the whole point of printing it.

## Picking good ones

Short beats clever: `menu`, `v2`, `tour`, `invite`. Match the slug to the moment, not the destination — `spring-sale` outlives whatever CMS path it points at today, and when the page moves you just repoint the link. Avoid lookalike traps (`0` vs `O`, `1` vs `l`) for anything read over a phone call, and prefer lowercase: it reads cleaner on print and nobody has to guess whether you shouted.

Add a description and a tag or two while creating the link; six months from now, `summer-fest` next to forty auto-generated codes is the one you will still recognize. Tags also power the dashboard filter and the API's `?tag=` query, so a little discipline at creation pays off every time you audit.

## Retiring without regret

When a slug has served its purpose, deactivating the link retires it cleanly: it stops resolving, its stats stay in your dashboard, and the name is never silently recycled under you — even deactivated links hold their slugs, so no stranger (and no future you) can accidentally inherit `launch` while old flyers still circulate. If the campaign returns next year, reactivate the same link instead of minting `launch-2` and fragmenting your history.
