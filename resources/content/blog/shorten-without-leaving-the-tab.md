---
title: Shorten links without leaving the tab
date: 2026-09-28
description: The Chrome extension: popup, right-click, and omnibox shortening with guest and API-key modes.
---

The fastest short link is the one you never switch tabs for. The ternis.link browser extension shortens the page you're on from three places: the toolbar popup, the right-click menu, and the address bar (type the keyword, hit Tab, paste or confirm). Zero dependencies, plain Manifest V3, permissions limited to what shortening needs.

## Two modes

- **Guest mode:** shorten immediately, same rules as the href.nz form — auto slugs, fair-use quotas.
- **API-key mode:** paste a `tl_…` key once and the extension acts as you — your domains, your slugs, your analytics, with the key stored on-device and sent only to the API.

Every result shows a QR preview for the scan-and-share case, and a short confirmation step keeps menu-driven shortens deliberate rather than accidental.

## Install and verify

Grab it from the [download page](https://ternis.link/pages/extension), which also serves a version endpoint and a zip built straight from the repo — the same source you can read. Updates follow the same path, so the extension you audit is the extension you run. Between the popup for daily use, the menu for research rabbit holes, and the omnibox for keyboard devotees, there is now no workflow that requires opening the shortener site at all.
