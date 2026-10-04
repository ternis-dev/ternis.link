@php
$faqs = [
    [
        'q' => 'Can I shorten regular links or only videos on href.yt?',
        'a' => 'You can shorten any web destination on <strong>href.yt</strong>. While the domain and tools are tailored specifically for YouTube, Twitch, Shorts, and video creators, standard web links work with the exact same sub-millisecond redirect speed.',
    ],
    [
        'q' => 'How do timestamp deep-links work?',
        'a' => 'When you share a video with a timestamp (like <code>?t=90s</code> or <code>?t=1m30s</code>), href.yt preserves the exact parameters through the redirect, jumping viewers directly to the highlight or chapter you want them to see.',
    ],
    [
        'q' => 'Do href.yt links show ads, countdowns, or interstitial walls?',
        'a' => 'Never. Every href.yt redirect resolves directly over TLS 1.3 straight to the destination. There are zero ads, zero countdown timers, and no third-party tracking scripts.',
    ],
    [
        'q' => 'How can I get custom vanity video slugs or higher limits?',
        'a' => 'Guests can shorten links instantly with auto-generated slugs. To claim custom branded slugs (e.g., <code>href.yt/my-stream</code>) and access real-time retention charts, sign in with <a href="/login" class="text-red-400 underline underline-offset-2">Ternis Auth</a>.',
    ],
];

