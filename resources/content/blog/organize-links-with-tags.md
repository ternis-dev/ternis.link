---
title: Organize links with tags and descriptions
date: 2026-09-29
description: Turn a pile of short links into a searchable archive — filter by campaign, client, or topic on the dashboard and the API.
---

A dozen short links fit in your head. A few hundred do not. Tags and descriptions are how a link list stays navigable: label each link once at creation, then slice the archive by campaign, client, channel, or topic whenever you need it.

## Tagging rules that scale

Tags are lowercase slugs with dashes (`launch`, `q4-newsletter`, `client-acme`), up to ten per link. The constraint is the feature: a shared vocabulary beats free text. Agree on a scheme early — `ev-` for events, a client prefix, one tag per campaign — and stick to it. An invalid tag is rejected at creation with a plain explanation instead of silently polluting your filters.

Descriptions are the human layer: up to 500 characters of context (`Landing page v3, hero variant B`) that the dashboard search reads alongside slugs, URLs, and tags. Future you, hunting a link from nine months ago, will be grateful.

## Filtering everywhere

On the dashboard, the tag dropdown narrows the table in one click, and clicking any tag pill on a row filters to it. Programmatically, `GET /v1/links?tag=launch` returns exactly the campaign's links for reports and audits. Origin filtering composes with tags: combine `?tag=` with `?api_key_id=` (see [one API key per integration](/pages/blog/2026-09-29-one-api-key-per-integration)) to answer "everything the newsletter worker tagged `launch`" in a single request.

## A habit worth building

Tag at creation, not later. Retrospective tagging requires remembering what each bare slug was for — the very problem tags solve. Two extra seconds per link buys an archive that answers its own questions.
