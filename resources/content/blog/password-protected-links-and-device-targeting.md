---
title: Gating sensitive links and targeting devices without tracking cookies
date: 2026-10-03
description: How password-protected short links and edge device targeting (iOS vs. Android) work securely without user surveillance.
---

When distributing digital assets, two requirements often arise simultaneously:

1. **Access control**: Certain links—such as embargoed press releases, private beta test builds, design prototypes, and confidential pricing documents—must not be accessible to random scanners or leaked URLs.
2. **Conditional routing**: Different operating systems and devices often require different destinations (for instance, redirecting iPhone users to Apple TestFlight, Android users to Google Play, and desktop visitors to a web dashboard).

Historically, shorteners handled these requirements by injecting invasive tracking pixels, fingerprinting visitors across third-party cookies, or forcing visitors to create accounts on intermediary platforms.

Here is how our edge architecture solves both challenges with privacy and speed.

## 1. Password-protected short links with throttled unlock

Adding a password to a short link should not introduce third-party analytics bloat or expose passwords in query strings.

Our implementation functions as follows:

- **Bcrypt Hash Storage**: Passwords are never stored in plaintext. They are salted and hashed using Bcrypt on link creation.
- **Edge Prompt Screen**: When a visitor navigates to a password-protected short link, our edge router intercepts the request and serves a lightweight, zero-cookie unlock form with an accessible password input.
- **Brute-Force Rate Limiting**: The unlock endpoint is heavily rate-limited by IP address and subnet using an exponential backoff throttler (e.g. 5 failed attempts locks the gate for 15 minutes). This renders dictionary attacks economically impossible.
- **Stateless Verification**: Upon successful verification, an encrypted, signed HTTP-only cookie or short-lived token unlocks the destination redirect, handing the visitor directly to the target URL.

## 2. Device and OS targeting without invasive fingerprinting

Suppose you are launching a cross-platform mobile application. Sharing three separate links (`myapp.com/ios`, `myapp.com/android`, `myapp.com/web`) creates visual clutter and confuses potential testers.

A single intelligent short link can inspect the incoming HTTP `User-Agent` header at the edge:

- **iOS / iPadOS**: Routes directly to the Apple App Store or TestFlight invitation URL.
- **Android**: Routes directly to the Google Play Store or APK mirror.
- **macOS / Windows / Linux**: Routes to the desktop landing page or download portal.

### Why edge User-Agent evaluation preserves privacy
Unlike client-side fingerprinting scripts (which scrape canvas hashes, installed fonts, and battery status to track individuals across the internet), inspecting the standard `User-Agent` header at the reverse proxy:

1. **Requires zero client-side JavaScript**: The browser receives an instant `302 Found` or `307 Temporary Redirect` response.
2. **Leaves no tracking residue**: No cookies, device UUIDs, or persistent identifiers are written to the visitor's device.
3. **Respects anonymity**: The server only cares about broad categories (`iOS`, `Android`, `Desktop`) necessary to route the request accurately.

## 3. Combining targeting with UTM campaign preservation

When routing users conditionally, destination analytics must remain cohesive. 

If your marketing campaign includes UTM parameters:

```
https://href.nz/app-download?utm_source=twitter&utm_campaign=beta-launch
```

Our edge targeting engine automatically preserves and forwards all original query parameters to the selected target destination:

- **iOS target**: `https://apps.apple.com/app/id123456?utm_source=twitter&utm_campaign=beta-launch`
- **Android target**: `https://play.google.com/store/apps/details?id=com.app&utm_source=twitter&utm_campaign=beta-launch`

This ensures your acquisition reporting inside App Store Connect and Google Play Console remains unbroken, without manual parameter stitching.

---

Access control and device awareness do not require compromising visitor trust. Configure password protection and conditional targeting on any short link today from your dashboard.
