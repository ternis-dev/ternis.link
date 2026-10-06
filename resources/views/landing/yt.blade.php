<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>href.yt — short links built for video &amp; creators</title>
    <meta name="description" content="href.yt — the fast short link &amp; inline QR studio for video creators. Paste a long URL, share a clean href.yt link. No account needed, instant, free.">
    <meta name="theme-color" content="#0f0f0f">
    <link rel="canonical" href="https://href.yt/">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="href.yt">
    <meta property="og:title" content="href.yt — short links built for video &amp; creators">
    <meta property="og:description" content="Paste a long URL and get back a clean href.yt link with instant inline vector QR codes. Built for creators — free, instant, no sign-up.">
    <meta property="og:url" content="https://href.yt/">
    <meta name="twitter:card" content="summary">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <noscript><style>.yt-loader { display: none !important; }</style></noscript>
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
                <svg viewBox="0 0 16 16" aria-hidden="true"><polygon points="4,2 14,8 4,14"/></svg>
            </span>
            href<span>.yt</span>
        </a>

        <div class="yt-head-center">
            <a href="#shorten" class="yt-head-link">Shortener</a>
            <a href="#simulator" class="yt-head-link">Preview</a>
            <a href="#features" class="yt-head-link">Features</a>
            <a href="#faq" class="yt-head-link">FAQ</a>
        </div>

        <nav class="yt-nav" aria-label="Account">
            @auth
                <a href="{{ \App\Support\DomainUrls::publicDashboard('/') }}" class="yt-login">
                    <span>Dashboard</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            @else
                <a href="{{ url('/login') }}" class="yt-login">
                    <span>Members log in</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            @endauth
        </nav>
    </header>

    <div class="yt-wrap">

        {{-- ── Hero ───────────────────────────────────────── --}}
        <section class="yt-hero" aria-labelledby="hero-title">
            <div class="yt-badge" aria-hidden="true">
                <svg width="10" height="10" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><polygon points="3,2 14,8 3,14"/></svg>
                For creators, video &amp; streams
            </div>
            <h1 class="yt-title" id="hero-title">
                Short links for<br>
                <span class="yt-title-accent">video people.</span>
            </h1>
            <p class="yt-sub">
                Paste any long URL and get back a clean <strong>href.yt</strong> link with instant inline vector QR codes —
                optimized for descriptions, pinned comments, stream overlays, and Linktree alternatives.
                No account needed.
            </p>
            <div class="yt-hero-actions">
                <a href="#shorten" class="yt-play-cta">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><polygon points="3,2 14,8 3,14"/></svg>
                    <span>Shorten a link now</span>
                </a>
                <a href="{{ url('/new') }}" class="yt-ghost">
                    <span>Distraction-free mode</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            </div>
            <p class="yt-hero-links">
                <a href="{{ url('/pages/stats') }}">Live network stats</a>
                <span aria-hidden="true">·</span>
                <a href="https://docs.ternis.link/api">API docs</a>
                <span aria-hidden="true">·</span>
                <a href="{{ url('/login') }}">Member sign-in</a>
                <span aria-hidden="true">·</span>
                <span class="yt-kbd-hint"><kbd>/</kbd> to focus input</span>
            </p>
        </section>

        {{-- ── Creator Presets & Helper ─────────────────────── --}}
        <section class="yt-presets-bar" aria-label="Creator URL presets">
            <span class="yt-presets-label">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                Presets:
            </span>
            <div class="yt-presets-list">
                <button type="button" class="yt-preset-btn" data-preset-url="https://youtube.com/watch?v=dQw4w9WgXcQ">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="5.5" width="19" height="13" rx="3"/><polygon points="10.5,9.5 15.5,12 10.5,14.5" fill="currentColor" stroke="none"/></svg>
                    Video Link
                </button>
                <button type="button" class="yt-preset-btn" data-preset-url="https://youtube.com/@creator?sub_confirmation=1" title="Appends 1-click channel subscription prompt">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                    1-Click Subscribe
                </button>
                <button type="button" class="yt-preset-btn" data-preset-url="https://youtu.be/dQw4w9WgXcQ?t=1m30s" title="Starts playback at specific timestamp">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
                    Timestamp Jump
                </button>
                <button type="button" class="yt-preset-btn" data-preset-url="https://twitch.tv/streamer">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Twitch Live
                </button>
                <button type="button" class="yt-preset-btn" data-preset-url="https://store.example.com/drop?utm_source=youtube&utm_medium=video_desc">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 7h15l-1.5 9h-12z"/><path d="M6 7 5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg>
                    Merch Drop + UTM
                </button>
            </div>
        </section>

        {{-- ── Shorten form ────────────────────────────────── --}}
        <section class="yt-form-zone" id="shorten" aria-label="Shorten a link">
            <livewire:public.shorten-form :theme="'yt'" />
        </section>

        {{-- ── Live stats ──────────────────────────────────── --}}
        <div class="yt-stats" role="list" aria-label="Network stats">
            <div class="yt-stat" role="listitem">
                <div class="yt-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13.5a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 10.5a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>
                </div>
                <p class="yt-stat-num"
                    data-target="{{ $stats['total_links'] ?? 0 }}"
                    data-suffix="">0</p>
                <p class="yt-stat-label">links created</p>
            </div>
            <div class="yt-stat" role="listitem">
                <div class="yt-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 3 7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/><path d="m13 13 6 6"/></svg>
                </div>
                <p class="yt-stat-num"
                    data-target="{{ $stats['total_clicks'] ?? 0 }}"
                    data-suffix="">0</p>
                <p class="yt-stat-label">redirects counted</p>
            </div>
            <div class="yt-stat" role="listitem">
                <div class="yt-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 4.5 13.5H12L11 22l8.5-11.5H13L13 2Z"/></svg>
                </div>
                <p class="yt-stat-num"
                    data-target="{{ $stats['links_today'] ?? 0 }}"
                    data-suffix="">0</p>
                <p class="yt-stat-label">created today</p>
            </div>
        </div>

        {{-- ── Interactive Simulator Section ───────────────── --}}
        <section class="yt-sim-section" id="simulator" aria-labelledby="sim-title">
            <div class="yt-sim-head">
                <span class="yt-badge" aria-hidden="true">Live Preview Simulator</span>
                <h2 class="yt-sim-title" id="sim-title">See how your link looks to viewers</h2>
                <p class="yt-sim-sub">Clean 8-character slugs look intentional, professional and uncluttered across all platforms.</p>
            </div>

            <div class="yt-sim-card">
                <div class="yt-sim-tabs" role="tablist" aria-label="Simulator views">
                    <button type="button" class="yt-sim-tab is-active" id="sim-tab-desc" data-sim-tab="desc" role="tab" aria-selected="true" aria-controls="sim-desc-view">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="7" y1="8" x2="17" y2="8"/><line x1="7" y1="12" x2="17" y2="12"/><line x1="7" y1="16" x2="13" y2="16"/></svg>
                        Video Description
                    </button>
                    <button type="button" class="yt-sim-tab" id="sim-tab-pin" data-sim-tab="pin" role="tab" aria-selected="false" aria-controls="sim-pin-view">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Pinned Comment
                    </button>
                    <button type="button" class="yt-sim-tab" id="sim-tab-outro" data-sim-tab="outro" role="tab" aria-selected="false" aria-controls="sim-outro-view">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="12" cy="12" r="3"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
                        End-Card QR Overlay
                    </button>
                </div>

                {{-- Tab 1: Video Description --}}
                <div class="yt-sim-view is-active" id="sim-desc-view" role="tabpanel" aria-labelledby="sim-tab-desc" tabindex="0">
                    <div class="yt-mock-desc">
                        <p class="yt-mock-title">How I Built a Viral SaaS in 14 Days (Full Breakdown)</p>
                        <p class="yt-mock-meta">142K views · 2 days ago</p>
                        <div class="yt-mock-body">
                            <p>Everything mentioned in this video:</p>
                            <p>👉 Source Code &amp; Templates: <span class="yt-mock-link">https://href.yt/saas2026</span></p>
                            <p>👉 Join the Discord Community: <span class="yt-mock-link">https://href.yt/discord</span></p>
                            <p>👉 Exclusive Sponsor Discount: <span class="yt-mock-link">https://href.yt/promo-deal</span></p>
                        </div>
                    </div>
                </div>

                {{-- Tab 2: Pinned Comment --}}
                <div class="yt-sim-view" id="sim-pin-view" role="tabpanel" aria-labelledby="sim-tab-pin" tabindex="0" hidden>
                    <div class="yt-mock-comment">
                        <div class="yt-mock-avatar">YT</div>
                        <div class="yt-mock-comment-content">
                            <div class="yt-mock-pin-badge">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 12V4H17V2H7V4H8V12L6 14V16H11V22L12 23L13 22V16H18V14L16 12Z"/></svg>
                                Pinned by Creator
                            </div>
                            <p class="yt-mock-author">Creator Studio <span class="yt-mock-badge-auth">Author</span> <span class="yt-mock-time">1 hour ago</span></p>
                            <p class="yt-mock-text">
                                Thanks for watching! Grab the cheat sheet here: <span class="yt-mock-link">https://href.yt/cheatsheet</span> — updated daily for subscribers!
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Tab 3: End-Card QR Overlay --}}
                <div class="yt-sim-view" id="sim-outro-view" role="tabpanel" aria-labelledby="sim-tab-outro" tabindex="0" hidden>
                    <div class="yt-mock-video">
                        <div class="yt-mock-video-bg">
                            <div class="yt-mock-video-inner">
                                <span class="yt-mock-video-label">1080p Video Outro (16:9)</span>
                                <div class="yt-mock-outro-card">
                                    <div class="yt-mock-outro-qr">
                                        <svg viewBox="0 0 24 24" width="60" height="60" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="3" height="3"/><rect x="18" y="18" width="3" height="3"/>
                                        </svg>
                                    </div>
                                    <div class="yt-mock-outro-text">
                                        <p class="yt-mock-outro-action">SCAN TO OPEN</p>
                                        <p class="yt-mock-outro-url">href.yt/merch-drop</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <hr class="yt-divider">

        {{-- ── Why href.yt ─────────────────────────────────── --}}
        <section class="yt-features" id="features" aria-labelledby="features-title">
            <h2 class="yt-features-title" id="features-title">Built for creators</h2>
            <p class="yt-features-sub">Everything you need in a description-box link — nothing you don't.</p>
            <div class="yt-grid">
                <article class="yt-feat">
                    <div class="yt-feat-header">
                        <div class="yt-feat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 4.5 13.5H12L11 22l8.5-11.5H13L13 2Z"/></svg>
                        </div>
                        <h3 class="yt-feat-name">Instant, no sign-up</h3>
                    </div>
                    <p class="yt-feat-desc">Paste and shorten in under a second. No registration, no email, no friction. Up to 50 guest links a day.</p>
                </article>
                <article class="yt-feat">
                    <div class="yt-feat-header">
                        <div class="yt-feat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13.5a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 10.5a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>
                        </div>
                        <h3 class="yt-feat-name">Clean href.yt links</h3>
                    </div>
                    <p class="yt-feat-desc">8-character slugs that are short enough for pin comments, video descriptions and merch pages.</p>
                </article>
                <article class="yt-feat">
                    <div class="yt-feat-header">
                        <div class="yt-feat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16"/><path d="M7 20v-6M12 20V8M17 20v-11"/></svg>
                        </div>
                        <h3 class="yt-feat-name">Click analytics</h3>
                    </div>
                    <p class="yt-feat-desc">Members see referrers, countries and per-day click charts — exactly what you need to measure a drop.</p>
                </article>
                <article class="yt-feat">
                    <div class="yt-feat-header">
                        <div class="yt-feat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 12V4.5A1 1 0 0 1 4.5 3.5H12L20.5 12 12 20.5Z"/><circle cx="8" cy="8" r="1.2" fill="currentColor" stroke="none"/></svg>
                        </div>
                        <h3 class="yt-feat-name">Custom slugs</h3>
                    </div>
                    <p class="yt-feat-desc">Pick your own keyword for brand-safe links that look intentional — available to signed-in members.</p>
                </article>
                <article class="yt-feat">
                    <div class="yt-feat-header">
                        <div class="yt-feat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="5.25" y="5.25" width="2.5" height="2.5" rx="0.5" fill="currentColor" stroke="none"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="16.25" y="5.25" width="2.5" height="2.5" rx="0.5" fill="currentColor" stroke="none"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="5.25" y="16.25" width="2.5" height="2.5" rx="0.5" fill="currentColor" stroke="none"/><rect x="14" y="14" width="2.5" height="2.5" rx="0.5" fill="currentColor" stroke="none"/><rect x="18.5" y="14" width="2.5" height="2.5" rx="0.5" fill="currentColor" stroke="none"/><rect x="14" y="18.5" width="2.5" height="2.5" rx="0.5" fill="currentColor" stroke="none"/><rect x="18.5" y="18.5" width="2.5" height="2.5" rx="0.5" fill="currentColor" stroke="none"/></svg>
                        </div>
                        <h3 class="yt-feat-name">QR codes free</h3>
                    </div>
                    <p class="yt-feat-desc">Every link comes with an instant inline vector QR code with SVG and PNG export — ideal for OBS and stream overlays.</p>
                </article>
                <article class="yt-feat">
                    <div class="yt-feat-header">
                        <div class="yt-feat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 18l4-6-4-6M8 18l-4-6 4-6"/></svg>
                        </div>
                        <h3 class="yt-feat-name">API access</h3>
                    </div>
                    <p class="yt-feat-desc">Automate link creation from upload scripts, spreadsheets or n8n via official API hosts (*.t-api.de).</p>
                </article>
            </div>
        </section>

        {{-- ── Creator FAQ Accordion ───────────────────────── --}}
        <section class="yt-faq" id="faq" aria-labelledby="faq-title">
            <h2 class="yt-features-title" id="faq-title">Creator Questions &amp; Answers</h2>
            <p class="yt-features-sub">Everything you need to know about using href.yt links in production.</p>
            
            <div class="yt-faq-list">
                <details class="yt-faq-item">
                    <summary class="yt-faq-summary">
                        <span>Are href.yt links safe for YouTube descriptions and pinned comments?</span>
                        <svg class="yt-faq-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                    </summary>
                    <div class="yt-faq-body">
                        <p>Yes. href.yt links issue instant, direct HTTP 301 redirects that are fully transparent to search engines, crawlers, and platform trust systems. Malicious URLs are prohibited and scanned, ensuring the entire domain stays verified and reputable.</p>
                    </div>
                </details>

                <details class="yt-faq-item">
                    <summary class="yt-faq-summary">
                        <span>How do the inline QR codes work for video overlays and streams?</span>
                        <svg class="yt-faq-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                    </summary>
                    <div class="yt-faq-body">
                        <p>When you shorten a link, href.yt generates high-contrast SVG and PNG QR codes inline. You can download the 600px PNG or SVG vector file directly into OBS Studio, vMix, Premiere Pro, or DaVinci Resolve without any extra tools.</p>
                    </div>
                </details>

                <details class="yt-faq-item">
                    <summary class="yt-faq-summary">
                        <span>Do short links preserve UTM tracking and affiliate parameters?</span>
                        <svg class="yt-faq-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                    </summary>
                    <div class="yt-faq-body">
                        <p>Yes. Any query parameters, UTM codes (like <code>utm_source=youtube</code>), and video timestamps (<code>?t=1m30s</code>) you include in your destination URL are strictly preserved during the redirect.</p>
                    </div>
                </details>

                <details class="yt-faq-item">
                    <summary class="yt-faq-summary">
                        <span>Can I customize the slug after the slash?</span>
                        <svg class="yt-faq-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                    </summary>
                    <div class="yt-faq-body">
                        <p>Guests receive an automatically generated 8-character code. Verified members can sign in via Ternis Auth to select custom slugs (like <code>href.yt/podcast</code>), track real-time click analytics, and manage link collections.</p>
                    </div>
                </details>
            </div>
        </section>

        <hr class="yt-divider">

        {{-- ── Notes ────────────────────────────────────────── --}}
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
