---
title: Flat UI and Austrian Link Management at dash.meinlink.at
date: 2026-11-08
description: dash.meinlink.at gets its very own identity — a high-contrast flat UI with crisp borders, zero drop shadows, Austrian red tones, and full SVG icon mastery.
---

Shorteners often look identical: neutral gray cards, subtle box shadows, and generic components shared across multi-tenant domains. For `dash.meinlink.at`, we took a radically different path: giving Austria’s dedicated link shortener its very own distinct visual language, component system, and workflow tools.

## The Flat UI Philosophy

Unlike the soft gradients and drop-shadow styling of other dashboards across our network, `dash.meinlink.at` embraces a pure **Flat UI**:

- **Zero Drop Shadows**: Clean 1.5px solid borders replace blur effects, ensuring instant optical contrast and razor-sharp readability on retina displays.
- **Austrian Palette**: Grounded in crisp Austrian red (`#d92534`) and dark charcoal (`#18181b`), paired with high-contrast surfaces and full dark-mode awareness.
- **Pure SVG Iconography**: Every metric, button, and navigation action uses custom, pixel-perfect SVGs instead of icon fonts. No layout shifts, no missing glyphs.
- **Dedicated Component Library**: Rather than sharing generic layout components with other dashboards, `dash.meinlink.at` is powered by its own isolated Blade component suite (`<x-meinlink::card>`, `<x-meinlink::stat>`, `<x-meinlink::navbar>`, `<x-meinlink::badge>`).

## Advanced Austrian Link Workflows

Beyond aesthetics, the update brings productivity features crafted for businesses, newsletter authors, and regional campaigns:

1. **Inline Quick-Shorten**: Create new `meinlink.at` links directly from the dashboard overview in a single keypress, without opening modal dialogues.
2. **Vector SVG QR Code Export**: In addition to standard raster images, download infinite-resolution vector SVGs for printed media, flyers, menus, and billboards.
3. **CSV Export**: Export your entire link collection — including targets, clicks, creation dates, and descriptions — with one click into standard semicolon-delimited CSV.
4. **Campaign & UTM Builder**: Attach `utm_source`, `utm_medium`, and `utm_campaign` directly in the creation workflow, ensuring accurate Google Analytics and Matomo attribution.
5. **Flat Tagging & Instant Filtering**: Organize Austrian campaigns by department, region, or channel (`wien`, `newsletter`, `events`) and filter links instantly.

Experience the new dashboard directly at **[dash.meinlink.at](https://dash.meinlink.at)**.
