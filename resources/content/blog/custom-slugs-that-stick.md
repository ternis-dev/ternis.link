---
title: Custom slugs that stick
date: 2026-09-27
description: Memorable short links people can recall and type — how custom slugs work, and how to pick good ones.
---

Auto-generated slugs are fine for throwaway links. But the links you say out loud — on stage, on a podcast, on packaging — deserve a slug people remember. That is what custom slugs are for: `links.example.com/launch` instead of `links.example.com/a8Fk2zQ1`.

## The rules

- Letters, numbers, dashes and underscores only (`[a-zA-Z0-9_-]`), so slugs survive being read aloud and retyped.
- Minimum length depends on your plan — shorter is a privilege, because short namespace is scarce.
- Slugs are unique per domain: your `launch` and someone else's `launch` can coexist on different hostnames.

## Picking good ones

Short beats clever: `menu`, `v2`, `tour`, `invite`. Match the slug to the moment, not the destination — `spring-sale` outlives whatever CMS path it points at today, and when the page moves you just repoint the link. Add a description and a tag or two while creating it; six months from now, `summer-fest` next to forty auto-generated codes is the one you will still recognize.

And when a slug has served its purpose, deactivating the link retires it cleanly: it stops resolving, its stats stay in your dashboard, and the name is yours to reuse deliberately — never silently recycled under you.
