@php
$faqs = [
    ['q' => 'Can I shorten regular links or only videos on href.yt?', 'a' => 'You can shorten any web destination. href.yt is tuned for YouTube, Shorts, Twitch, TikTok, Vimeo, Loom, and video creators, but standard web links get the same direct edge redirect.'],
    ['q' => 'How do timestamp deep-links work?', 'a' => 'Timestamp parameters like <code>?t=90s</code>, <code>?t=1m30s</code>, or <code>?time_continue=45</code> are preserved cleanly through the redirect, taking viewers straight to the moment, chapter, or punchline you intended to share.'],
    ['q' => 'Do href.yt links show ads, countdowns, or intermediate screens?', 'a' => 'Never. Every redirect resolves directly over TLS 1.3 with zero ad walls, countdown timers, captcha interstitials, or third-party tracking cookies.'],
    ['q' => 'How do I claim custom video vanity slugs?', 'a' => 'Guests can shorten instantly with 7-character randomized slugs. Sign in with <a href="/login" class="underline text-red-400 hover:text-red-300">Ternis Auth SSO</a> to claim custom vanity slugs (like <code>href.yt/stream</code> or <code>href.yt/merch</code>) and unlock real-time retention telemetry.'],
    ['q' => 'Can I use href.yt in Twitch bots, Discord, and OBS overlays?', 'a' => 'Yes! The short 7-character format fits cleanly into Twitch chat commands (e.g. <code>!video</code>), Discord embeds, Instagram bios, and OBS stream banners without cluttering the screen.'],
    ['q' => 'Is there an API for stream automation?', 'a' => 'Yes. Authenticated creators get access to our high-speed REST API to cut links automatically via OBS scripts, Streamer.bot, Discord bots, or video upload webhooks.'],
];
$faqJsonLd = array_map(fn ($faq) => ['@type' => 'Question', 'name' => $faq['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($faq['a'])]], $faqs);
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>href.yt — Links with a point of view · Video Link Accelerator</title>
    <meta name="description" content="Compact, direct links for video creators, streamers, and the moments worth sharing. Preserves timestamps, zero ad walls, sub-10ms edge redirects.">
    <meta name="theme-color" content="#0c0e12">
    <link rel="canonical" href="https://href.yt/">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Open Graph & Twitter Cards --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="href.yt">
    <meta property="og:title" content="href.yt — Links with a point of view">
    <meta property="og:description" content="Compact, direct links for video creators, streamers, and the moments worth sharing. Zero ad walls. Timestamp ready.">
    <meta property="og:url" content="https://href.yt/">
    <meta name="twitter:card" content="summary_large_image">

    {{-- Font preloading --}}
    <link rel="preload" href="/fonts/inter-var.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/space-grotesk-var.woff2" as="font" type="font/woff2" crossorigin>

    {{-- Structured Data --}}
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebSite',
                '@id' => 'https://href.yt/#website',
                'url' => 'https://href.yt/',
                'name' => 'href.yt',
                'description' => 'The high-speed video and creator link accelerator.',
            ],
            [
                '@type' => 'WebApplication',
                '@id' => 'https://href.yt/#webapp',
                'name' => 'href.yt',
                'applicationCategory' => 'UtilitiesApplication',
                'url' => 'https://href.yt/',
                'operatingSystem' => 'All',
            ],
            [
                '@type' => 'FAQPage',
                '@id' => 'https://href.yt/#faq',
                'mainEntity' => $faqJsonLd,
            ]
        ]
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    @vite(['resources/css/yt.css', 'resources/js/yt.js'])
    @livewireStyles

    @if (config('services.turnstile.key'))
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
    @endif
</head>
<body class="yt-canvas font-sans antialiased text-neutral-100 selection:bg-red-600 selection:text-white">
<div class="yt-shell">
    {{-- Studio Top Bar / Navigation --}}
    <header class="yt-nav sticky top-0 z-50 mx-auto px-5 py-4 lg:px-10">
        <div class="mx-auto flex max-w-7xl items-center justify-between">
            <a href="/" class="yt-logo flex items-center gap-3 font-display text-xl font-bold" aria-label="href.yt home">
                <span class="yt-logo-mark flex h-9 w-9 items-center justify-center rounded-xl text-lg font-black">↗</span>
                <span>href<span class="font-normal opacity-45">.yt</span></span>
                <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full border border-red-500/30 bg-red-500/10 px-2 py-0.5 text-[10px] font-mono uppercase tracking-wider text-red-400">
                    <span class="yt-rec-dot h-1.5 w-1.5 rounded-full bg-red-500"></span>
                    <span>Studio Desk</span>
                </span>
            </a>

            <nav class="flex items-center gap-5 text-xs font-medium text-neutral-300">
                <a href="#why" class="yt-link hidden md:inline transition hover:text-white">Why href.yt</a>
                <a href="#simulator" class="yt-link hidden lg:inline transition hover:text-white">Simulator</a>
                <a href="#api" class="yt-link hidden sm:inline transition hover:text-white">API</a>
                <a href="#faq" class="yt-link hidden sm:inline transition hover:text-white">FAQ</a>
                <a href="/new" class="yt-btn-secondary hidden sm:inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold">
                    <span>⚡ Quick Cut</span>
                </a>
                @auth
                    <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="yt-btn-primary rounded-full px-4 py-2 text-xs font-bold">
                        Dashboard ↗
                    </a>
                @else
                    <a href="{{ url('/login') }}" class="yt-btn-primary rounded-full px-4 py-2 text-xs font-bold">
                        Creator login
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    {{-- Main Studio Workspace --}}
    <main class="relative z-10 mx-auto max-w-7xl px-5 pb-24 pt-12 lg:px-10 lg:pt-20">
        {{-- Hero & Live Shortening Deck --}}
        <section class="grid items-start gap-12 lg:grid-cols-[1fr_1fr] xl:gap-16">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-red-500/25 bg-red-500/10 px-3 py-1 font-mono text-[11px] font-bold uppercase tracking-wider text-red-400">
                    <span class="yt-rec-dot h-2 w-2 rounded-full bg-red-500"></span>
                    <span>A small tool for big moments / 01</span>
                </div>

                <h1 class="yt-display mt-6 max-w-3xl font-display text-5xl font-black tracking-tight sm:text-7xl lg:text-8xl">
                    Make the<br>
                    <span class="yt-stroke">moment</span><br>
                    <span class="text-[var(--signal)]">click.</span>
                </h1>

                <p class="mt-8 max-w-lg text-base leading-relaxed text-neutral-400 sm:text-lg">
                    A link shortener with a point of view. Built for the clip, the chapter, the drop, and every place a long URL gets in the way.
                </p>

                {{-- Feature Badges & CTAs --}}
                <div class="mt-8 flex flex-wrap items-center gap-4 text-xs font-semibold">
                    <a href="#make" class="yt-btn-primary rounded-full px-6 py-3.5 text-sm font-bold shadow-lg shadow-red-600/30">
                        Cut a link <span class="yt-arrow ml-1 inline-block">↗</span>
                    </a>
                    <div class="flex items-center gap-2 font-mono text-neutral-400">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        <span>No account · No ad wall</span>
                    </div>
                </div>

                {{-- Interactive Sample Fillers --}}
                <div class="mt-10 border-t border-white/10 pt-6">
                    <div class="flex items-center justify-between text-xs text-neutral-400 mb-3">
                        <span class="font-mono uppercase tracking-wider text-[11px] text-neutral-400">Quick Test Presets:</span>
                        <span class="text-[11px] text-neutral-400">Click to load</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="yt-chip" data-sample-url="https://www.youtube.com/watch?v=dQw4w9WgXcQ?t=43s">
                            ▶ YouTube (4K + ?t=43s)
                        </button>
                        <button type="button" class="yt-chip" data-sample-url="https://www.youtube.com/shorts/kJQP7kiw5Fk">
                            ⚡ YouTube Shorts
                        </button>
                        <button type="button" class="yt-chip" data-sample-url="https://clips.twitch.tv/GloriousSpeedyWombatSuperVinlin">
                            🟣 Twitch Clip
                        </button>
                        <button type="button" class="yt-chip" data-sample-url="https://www.loom.com/share/d4a8f90b7c1e457e8d7890abcdef1234">
                            🎥 Loom Demo
                        </button>
                    </div>
                </div>

                {{-- Timestamp Adjustment Controls --}}
                <div class="mt-6">
                    <div class="flex items-center justify-between text-xs text-neutral-400 mb-2">
                        <span class="font-mono uppercase tracking-wider text-[11px] text-neutral-400">Timestamp Injector:</span>
                        <button type="button" data-timestamp-clear class="text-[11px] text-red-400 hover:text-red-300">Clear ?t=</button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="yt-chip" data-timestamp-add="15">+15s</button>
                        <button type="button" class="yt-chip" data-timestamp-add="30">+30s</button>
                        <button type="button" class="yt-chip" data-timestamp-add="60">+1 min</button>
                        <button type="button" class="yt-chip" data-timestamp-add="300">+5 min</button>
                    </div>
                </div>
            </div>

            {{-- The Live Link Desk --}}
            <div id="make" class="yt-desk rounded-3xl p-6 sm:p-8 backdrop-blur-xl">
                <div class="yt-desk-bar flex items-center justify-between border-b pb-4 font-mono text-[11px] uppercase tracking-[.18em] text-neutral-400">
                    <span class="flex items-center gap-2">
                        <i class="yt-rec-dot inline-block h-2.5 w-2.5 rounded-full bg-[var(--signal)]"></i>
                        <span>live / link desk</span>
                    </span>
                    <span class="text-[var(--acid)] font-bold">signal 001</span>
                </div>

                {{-- Detected Platform Dynamic Pill --}}
                <div id="yt-detected-platform" class="yt-detected-platform mt-4 hidden"></div>

                <div class="pb-6 pt-6">
                    <p class="font-display text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                        What are we<br>
                        <span class="text-[var(--acid)]">sending out?</span>
                    </p>
                    <p class="mt-2 text-sm leading-relaxed text-neutral-400">
                        Paste a video, stream, or any destination. We’ll give it a clean, memorable exit.
                    </p>
                </div>

                {{-- Livewire Public Shorten Form --}}
                <livewire:public.shorten-form :compact="true" />

                <div class="mt-8 flex items-center justify-between border-t border-white/10 pt-4 font-mono text-[10px] uppercase tracking-[.16em] text-neutral-400">
                    <span>youtube · twitch · everywhere</span>
                    <span>7 chars / direct</span>
                </div>
            </div>
        </section>

        {{-- Studio Marquee Banner --}}
        <div class="yt-marquee mt-20 -mx-5 overflow-hidden py-3.5 lg:-mx-10 shadow-inner">
            <div class="yt-marquee-track font-mono text-xs font-bold uppercase tracking-[.25em] text-white">
                timestamp ready &nbsp;✦&nbsp; zero ad walls &nbsp;✦&nbsp; creator-owned &nbsp;✦&nbsp; direct tls 1.3 edge redirect &nbsp;✦&nbsp; no cookie tracking &nbsp;✦&nbsp; twitch &amp; youtube optimized &nbsp;✦&nbsp; timestamp ready &nbsp;✦&nbsp; zero ad walls &nbsp;✦&nbsp; creator-owned &nbsp;✦&nbsp;
            </div>
        </div>

        {{-- Feature Architecture / The Edit --}}
        <section id="why" class="mt-24 grid gap-12 lg:grid-cols-[.85fr_1.15fr] items-start">
            <div>
                <p class="yt-kicker font-mono text-[11px] font-bold uppercase">The edit / 02</p>
                <h2 class="yt-display mt-4 font-display text-4xl font-extrabold sm:text-5xl text-white">
                    Less link.<br>More signal.
                </h2>
                <p class="mt-6 text-sm leading-relaxed text-neutral-400">
                    href.yt is deliberately narrow: make the handoff from your idea to someone else’s screen feel instant. No ad walls, no tracking cookies, no delayed countdowns.
                </p>

                <div class="mt-8 space-y-3 font-mono text-xs text-neutral-300">
                    <div class="flex items-center gap-2">
                        <span class="text-emerald-400 font-bold">✓</span>
                        <span>Preserves <code>?t=</code>, <code>?si=</code>, and video chapter hashes</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-emerald-400 font-bold">✓</span>
                        <span>Direct 302/301 HTTP redirects straight from edge caches</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-emerald-400 font-bold">✓</span>
                        <span>Compact 7-character IDs engineered for live chat and video bios</span>
                    </div>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <article class="yt-card rounded-3xl p-6 relative overflow-hidden">
                    <span class="font-mono text-xs text-[var(--signal)] font-bold">01 / TIME</span>
                    <h3 class="mt-8 font-display text-xl font-bold text-white">Land on the moment</h3>
                    <p class="mt-3 text-sm leading-relaxed text-neutral-400">
                        Keep <code>?t=...</code> intact and send people directly to the punchline, chorus, or chapter. Viewers don't have to scrub through a 3-hour VOD.
                    </p>
                </article>

                <article class="yt-card rounded-3xl p-6 relative overflow-hidden">
                    <span class="font-mono text-xs text-[var(--signal)] font-bold">02 / TRUST</span>
                    <h3 class="mt-8 font-display text-xl font-bold text-white">No strange detours</h3>
                    <p class="mt-3 text-sm leading-relaxed text-neutral-400">
                        Direct TLS redirects. No countdowns, bait buttons, or third-party cookies between click and content. Your audience stays protected.
                    </p>
                </article>

                <article class="yt-card rounded-3xl p-6 relative overflow-hidden">
                    <span class="font-mono text-xs text-[var(--signal)] font-bold">03 / SHAPE</span>
                    <h3 class="mt-8 font-display text-xl font-bold text-white">Fits the frame</h3>
                    <p class="mt-3 text-sm leading-relaxed text-neutral-400">
                        Seven characters for chat, descriptions, overlays, bios, and anywhere attention is already scarce. Clean, legible, and easy to dictate on stream.
                    </p>
                </article>

                <article class="yt-card rounded-3xl p-6 relative overflow-hidden">
                    <span class="font-mono text-xs text-[var(--signal)] font-bold">04 / RANGE</span>
                    <h3 class="mt-8 font-display text-xl font-bold text-white">Not just video</h3>
                    <p class="mt-3 text-sm leading-relaxed text-neutral-400">
                        YouTube, Twitch, TikTok, Vimeo, Loom—or the ordinary web page you need to put somewhere better. Works across the entire digital ecosystem.
                    </p>
                </article>
            </div>
        </section>

        {{-- Interactive Simulator & Stream Overlay Mockup --}}
        <section id="simulator" class="mt-24 rounded-3xl border border-white/10 bg-black/40 p-6 sm:p-10 backdrop-blur-md">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-white/10 pb-6">
                <div>
                    <span class="font-mono text-xs text-[var(--acid)] uppercase tracking-wider font-bold">Preview Instrument / 03</span>
                    <h2 class="font-display text-3xl font-extrabold text-white mt-1">Live Simulator &amp; Contexts</h2>
                    <p class="text-sm text-neutral-400 mt-1">See how your accelerated link looks across different creator channels.</p>
                </div>
                <div class="flex items-center gap-1 rounded-xl border border-white/10 bg-white/5 p-1 text-xs">
                    <button type="button" class="yt-sim-tab is-active" data-sim-tab="twitch">Twitch Chat</button>
                    <button type="button" class="yt-sim-tab" data-sim-tab="obs">OBS Overlay</button>
                    <button type="button" class="yt-sim-tab" data-sim-tab="bio">Video Bio</button>
                </div>
            </div>

            <div class="mt-8">
                {{-- Pane 1: Twitch Chat --}}
                <div data-sim-pane="twitch" class="rounded-2xl border border-white/10 bg-[#18181b] p-5 font-mono text-xs">
                    <div class="flex items-center gap-2 border-b border-white/10 pb-3 text-neutral-400 text-[11px]">
                        <span class="h-2 w-2 rounded-full bg-purple-500"></span>
                        <span>StreamChat // #creator-live</span>
                    </div>
                    <div class="mt-4 space-y-3">
                        <div class="flex items-start gap-2">
                            <span class="font-bold text-purple-400">Nightbot:</span>
                            <span class="text-neutral-200">Check out the newly dropped highlight clip! <strong class="text-white underline underline-offset-2">href.yt/epic-win</strong> (opens directly at 04:12)</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="font-bold text-emerald-400">Viewer404:</span>
                            <span class="text-neutral-300">POG clip! That was insane!</span>
                        </div>
                    </div>
                </div>

                {{-- Pane 2: OBS Stream Overlay --}}
                <div data-sim-pane="obs" class="hidden rounded-2xl border border-white/10 bg-gradient-to-r from-red-950/40 via-black/80 to-zinc-950/80 p-8 text-center">
                    <div class="inline-flex items-center gap-3 rounded-full border border-red-500/40 bg-black/70 px-6 py-2.5 shadow-2xl">
                        <span class="yt-rec-dot h-3 w-3 rounded-full bg-red-500"></span>
                        <span class="font-mono text-sm uppercase tracking-wider text-neutral-300">LATEST VIDEO:</span>
                        <span class="font-display text-lg font-black text-white">href.yt/new-vlog</span>
                    </div>
                    <p class="mt-4 text-xs text-neutral-400">Rendered with crisp SVG vector clarity on 1080p and 4K stream overlays.</p>
                </div>

                {{-- Pane 3: Video Bio --}}
                <div data-sim-pane="bio" class="hidden rounded-2xl border border-white/10 bg-[#121212] p-6 text-sm text-neutral-300">
                    <div class="border-b border-white/10 pb-3 font-semibold text-white">
                        YouTube Description &amp; Social Links
                    </div>
                    <div class="mt-4 space-y-2 font-mono text-xs">
                        <p class="text-neutral-400">🎧 Podcast Episode: <span class="text-red-400">href.yt/ep-42</span></p>
                        <p class="text-neutral-400">🎮 Discord Community: <span class="text-red-400">href.yt/discord</span></p>
                        <p class="text-neutral-400">👕 Official Creator Merch: <span class="text-red-400">href.yt/shop</span></p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Developer & Automation API --}}
        <section id="api" class="mt-24 rounded-3xl border border-white/10 bg-neutral-900/60 p-6 sm:p-10 backdrop-blur-md">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-white/10 pb-6">
                <div>
                    <span class="font-mono text-xs text-[var(--signal)] uppercase tracking-wider font-bold">Automation // 04</span>
                    <h2 class="font-display text-3xl font-extrabold text-white mt-1">Creator &amp; Streamer API</h2>
                    <p class="text-sm text-neutral-400 mt-1">Programmatically cut links via OBS scripts, Discord bots, and CI pipelines.</p>
                </div>
                <div class="flex items-center gap-1 rounded-xl border border-white/10 bg-white/5 p-1 text-xs">
                    <button type="button" class="rounded-lg px-3 py-1 font-mono border border-red-500 bg-red-500/10 text-white" data-api-lang="curl">cURL</button>
                    <button type="button" class="rounded-lg px-3 py-1 font-mono text-neutral-400 hover:text-white" data-api-lang="fetch">JavaScript</button>
                    <button type="button" class="rounded-lg px-3 py-1 font-mono text-neutral-400 hover:text-white" data-api-lang="python">Python</button>
                </div>
            </div>

            <div class="mt-6">
                {{-- cURL Snippet --}}
                <div data-api-snippet="curl" class="relative rounded-2xl bg-black/80 p-5 font-mono text-xs text-neutral-200 overflow-x-auto">
                    <pre><code>curl -X POST "https://ternis.link/api/v1/links" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "destination_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ?t=45s",
    "domain": "href.yt",
    "custom_slug": "stream-highlight"
  }'</code></pre>
                </div>

                {{-- JS Snippet --}}
                <div data-api-snippet="fetch" class="hidden relative rounded-2xl bg-black/80 p-5 font-mono text-xs text-neutral-200 overflow-x-auto">
                    <pre><code>const response = await fetch('https://ternis.link/api/v1/links', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_API_KEY',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    destination_url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ?t=45s',
    domain: 'href.yt',
    custom_slug: 'stream-highlight'
  })
});
const data = await response.json();
console.log(data.short_url); // https://href.yt/stream-highlight</code></pre>
                </div>

                {{-- Python Snippet --}}
                <div data-api-snippet="python" class="hidden relative rounded-2xl bg-black/80 p-5 font-mono text-xs text-neutral-200 overflow-x-auto">
                    <pre><code>import requests