$faqJsonLd = array_map(fn ($faq) => [
    '@type' => 'Question',
    'name' => $faq['q'],
    'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($faq['a'])],
], $faqs);
@endphp

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>href.yt — Video &amp; Creator Link Accelerator</title>
    <meta name="description" content="High-velocity short links for YouTube, Twitch, TikTok and video creators. Deep-link with timestamp accuracy, custom video slugs, and zero ad-trackers.">
    <meta name="theme-color" content="#ff0033">
    <link rel="canonical" href="https://href.yt/">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Open Graph & Twitter --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="href.yt">
    <meta property="og:title" content="href.yt — Video &amp; Creator Link Accelerator">
    <meta property="og:description" content="Ultra-compact 7-character links for video highlights, streams, and YouTube timestamps.">
    <meta property="og:url" content="https://href.yt/">

    {{-- Font preloading --}}
    <link rel="preload" href="/fonts/inter-var.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/space-grotesk-var.woff2" as="font" type="font/woff2" crossorigin>

    {{-- JSON-LD structured data --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebSite',
                '@id' => 'https://href.yt/#website',
                'url' => 'https://href.yt/',
                'name' => 'href.yt',
                'description' => 'High-velocity short links for YouTube, Twitch, and video creators.',
            ],
            [
                '@type' => 'WebApplication',
                '@id' => 'https://href.yt/#webapp',
                'name' => 'href.yt Video Link Accelerator',
                'applicationCategory' => 'UtilitiesApplication',
                'url' => 'https://href.yt/',
            ],
            [
                '@type' => 'FAQPage',
                '@id' => 'https://href.yt/#faq',
                'mainEntity' => $faqJsonLd,
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    @vite(['resources/css/yt.css', 'resources/js/yt.js'])
    @livewireStyles

    @if (config('services.turnstile.key'))
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
    @endif
</head>
<body class="yt-canvas min-h-screen font-sans text-neutral-100 antialiased selection:bg-red-600 selection:text-white">

    {{-- Top ambient glow --}}
    <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-96 overflow-hidden">
        <div class="absolute -top-48 left-1/2 -translate-x-1/2 h-96 w-[800px] rounded-full bg-gradient-to-b from-red-600/20 via-rose-600/10 to-transparent blur-3xl"></div>
    </div>

    {{-- Navigation Header --}}
    <header class="border-b border-white/5 bg-[#08090d]/80 backdrop-blur-md sticky top-0 z-40">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3.5 sm:px-6">
            <a href="/" class="group flex items-center gap-2.5" aria-label="href.yt home">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-red-500 to-rose-700 text-white shadow-lg shadow-red-600/30 transition duration-200 group-hover:scale-105">
                    <svg class="h-4.5 w-4.5 translate-x-0.5 fill-current" viewBox="0 0 24 24">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                </span>
                <span class="font-display text-xl font-bold tracking-tight text-white">
                    href<span class="text-red-500">.yt</span>
                </span>
            </a>

            <div class="flex items-center gap-3 sm:gap-4">
                {{-- Live studio indicator --}}
                <div class="hidden items-center gap-2 rounded-full border border-red-500/20 bg-red-500/10 px-3 py-1 text-xs sm:flex">
                    <span class="yt-rec-dot h-2 w-2 rounded-full bg-red-500"></span>
                    <span class="font-mono font-semibold tracking-wider text-red-400">REC · ACCELERATOR</span>
                </div>

                {{-- Mini Audio Equalizer --}}
                <div class="hidden items-end gap-0.5 h-4 sm:flex" aria-hidden="true" title="Audio / Video Equalizer">
                    <div class="yt-eq-bar-1 w-1 rounded-full bg-red-500"></div>
                    <div class="yt-eq-bar-2 w-1 rounded-full bg-rose-500"></div>
                    <div class="yt-eq-bar-3 w-1 rounded-full bg-red-400"></div>
                    <div class="yt-eq-bar-4 w-1 rounded-full bg-amber-400"></div>
                </div>

                @auth
                    <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="yt-btn-secondary rounded-lg px-3.5 py-1.5 text-xs font-semibold">
                        Dashboard &rarr;
                    </a>
                @else
                    <a href="{{ url('/login') }}" class="yt-btn-secondary rounded-lg px-3.5 py-1.5 text-xs font-semibold">
                        Creator Login
                    </a>
                @endauth
            </div>
        </div>
    </header>

    {{-- Main Hero --}}
    <main class="mx-auto max-w-5xl px-4 py-12 sm:px-6 sm:py-16">
        <section class="text-center">
            <div class="inline-flex items-center gap-2 rounded-full border border-red-500/30 bg-red-500/10 px-3.5 py-1 text-xs font-medium text-red-300 backdrop-blur-md">
                <span>⚡</span>
                <span>The Video &amp; Creator Link Shortener</span>
            </div>

            <h1 class="font-display mt-6 text-4xl font-extrabold tracking-tight text-white sm:text-6xl sm:leading-tight">
                Video links, <span class="bg-gradient-to-r from-red-500 via-rose-500 to-amber-400 bg-clip-text text-transparent">accelerated.</span>
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-base text-neutral-400 sm:text-lg">
                Compact 7-character URLs for YouTube, Twitch, TikTok, and video streams. Share timestamp moments and video chapters that jump viewers straight to the highlight.
            </p>

            {{-- Faux Video Player Frame for Link Shortener --}}
            <div class="yt-card mx-auto mt-10 max-w-2xl overflow-hidden rounded-2xl text-left">
                {{-- Video Player Bezel Header --}}
                <div class="flex items-center justify-between border-b border-white/10 bg-black/40 px-4 py-2.5 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-red-500/80"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-500/80"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500/80"></span>
                        <span class="font-mono text-neutral-400 ml-2">href.yt // input-stream</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="yt-timecode rounded px-1.5 py-0.5 text-[11px] text-red-400">HD 1080p</span>
                        <span class="yt-timecode rounded px-1.5 py-0.5 text-[11px] text-neutral-400">00:00:00</span>
                    </div>
                </div>

                {{-- Player Body with Shortener --}}
                <div class="p-6 sm:p-8">
                    <div class="mb-3 flex items-center justify-between">
                        <label for="destination_url" class="text-xs font-semibold uppercase tracking-wider text-neutral-400">
                            Paste Video, Stream, or Destination URL
                        </label>
                        {{-- Live detected platform badge --}}
                        <div id="yt-detected-platform" class="hidden"></div>
                    </div>

                    {{-- Livewire Shortener Component --}}
                    <livewire:public.shorten-form :compact="true" />

                    {{-- Interactive Timestamp & Sample Helpers --}}
                    <div class="mt-6 border-t border-white/10 pt-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-1.5 text-neutral-400">
                                <span>Timestamp jump:</span>
                                <button type="button" data-timestamp-add="30" class="rounded border border-white/10 bg-white/5 px-2 py-0.5 text-neutral-300 hover:border-red-500/40 hover:bg-red-500/10 hover:text-white transition">
                                    +30s
                                </button>
                                <button type="button" data-timestamp-add="60" class="rounded border border-white/10 bg-white/5 px-2 py-0.5 text-neutral-300 hover:border-red-500/40 hover:bg-red-500/10 hover:text-white transition">
                                    +1m
                                </button>
                                <button type="button" data-timestamp-add="300" class="rounded border border-white/10 bg-white/5 px-2 py-0.5 text-neutral-300 hover:border-red-500/40 hover:bg-red-500/10 hover:text-white transition">
                                    +5m
                                </button>
                            </div>

                            <div class="flex items-center gap-1.5 text-neutral-400">
                                <span>Try demo:</span>
                                <button type="button" data-sample-url="https://www.youtube.com/watch?v=dQw4w9WgXcQ" class="text-xs text-red-400 underline underline-offset-2 hover:text-red-300">
                                    YouTube Video
                                </button>
                                <span>·</span>
                                <button type="button" data-sample-url="https://youtube.com/shorts/sample123" class="text-xs text-red-400 underline underline-offset-2 hover:text-red-300">
                                    Shorts
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Player Progress Scrub Bar (Cosmetic) --}}
                <div class="h-1 w-full bg-white/5">
                    <div class="h-full w-2/3 bg-gradient-to-r from-red-600 via-rose-500 to-amber-500"></div>
                </div>
            </div>
        </section>

        {{-- Feature Highlights --}}
        <section class="mt-20">
            <div class="text-center">
                <h2 class="font-display text-2xl font-bold tracking-tight text-white sm:text-3xl">
                    Engineered for Creators &amp; Streams
                </h2>
                <p class="mx-auto mt-2 max-w-xl text-sm text-neutral-400">
                    Why content creators, streamers, and video editors choose href.yt for their channels.
                </p>
            </div>

            <div class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 text-left">
                <div class="yt-card rounded-xl p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-500/10 text-red-400">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <h3 class="font-display text-base font-bold text-white">Timestamp Precision</h3>
                    </div>
                    <p class="mt-2.5 text-xs leading-relaxed text-neutral-400">
                        Preserve <code>?t=...</code> parameters across redirects. Jump audiences straight to the joke, interview answer, or highlight moment.
                    </p>
                </div>

                <div class="yt-card rounded-xl p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-500/10 text-rose-400">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><line x1="12" x2="12.01" y1="18" y2="18"/>
                            </svg>
                        </div>
                        <h3 class="font-display text-base font-bold text-white">Shorts &amp; Reels Ready</h3>
                    </div>
                    <p class="mt-2.5 text-xs leading-relaxed text-neutral-400">
                        Fits seamlessly inside vertical video descriptions, pinned comments, TikTok bios, and YouTube Shorts overlays without wrapping.
                    </p>
                </div>

                <div class="yt-card rounded-xl p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-400">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" x2="21" y1="10" y2="3"/><line x1="3" x2="10" y1="21" y2="14"/>
                            </svg>
                        </div>
                        <h3 class="font-display text-base font-bold text-white">7-Character URLs</h3>
                    </div>
                    <p class="mt-2.5 text-xs leading-relaxed text-neutral-400">
                        At only 7 characters (<code>href.yt/</code>), save precious character limits in live Twitch chat, Discord broadcasts, and tweets.
                    </p>
                </div>

                <div class="yt-card rounded-xl p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>
                            </svg>
                        </div>
                        <h3 class="font-display text-base font-bold text-white">Zero Ad-Walls</h3>
                    </div>
                    <p class="mt-2.5 text-xs leading-relaxed text-neutral-400">
                        Sub-millisecond redirects with zero intermediate ad walls, fake download buttons, or third-party cookies. Clean and trustworthy.
                    </p>
                </div>
            </div>
        </section>

        {{-- Supported Platforms strip --}}
        <section class="mt-16 text-center">
            <p class="text-xs uppercase tracking-wider text-neutral-500 font-semibold">Works seamlessly across video platforms</p>
            <div class="mt-4 flex flex-wrap items-center justify-center gap-4 text-xs font-medium text-neutral-400">
                <span class="rounded-lg border border-white/5 bg-white/5 px-3 py-1.5 text-white">YouTube &amp; Shorts</span>
                <span class="rounded-lg border border-white/5 bg-white/5 px-3 py-1.5 text-white">Twitch VODs &amp; Clips</span>
                <span class="rounded-lg border border-white/5 bg-white/5 px-3 py-1.5 text-white">TikTok</span>
                <span class="rounded-lg border border-white/5 bg-white/5 px-3 py-1.5 text-white">Vimeo</span>
                <span class="rounded-lg border border-white/5 bg-white/5 px-3 py-1.5 text-white">Kick</span>
                <span class="rounded-lg border border-white/5 bg-white/5 px-3 py-1.5 text-white">Loom</span>
            </div>
        </section>

        {{-- Connected Network --}}
        <section class="mt-16 text-left">
            <h2 class="text-center font-display text-2xl font-bold tracking-tight text-white">Part of the Ternis Network</h2>
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="yt-card rounded-xl p-5">
                    <div class="font-display text-base font-bold text-white">href.nz</div>
                    <p class="mt-1 text-xs text-neutral-400">The open, public link shortener for quick links &amp; instant QR codes.</p>
                    <a href="https://href.nz" class="mt-3 inline-block text-xs font-semibold text-red-400 hover:text-red-300">Visit href.nz &rarr;</a>
                </div>
                <div class="yt-card rounded-xl p-5">
                    <div class="font-display text-base font-bold text-white">href.re</div>
                    <p class="mt-1 text-xs text-neutral-400">Official business redirects, invoices, and verified corporate channels.</p>
                    <a href="https://href.re" class="mt-3 inline-block text-xs font-semibold text-red-400 hover:text-red-300">Visit href.re &rarr;</a>
                </div>
                <div class="yt-card rounded-xl p-5">
                    <div class="font-display text-base font-bold text-white">ternis.link</div>
                    <p class="mt-1 text-xs text-neutral-400">Personal subdomains (<code>name.ternis.link</code>) for family &amp; partners.</p>
                    <a href="https://ternis.link" class="mt-3 inline-block text-xs font-semibold text-red-400 hover:text-red-300">Visit ternis.link &rarr;</a>
                </div>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="mt-16 text-left">
            <h2 class="text-center font-display text-2xl font-bold tracking-tight text-white">Frequently Asked Questions</h2>
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($faqs as $faq)
                    <div class="yt-card rounded-xl p-5">
                        <h3 class="font-display text-sm font-bold text-white">{{ $faq['q'] }}</h3>
                        <p class="mt-2 text-xs leading-relaxed text-neutral-400">{!! $faq['a'] !!}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer class="mt-16 border-t border-white/5 bg-[#08090d] py-8 text-center text-xs text-neutral-500">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-center gap-4 px-4">
            <span class="text-neutral-400">href.yt — Video &amp; Creator Link Accelerator</span>
            <span>·</span>
            <a href="https://ternis.link/pages/legal/privacy" class="hover:text-white transition">Privacy</a>
            <span>·</span>
            <a href="https://ternis.link/pages/legal/terms" class="hover:text-white transition">Terms</a>
            <span>·</span>
            <a href="{{ \App\Support\DomainUrls::impressum('href.yt', 'en') }}" class="hover:text-white transition">Imprint</a>
            <span>·</span>
            <a href="https://ternis.dev" class="hover:text-white transition">ternis.dev</a>
        </div>
        <p class="mx-auto mt-4 max-w-2xl px-4 text-[11px] leading-relaxed text-neutral-600">
            Disclaimer: href.yt is an independent utility and is not affiliated, associated, authorized, endorsed by, or in any way officially connected with YouTube, Google LLC, Alphabet Inc., or any of their subsidiaries or affiliates.
        </p>
    </footer>

    @livewireScripts
</body>
</html>
