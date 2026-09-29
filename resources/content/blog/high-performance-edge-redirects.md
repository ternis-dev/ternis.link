---
title: High-performance edge redirects with Caddy and Redis
date: 2026-09-29
description: How Caddy, PHP-FPM, and an in-memory Redis cache deliver sub-millisecond short-link resolution under heavy traffic.
---

When a short link goes viral on social media or gets dispatched in an email campaign to ten thousand subscribers, redirect latency is the difference between a seamless click and a dropped visitor. Every millisecond spent negotiating TLS, booting application frameworks, or querying a relational database delays the recipient from reaching the intended page.

Here is how the ternis.link architecture is engineered for sub-millisecond redirect performance without sacrificing analytics or security.

## The critical redirect path

A traditional URL shortener performs multiple expensive operations on every single request: resolving domain context, querying a database for the slug record, parsing JSON metadata, running tracking queries synchronously, and emitting a `301` or `302` header. Under spiky traffic, database connection pools exhaust quickly and response times spike into hundreds of milliseconds.

In our stack, the redirect pipeline is stripped down to bare essentials:

1. **Lightweight TLS termination at the edge**: [Caddy](https://caddyserver.com) terminates TLS 1.3 with automatic certificate renewal, enforces HTTP/2 and HTTP/3 multiplexing, and proxies directly to the PHP-FPM Unix domain socket (`unix//run/php/php-fpm.sock`).
2. **In-memory hot slug caching**: When a slug is requested, the application checks an in-memory Redis cache (`link:{domain_id}:{slug}`) before ever touching the database. Hot slugs are cached with a 300-second time-to-live (TTL). If the cached record exists and is active, the database lookup is bypassed entirely.
3. **Immediate cache coherence**: Whenever a link is edited in the dashboard, renamed, expired, or deactivated via [the API](https://docs.ternis.link/api), model lifecycle events instantly invalidate the specific Redis key (`Link::forgetCachedSlug`). There is no lag between updating a destination URL and seeing the change live.
4. **Asynchronous analytics logging**: Clicks are not written synchronously during the redirect HTTP request. The click payload (hashed IP, user agent, referrer, timestamp) is pushed to a background queue worker (`RecordClick`), allowing the HTTP response to finish and return the `302 Found` header immediately.

## Why misses are never cached

A common pitfall in shortener caching is caching negative lookups (cache misses). If an unknown slug is queried before it is created, caching the miss means a freshly minted link will return `404 Not Found` until the cache expires.

In ternis.link, misses are never cached. If a slug is not found in Redis, the query falls through to the database. The moment a link is created via the dashboard, browser extension, or API, it is immediately resolvable across all edge nodes without cold-cache propagation delays.

## Verifiable performance

By offloading analytics to queue workers and hot slugs to Redis, redirect handlers complete in single-digit milliseconds. Whether resolving [custom slugs](https://ternis.link/pages/blog/2026-09-28-custom-slugs-that-stick) or [ephemeral event links](https://ternis.link/pages/blog/2026-09-29-links-that-expire-for-events-and-listings), the infrastructure remains responsive regardless of request volume.

Check our live aggregates and response volumes on the public [network stats](https://ternis.link/pages/stats) page.