res = requests.post(
    "https://ternis.link/api/v1/links",
    headers={"Authorization": "Bearer YOUR_API_KEY"},
    json={
        "destination_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ?t=45s",
        "domain": "href.yt",
        "custom_slug": "stream-highlight"
    }
)
print(res.json()["short_url"])</code></pre>
                </div>
            </div>
        </section>

        {{-- Notes from the Desk / FAQ --}}
        <section id="faq" class="mt-24 border-t border-[var(--line)] pt-14">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <span class="font-mono text-xs text-[var(--signal)] uppercase tracking-wider font-bold">FAQ / 05</span>
                    <h2 class="font-display text-4xl font-extrabold text-white mt-1">Notes from the desk</h2>
                </div>
                <span class="font-mono text-xs text-neutral-400">Everything you need to know about href.yt</span>
            </div>

            <div class="mt-10 grid gap-6 sm:grid-cols-2">
                @foreach ($faqs as $faq)
                    <article class="rounded-2xl border border-white/10 bg-white/5 p-6 hover:border-white/20 transition">
                        <h3 class="font-display font-bold text-lg text-white">{{ $faq['q'] }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-neutral-400">{!! $faq['a'] !!}</p>
                    </article>
                @endforeach
            </div>
        </section>
    </main>

    {{-- Studio Footer --}}
    <footer class="relative z-10 border-t border-[var(--line)] px-5 py-10 lg:px-10 bg-black/50">
        <div class="mx-auto flex max-w-7xl flex-col justify-between gap-6 text-xs text-neutral-400 sm:flex-row sm:items-center">
            <div class="flex items-center gap-3">
                <span class="font-display text-xl font-bold text-white">href<span class="text-red-500 font-semibold">.yt</span></span>
                <span class="font-mono text-[11px] text-neutral-400">· Video &amp; Creator Link Accelerator</span>
            </div>

            <div class="flex flex-wrap gap-5 font-medium">
                <a href="https://ternis.link/pages/legal/privacy" class="hover:text-white transition">Privacy</a>
                <a href="https://ternis.link/pages/legal/terms" class="hover:text-white transition">Terms</a>
                <a href="{{ \App\Support\DomainUrls::impressum('href.yt', 'en') }}" class="hover:text-white transition">Imprint</a>
                <a href="https://ternis.link/pages/stats" class="hover:text-white transition">Network Stats</a>
                <a href="https://ternis.dev" class="hover:text-white transition">ternis.dev ↗</a>
            </div>
        </div>

        <p class="mx-auto mt-6 max-w-7xl text-[11px] leading-relaxed text-neutral-400">
            href.yt is an independent utility and is not affiliated with YouTube, Google LLC, Alphabet Inc., or their subsidiaries.
        </p>
    </footer>
</div>

@livewireScripts
</body>
</html>
