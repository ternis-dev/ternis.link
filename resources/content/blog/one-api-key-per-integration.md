---
title: One API key per integration
date: 2026-09-29
description: Scope credentials per script, service, or CI job — with per-key link pages and an audit trail showing which integration made what.
---

The moment a second script needs your API key, mint a second key. One named key per integration — the deploy bot, the newsletter sender, your laptop experiments — is the cheapest security upgrade a link-shortener workflow has. When something misbehaves, you revoke one key instead of rotating a shared secret across five systems and hoping you found them all.

## Mint and scope

Create keys in the dashboard under API keys or with `POST /v1/api-keys`. The full `tl_…` token appears exactly once — only a hash is stored, so save it like a password. Name each key after its job (`ci-runner`, `newsletter-worker`): creation and revocation both land in your activity feed and trigger a security notification, so a key minted at 3 a.m. by nobody you know is visible by breakfast.

Every link a key creates is attributed to it. Responses carry the key's name and prefix, the activity entry records which key made the link, and `GET /v1/links?api_key_id=<ulid>` lists exactly one integration's links (`?api_key_id=none` selects dashboard-made links). No more guessing which script spawned that mystery slug.

## Keep noisy keys out of your way

Automation keys can flood a link list. Each key has a **show on dashboard** toggle: turn it off and that key's links disappear from the main list while staying fully intact on the key's own page — analytics, editing, QR downloads, all of it, with back-links that stay inside the key's context. Nothing is moved or deleted; flipping the toggle back restores the old view instantly.

## The revocation story

Revoking a key stops it cold — future requests answer `401` — while every link it ever made keeps resolving and every click stays counted. Attribution survives revocation too, so last year's campaign links still tell you which long-dead integration created them. The full contract with copy-paste examples is the [API guide](https://docs.ternis.link/api).
