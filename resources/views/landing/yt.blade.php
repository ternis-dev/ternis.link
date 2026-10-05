<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>href.yt — short links built for video &amp; creators</title>
    <meta name="description" content="href.yt — the short link domain for video creators. Paste a long URL, share a clean href.yt link. No account needed, instant, free.">
    <meta name="theme-color" content="#0f0f0f">
    <link rel="canonical" href="https://href.yt/">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="href.yt">
    <meta property="og:title" content="href.yt — short links built for video &amp; creators">
    <meta property="og:description" content="Paste a long URL and get back a clean href.yt link. Built for creators — free, instant, no sign-up.">
    <meta property="og:url" content="https://href.yt/">
    <meta name="twitter:card" content="summary">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/landing-public.css', 'resources/css/yt.css', 'resources/js/yt.js'])
    @livewireStyles
    @if (config('services.turnstile.key'))
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
    @endif
</head>
<body class="yt-root">

    {{-- Preloader --}}
    <div class="yt-loader" id="yt-loader" aria-hidden="true" role="presentation">
        <div class="yt-loader-inner">
            <p class="yt-load-brand">href<span>.yt</span></p>
            <div class="yt-load-track">
                <div class="yt-loader-fill" id="yt-loader-fill"></div>
            </div>
            <span class="yt-load-pct" id="yt-load-pct">0%</span>
        </div>
    </div>

    <a class="yt-skip" href="#shorten">Skip to shortener</a>

    {{-- ── Header ─────────────────────────────────────────── --}}
    <header class="yt-head">
        <a href="/" class="yt-brand" aria-label="href.yt home">
            <span class="yt-brand-play" aria-hidden="true">
                {{-- play triangle --}}
                <svg viewBox="0 0 16 16" aria-hidden="true"><polygon points="4,2 14,8 4,14"/></svg>
            </span>
            href<span>.yt</span>
        </a>
        <nav class="yt-nav" aria-label="Account">
            @auth
                <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="yt-login">
                    Dashboard
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            @else
                <a href="{{ url('/login') }}" class="yt-login">
                    Members log in
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            @endauth
        </nav>
    </header>

    <div class="yt-wrap">

        {{-- ── Hero ───────────────────────────────────────── --}}
        <section class="yt-hero" aria-labelledby="hero-title">
            <div class="yt-badge" aria-hidden="true">
                {{-- play icon in badge --}}
                <svg width="10" height="10" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><polygon points="3,2 14,8 3,14"/></svg>
                For creators &amp; video
            </div>
            <h1 class="yt-title" id="hero-title">
                Short links for<br>
                <span class="yt-title-accent">video people.</span>
            </h1>
            <p class="yt-sub">
                Paste any long URL and get back a clean <strong>href.yt</strong> link —
                perfect for descriptions, pinned comments and Linktree alternatives.
                No account needed.
            </p>
            <div class="yt-hero-actions">
                <a href="#shorten" class="yt-play-cta">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><polygon points="3,2 14,8 3,14"/></svg>
                    <span>Shorten a link now</span>
                </a>
                <a href="{{ url('/new') }}" class="yt-ghost">
                    <span>Use the focused shortener</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            </div>
            <p class="yt-hero-links">
                <a href="{{ url('/pages/stats') }}">Live network stats</a>
                <span aria-hidden="true">·</span>
                <a href="https://docs.ternis.link/api">API docs</a>
                <span aria-hidden="true">·</span>
                <a href="{{ url('/login') }}">Member sign-in</a>
            </p>
        </section>

        {{-- ── Shorten form ────────────────────────────────── --}}
        <section class="yt-form-zone" id="shorten" aria-label="Shorten a link">
            <livewire:public.shorten-form :theme="'yt'" />
        </section>

        {{-- ── Live stats ──────────────────────────────────── --}}
        <dl class="yt-stats" aria-label="Network stats">
            <div class="yt-stat">
                <div class="yt-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13.5a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 10.5a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>
                </div>
                <dd class="yt-stat-num"
                    data-target="{{ $stats['total_links'] ?? 0 }}"
                    data-suffix="">0</dd>
                <dt class="yt-stat-label">links created</dt>
            </div>
            <div class="yt-stat">
                <div class="yt-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 3 7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/><path d="m13 13 6 6"/></svg>
                </div>
                <dd class="yt-stat-num"
                    data-target="{{ $stats['total_clicks'] ?? 0 }}"
                    data-suffix="">0</dd>
                <dt class="yt-stat-label">redirects counted</dt>
            </div>
            <div class="yt-stat">
                <div class="yt-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 4.5 13.5H12L11 22l8.5-11.5H13L13 2Z"/></svg>
                </div>
                <dd class="yt-stat-num"
                    data-target="{{ $stats['links_today'] ?? 0 }}"
                    data-suffix="">0</dd>
                <dt class="yt-stat-label">created today</dt>
            </div>
        </dl>

        <hr class="yt-divider">

        {{-- ── Why href.yt ─────────────────────────────────── --}}
        <section class="yt-features" aria-labelledby="features-title">
            <h2 class="yt-features-title" id="features-title">Built for creators</h2>
            <p class="yt-features-sub">Everything you need in a description-box link — nothing you don't.</p>
            <div class="yt-grid">
                <article class="yt-feat">
                    <div class="yt-feat-header">
                        <div class="yt-feat-icon" aria-hidden="true">
                            {{-- bolt --}}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 4.5 13.5H12L11 22l8.5-11.5H13L13 2Z"/></svg>
                        </div>
                        <h3 class="yt-feat-name">Instant, no sign-up</h3>
                    </div>
                    <p class="yt-feat-desc">Paste and shorten in under a second. No registration, no email, no friction. Up to 50 guest links a day.</p>
                </article>
                <article class="yt-feat">
                    <div class="yt-feat-header">
                        <div class="yt-feat-icon" aria-hidden="true">
                            {{-- link icon --}}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13.5a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 10.5a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>
                        </div>
                        <h3 class="yt-feat-name">Clean href.yt links</h3>
                    </div>
                    <p class="yt-feat-desc">8-character slugs that are short enough for pin comments, video descriptions and merch pages.</p>
                </article>
                <article class="yt-feat">
                    <div class="yt-feat-header">
                        <div class="yt-feat-icon" aria-hidden="true">
                            {{-- chart --}}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16"/><path d="M7 20v-6M12 20V8M17 20v-11"/></svg>
                        </div>
                        <h3 class="yt-feat-name">Click analytics</h3>
                    </div>
                    <p class="yt-feat-desc">Members see referrers, countries and per-day click charts — exactly what you need to measure a drop.</p>
                </article>
                <article class="yt-feat">
                    <div class="yt-feat-header">
                        <div class="yt-feat-icon" aria-hidden="true">
                            {{-- tag --}}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 12V4.5A1 1 0 0 1 4.5 3.5H12L20.5 12 12 20.5Z"/><circle cx="8" cy="8" r="1.2" fill="currentColor" stroke="none"/></svg>
                        </div>
                        <h3 class="yt-feat-name">Custom slugs</h3>
                    </div>
                    <p class="yt-feat-desc">Pick your own keyword for brand-safe links that look intentional — available to signed-in members.</p>
                </article>
                <article class="yt-feat">
                    <div class="yt-feat-header">
                        <div class="yt-feat-icon" aria-hidden="true">
                            {{-- qr --}}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="5.25" y="5.25" width="2.5" height="2.5" rx="0.5" fill="currentColor" stroke="none"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="16.25" y="5.25" width="2.5" height="2.5" rx="0.5" fill="currentColor" stroke="none"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="5.25" y="16.25" width="2.5" height="2.5" rx="0.5" fill="currentColor" stroke="none"/><rect x="14" y="14" width="2.5" height="2.5" rx="0.5" fill="currentColor" stroke="none"/><rect x="18.5" y="14" width="2.5" height="2.5" rx="0.5" fill="currentColor" stroke="none"/><rect x="14" y="18.5" width="2.5" height="2.5" rx="0.5" fill="currentColor" stroke="none"/><rect x="18.5" y="18.5" width="2.5" height="2.5" rx="0.5" fill="currentColor" stroke="none"/></svg>
                        </div>
                        <h3 class="yt-feat-name">QR codes free</h3>
                    </div>
                    <p class="yt-feat-desc">Every link comes with a downloadable QR code — ideal for end-card overlays and merch tables.</p>
                </article>
                <article class="yt-feat">
                    <div class="yt-feat-header">
                        <div class="yt-feat-icon" aria-hidden="true">
                            {{-- api / brackets --}}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 18l4-6-4-6M8 18l-4-6 4-6"/></svg>
                        </div>
                        <h3 class="yt-feat-name">API access</h3>
                    </div>
                    <p class="yt-feat-desc">Automate link creation from spreadsheets, n8n or your own tools via a versioned REST API.</p>
                </article>
            </div>
        </section>

        <hr class="yt-divider">

        {{-- ── Notes --}}
        <section class="yt-notes" aria-label="Good to know">
            <div class="yt-note">
                <h2 class="yt-note-title">Members get more</h2>
                <p>Guests get auto-generated 8-char codes. <a href="{{ url('/login') }}">Sign in</a> for custom slugs, shorter links &amp; click stats — same account across the whole Ternis network.</p>
            </div>
            <div class="yt-note">
                <h2 class="yt-note-title">Fair &amp; private</h2>
                <p>50 links per day for guests, no ad trackers, visitor IPs stored only as one-way hashes. You own your links — abuse gets removed, everything else stays live.</p>
            </div>
            <div class="yt-note">
                <h2 class="yt-note-title">Other domains</h2>
                <p>Want something more neutral? Use <a href="https://href.nz">href.nz</a>. Need a verified business link? That's <a href="https://href.re">href.re</a>.</p>
            </div>
            <div class="yt-note">
                <h2 class="yt-note-title">The focused shortener</h2>
                <p>Prefer a distraction-free page? Use <a href="{{ url('/new') }}">href.yt/new</a> — just the form, nothing else.</p>
            </div>
        </section>

        {{-- ── Footer ──────────────────────────────────────── --}}
        <footer class="yt-foot">
            <p class="yt-foot-links">
                href.yt &copy; {{ date('Y') }} built by <a href="https://ternis.dev">ternis.dev</a>
                <span class="yt-foot-sep" aria-hidden="true">·</span>
                <a href="https://ternis.link/pages/legal/privacy">privacy</a>
                <span class="yt-foot-sep" aria-hidden="true">·</span>
                <a href="https://ternis.link/pages/legal/terms">terms</a>
                <span class="yt-foot-sep" aria-hidden="true">·</span>
                <a href="{{ \App\Support\DomainUrls::impressum('href.yt', 'en') }}">imprint</a>
            </p>
            <p class="yt-foot-disclaimer">
                href.yt is an independent link shortening service and is not affiliated with, endorsed by, authorized by, or in any way officially connected with YouTube, Google LLC, Alphabet Inc., or any of their subsidiaries or affiliates. "YouTube" is a registered trademark of Google LLC.
            </p>
        </footer>

    </div>{{-- /.yt-wrap --}}

    @livewireScripts
</body>
</html>
