<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>clicked.at — Newsletter & Email Click Tracking, Simplified</title>
    <meta name="description" content="clicked.at turns every link in your newsletter into a tracked short link. See who clicked what, when, and from where — in real time. Privacy-first, no cookies.">
    <meta name="keywords" content="newsletter tracking, email click tracking, link tracking, click analytics, email marketing analytics, newsletter analytics, link shortener tracking">
    <meta name="author" content="Ternis">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="theme-color" content="#6d28d9">

    <link rel="canonical" href="https://clicked.at/">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="clicked.at">
    <meta property="og:title" content="clicked.at — Know what your readers click">
    <meta property="og:description" content="Drop tracked short links into any newsletter or email campaign and watch your click data flow in real time. No cookies. No third-party scripts in your email.">
    <meta property="og:url" content="https://clicked.at/">

    {{-- Twitter / X --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="clicked.at — Know what your readers click">
    <meta name="twitter:description" content="Real-time click analytics for newsletters, email campaigns, and every link you ship.">

    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Structured Data --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type'       => 'WebSite',
                '@id'         => 'https://clicked.at/#website',
                'url'         => 'https://clicked.at/',
                'name'        => 'clicked.at',
                'description' => 'Newsletter and email click tracking powered by the ternis.link network.',
                'inLanguage'  => 'en',
            ],
            [
                '@type'               => 'WebApplication',
                '@id'                 => 'https://clicked.at/#webapp',
                'name'                => 'clicked.at Link Tracker',
                'applicationCategory' => 'UtilitiesApplication',
                'operatingSystem'     => 'All',
                'url'                 => 'https://clicked.at/',
                'description'         => 'Replace any URL in your newsletter with a clicked.at tracked link and get real-time click data — no cookies, no third-party scripts.',
                'offers'              => [
                    '@type'         => 'Offer',
                    'price'         => '0',
                    'priceCurrency' => 'EUR',
                ],
                'featureList' => [
                    'Real-time click analytics per link',
                    'Referrer and device tracking',
                    'Custom short slugs',
                    'Privacy-first: SHA-256 hashed IPs, no ad trackers',
                    'API for automation',
                    'Dashboard with per-link stats',
                ],
            ],
            [
                '@type'      => 'FAQPage',
                '@id'        => 'https://clicked.at/#faq',
                'mainEntity' => [
                    [
                        '@type'          => 'Question',
                        'name'           => 'How does clicked.at track newsletter clicks?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text'  => 'You replace links in your newsletter with clicked.at short links. When a reader clicks, the request hits our server first, we record the click (timestamp, referrer, device type), then instantly redirect to the destination. No pixel, no script, no cookie — tracking happens at the redirect layer.',
                        ],
                    ],
                    [
                        '@type'          => 'Question',
                        'name'           => 'Do I need to change my email provider?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text'  => 'No. clicked.at works with any email provider or newsletter tool — Mailchimp, Substack, ConvertKit, beehiiv, or plain text email. Just swap URLs before you hit send.',
                        ],
                    ],
                    [
                        '@type'          => 'Question',
                        'name'           => 'Is click tracking GDPR-compliant?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text'  => 'Yes. We never store raw IP addresses — only a one-way SHA-256 hash used for deduplication. No advertising cookies, no third-party data sharing. Your readers\' privacy is protected at the infrastructure level.',
                        ],
                    ],
                    [
                        '@type'          => 'Question',
                        'name'           => 'Can I use my own domain for tracked links?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text'  => 'Yes. Members on eligible plans can point a custom hostname (e.g. go.yourbrand.com) at the network. Every link then carries your brand, not ours.',
                        ],
                    ],
                    [
                        '@type'          => 'Question',
                        'name'           => 'What link data does clicked.at collect per click?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text'  => 'Timestamp, referring domain, browser family, OS, and device type. Raw IPs are immediately hashed and discarded. No page content, no user identity, no cross-site profiling.',
                        ],
                    ],
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    @vite(['resources/css/clicked.css'])
    @livewireStyles
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased selection:bg-violet-500 selection:text-white dark:bg-zinc-950 dark:text-zinc-100">

    <a href="#get-started" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-violet-600 focus:px-4 focus:py-2 focus:text-white">
        Skip to get started
    </a>

    {{-- Ambient background --}}
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -top-48 left-1/2 -translate-x-1/2 h-[600px] w-[900px] rounded-full bg-gradient-to-b from-violet-500/12 via-indigo-500/6 to-transparent blur-3xl"></div>
        <div class="absolute top-[700px] -right-40 h-[500px] w-[500px] rounded-full bg-violet-200/30 blur-3xl dark:bg-violet-900/10"></div>
        <svg class="absolute inset-0 h-full w-full stroke-zinc-900/[0.03] [mask-image:radial-gradient(100%_60%_at_top_center,white,transparent_80%)] dark:stroke-white/[0.025]" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="ca-grid" width="48" height="48" patternUnits="userSpaceOnUse">
                    <path d="M48 0L0 0 0 48" fill="none" stroke-width="1"/>
                    <circle cx="48" cy="48" r="1" fill="currentColor" fill-opacity="0.3"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#ca-grid)" />
        </svg>
    </div>

    {{-- ── NAVIGATION ────────────────────────────────────── --}}
    <header class="sticky top-0 z-40 w-full border-b border-zinc-200/80 bg-white/80 backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
            {{-- Logo --}}
            <a href="/" class="group flex items-center gap-2.5 text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100" aria-label="clicked.at home">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 via-indigo-600 to-violet-700 text-white shadow-md shadow-violet-500/30 transition duration-200 group-hover:scale-105 group-hover:shadow-violet-500/50">
                    {{-- cursor-click icon --}}
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 3.5V2M5.06 5.06L4 4M3.5 9H2M5.06 12.94L4 14M12.94 5.06L14 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M9 9L20.5 14.5L14.5 16L12.5 22L9 9Z" fill="currentColor" fill-opacity="0.2" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span>clicked<span class="text-violet-600 dark:text-violet-400">.at</span></span>
            </a>

            <nav class="flex items-center gap-3 sm:gap-6" aria-label="Main navigation">
                <a href="#how-it-works" class="hidden text-sm font-medium text-zinc-600 transition hover:text-zinc-900 sm:inline-block dark:text-zinc-400 dark:hover:text-zinc-100">How it works</a>
                <a href="#features" class="hidden text-sm font-medium text-zinc-600 transition hover:text-zinc-900 sm:inline-block dark:text-zinc-400 dark:hover:text-zinc-100">Features</a>
                <a href="#faq" class="hidden text-sm font-medium text-zinc-600 transition hover:text-zinc-900 sm:inline-block dark:text-zinc-400 dark:hover:text-zinc-100">FAQ</a>
                <a href="https://ternis.link/pages/blog" class="hidden text-sm font-medium text-zinc-600 transition hover:text-zinc-900 sm:inline-block dark:text-zinc-400 dark:hover:text-zinc-100">Blog</a>

                @auth
                    <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="group inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-500 active:scale-[0.98]">
                        <span>Dashboard</span>
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                    </a>
                @else
                    <a href="{{ url('/login') }}" class="group inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-3.5 py-2 text-sm font-semibold text-zinc-800 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-700 dark:hover:bg-zinc-800">
                        <span>Sign in</span>
                        <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main>

        {{-- ── HERO ─────────────────────────────────────────── --}}
        <section class="relative px-4 pt-16 pb-20 sm:px-6 sm:pt-24 sm:pb-28" id="get-started">
            <div class="mx-auto max-w-3xl text-center">

                {{-- Live pill --}}
                <div class="inline-flex items-center gap-2 rounded-full border border-violet-200/80 bg-violet-50/80 px-3.5 py-1 text-xs font-semibold text-violet-700 shadow-xs backdrop-blur-sm dark:border-violet-800/60 dark:bg-violet-950/60 dark:text-violet-300">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping-slow absolute inline-flex h-full w-full rounded-full bg-violet-500 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-violet-600"></span>
                    </span>
                    <span>Real-time click tracking for newsletters &amp; email</span>
                </div>

                <h1 class="mt-6 font-display text-4xl font-extrabold tracking-tight text-zinc-900 sm:text-5xl sm:leading-tight lg:text-6xl dark:text-zinc-50">
                    Know exactly what<br class="hidden sm:inline" />
                    <span class="bg-[length:200%_auto] animate-shimmer bg-gradient-to-r from-violet-600 via-indigo-500 to-violet-500 bg-clip-text text-transparent">
                        your readers click.
                    </span>
                </h1>

                <p class="mx-auto mt-5 max-w-xl text-base text-zinc-600 sm:text-lg dark:text-zinc-400">
                    Replace links in your newsletter with <strong class="font-semibold text-zinc-800 dark:text-zinc-200">clicked.at short links</strong> and get instant, privacy-first analytics on every click — referrer, device, time. No pixel, no cookie, no script injected into your email.
                </p>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                    @auth
                        <a href="{{ \App\Support\DomainUrls::dashboard('/links') }}" class="group inline-flex items-center gap-2 rounded-xl bg-violet-600 px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:bg-violet-500 hover:shadow-violet-500/40 active:scale-[0.98]">
                            Open dashboard
                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </a>
                    @else
                        <a href="{{ url('/login') }}" class="group inline-flex items-center gap-2 rounded-xl bg-violet-600 px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:bg-violet-500 hover:shadow-violet-500/40 active:scale-[0.98]">
                            Start tracking — free
                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </a>
                    @endauth
                    <a href="#how-it-works" class="inline-flex items-center rounded-xl border border-zinc-200 bg-white px-6 py-3.5 text-base font-semibold text-zinc-700 shadow-xs transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:bg-zinc-800">
                        See how it works
                    </a>
                </div>

                {{-- Trust chips --}}
                <div class="mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-xs text-zinc-500 dark:text-zinc-500">
                    <span class="flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-emerald-500" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm3.354-9.354a.5.5 0 0 0-.708 0L7 9.293 5.354 7.646a.5.5 0 1 0-.708.708l2 2a.5.5 0 0 0 .708 0l4-4a.5.5 0 0 0 0-.708z"/></svg>
                        No cookies
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-emerald-500" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm3.354-9.354a.5.5 0 0 0-.708 0L7 9.293 5.354 7.646a.5.5 0 1 0-.708.708l2 2a.5.5 0 0 0 .708 0l4-4a.5.5 0 0 0 0-.708z"/></svg>
                        Works with any email tool
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-emerald-500" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm3.354-9.354a.5.5 0 0 0-.708 0L7 9.293 5.354 7.646a.5.5 0 1 0-.708.708l2 2a.5.5 0 0 0 .708 0l4-4a.5.5 0 0 0 0-.708z"/></svg>
                        GDPR-friendly
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-emerald-500" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm3.354-9.354a.5.5 0 0 0-.708 0L7 9.293 5.354 7.646a.5.5 0 1 0-.708.708l2 2a.5.5 0 0 0 .708 0l4-4a.5.5 0 0 0 0-.708z"/></svg>
                        Real-time dashboard
                    </span>
                </div>

            </div>
        </section>

        {{-- ── MOCK ANALYTICS CARD ──────────────────────────── --}}
        <section class="border-y border-zinc-200/80 bg-white/60 py-10 dark:border-zinc-800/60 dark:bg-zinc-900/40" aria-hidden="true">
            <div class="mx-auto max-w-3xl px-4 sm:px-6">
                <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-800 dark:bg-zinc-900">
                    {{-- Card header --}}
                    <div class="flex items-center gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                        <div class="flex gap-1.5">
                            <span class="h-3 w-3 rounded-full bg-red-400"></span>
                            <span class="h-3 w-3 rounded-full bg-amber-400"></span>
                            <span class="h-3 w-3 rounded-full bg-emerald-400"></span>
                        </div>
                        <span class="text-xs font-medium text-zinc-400">clicked.at — Link analytics</span>
                    </div>
                    {{-- Card body --}}
                    <div class="px-5 py-5">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-zinc-400">Issue #47 · &quot;How to grow your list&quot;</p>
                        <div class="space-y-3">
                            {{-- Row --}}
                            @foreach ([
                                ['label' => 'clicked.at/grow-guide',    'clicks' => 1842, 'pct' => 92],
                                ['label' => 'clicked.at/case-study',    'clicks' =>  963, 'pct' => 48],
                                ['label' => 'clicked.at/free-template', 'clicks' =>  714, 'pct' => 36],
                                ['label' => 'clicked.at/podcast-ep12',  'clicks' =>  309, 'pct' => 15],
                            ] as $row)
                            <div class="flex items-center gap-3">
                                <span class="w-44 shrink-0 truncate font-mono text-xs text-violet-600 dark:text-violet-400">{{ $row['label'] }}</span>
                                <div class="flex-1 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800" style="height:8px;">
                                    <div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-indigo-500" style="width:{{ $row['pct'] }}%;"></div>
                                </div>
                                <span class="w-14 shrink-0 text-right text-xs font-semibold tabular-nums text-zinc-700 dark:text-zinc-300">{{ number_format($row['clicks']) }} clicks</span>
                            </div>
                            @endforeach
                        </div>
                        <p class="mt-4 text-right text-xs text-zinc-400">Updated just now ·
                            <span class="text-emerald-500">↑ 23% vs last issue</span>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ── HOW IT WORKS ─────────────────────────────────── --}}
        <section id="how-it-works" class="py-16 sm:py-24">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <div class="text-center">
                    <span class="text-xs font-semibold uppercase tracking-wider text-violet-600 dark:text-violet-400">Simple by design</span>
                    <h2 class="mt-2 font-display text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                        Three steps. That's it.
                    </h2>
                    <p class="mx-auto mt-2 max-w-md text-sm text-zinc-500 dark:text-zinc-400">
                        No SDK, no pixel, no code changes to your email. Works with Substack, beehiiv, Mailchimp, ConvertKit, or raw SMTP.
                    </p>
                </div>

                <div class="mt-14 grid gap-8 sm:grid-cols-3">
                    {{-- Step 1 --}}
                    <div class="relative animate-fade-in-up [animation-delay:100ms] text-center">
                        <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500/15 to-indigo-500/25 text-violet-600 ring-1 ring-violet-500/25 dark:from-violet-950/80 dark:to-indigo-900/60 dark:text-violet-400 dark:ring-violet-500/30">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none">
                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="mb-2 text-3xl font-bold text-zinc-200 dark:text-zinc-700 select-none" aria-hidden="true">01</div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Wrap your links</h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                            Create a tracked short link on clicked.at for every URL you plan to include in your newsletter. Custom slugs, expiry dates, and UTM parameters supported.
                        </p>
                    </div>

                    {{-- Step 2 --}}
                    <div class="relative animate-fade-in-up [animation-delay:200ms] text-center">
                        <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500/15 to-indigo-500/25 text-violet-600 ring-1 ring-violet-500/25 dark:from-violet-950/80 dark:to-indigo-900/60 dark:text-violet-400 dark:ring-violet-500/30">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none">
                                <path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="mb-2 text-3xl font-bold text-zinc-200 dark:text-zinc-700 select-none" aria-hidden="true">02</div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Send your newsletter</h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                            Paste the clicked.at links into your email. No script tag, no tracking pixel, no change to how your sender works. Just different URLs.
                        </p>
                    </div>

                    {{-- Step 3 --}}
                    <div class="relative animate-fade-in-up [animation-delay:300ms] text-center">
                        <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500/15 to-indigo-500/25 text-violet-600 ring-1 ring-violet-500/25 dark:from-violet-950/80 dark:to-indigo-900/60 dark:text-violet-400 dark:ring-violet-500/30">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none">
                                <rect x="3" y="13" width="4" height="8" rx="1.5" fill="currentColor" fill-opacity="0.15" stroke="currentColor" stroke-width="2"/>
                                <rect x="10" y="8" width="4" height="13" rx="1.5" fill="currentColor" fill-opacity="0.25" stroke="currentColor" stroke-width="2"/>
                                <rect x="17" y="4" width="4" height="17" rx="1.5" fill="currentColor" fill-opacity="0.35" stroke="currentColor" stroke-width="2"/>
                                <path d="M4 11L11 6L16 9L21 3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="21" cy="3" r="1.5" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="mb-2 text-3xl font-bold text-zinc-200 dark:text-zinc-700 select-none" aria-hidden="true">03</div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Watch the clicks roll in</h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                            Your dashboard updates in real time as readers click. See per-link totals, referrer breakdown, device split, and hourly trend — all without leaving the browser.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ── FEATURES ─────────────────────────────────────── --}}
        <section id="features" class="border-t border-zinc-200/80 bg-zinc-100/50 py-16 sm:py-24 dark:border-zinc-800/80 dark:bg-zinc-900/40">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <div class="text-center">
                    <span class="text-xs font-semibold uppercase tracking-wider text-violet-600 dark:text-violet-400">Built for email senders</span>
                    <h2 class="mt-2 font-display text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                        Everything you need to know about your links
                    </h2>
                </div>

                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">

                    {{-- Feature cards --}}
                    @foreach ([
                        [
                            'title' => 'Real-time click counts',
                            'body'  => 'Clicks register within seconds of a reader following a link. No hourly batch jobs — the number you see is the number that happened.',
                            'color' => 'violet',
                            'icon'  => '<path d="M13 10V3L4 14h7v7l9-11h-7z" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" fill="currentColor" fill-opacity="0.15"/>',
                        ],
                        [
                            'title' => 'Referrer breakdown',
                            'body'  => 'See which email client or device your readers use. Understand whether Gmail or mobile gets the most clicks on a given issue.',
                            'color' => 'indigo',
                            'icon'  => '<circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2.2"/><path d="M12 2v3M12 19v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M2 12h3M19 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
                        ],
                        [
                            'title' => 'Per-link privacy',
                            'body'  => 'Readers\' IPs are immediately SHA-256 hashed and never stored in plain text. No fingerprinting, no cross-site tracking, no ad network involvement.',
                            'color' => 'emerald',
                            'icon'  => '<path d="M12 3.25L4.5 6.75V11.5C4.5 16.5 7.7 21.1 12 22.25C16.3 21.1 19.5 16.5 19.5 11.5V6.75L12 3.25Z" fill="currentColor" fill-opacity="0.15" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 12.5L11 14.5L15 9.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>',
                        ],
                        [
                            'title' => 'Custom slugs & branding',
                            'body'  => 'Make links meaningful. Use <code class="rounded bg-zinc-200 px-1 font-mono text-xs dark:bg-zinc-700">clicked.at/jun-offers</code> instead of a random string. Or point a custom domain at the network.',
                            'color' => 'amber',
                            'icon'  => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>',
                        ],
                        [
                            'title' => 'UTM parameter pass-through',
                            'body'  => 'Append UTM tags to your destination URL so your Google Analytics, Plausible, or Fathom data still sees the full campaign context — alongside clicked.at\'s own stats.',
                            'color' => 'sky',
                            'icon'  => '<path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>',
                        ],
                        [
                            'title' => 'API for automation',
                            'body'  => 'Create links programmatically with the REST API. Automate link generation in your newsletter workflow — one key, all your campaigns.',
                            'color' => 'rose',
                            'icon'  => '<path d="M8 9l3 3-3 3M13 15h3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="2.2"/>',
                        ],
                    ] as $i => $feat)
                    @php $delay = ($i + 1) * 80; @endphp
                    <div class="group animate-fade-in-up rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700"
                         style="animation-delay: {{ $delay }}ms">
                        <div class="flex items-center gap-3">
                            <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl
                                @if($feat['color'] === 'violet')  bg-gradient-to-br from-violet-500/10 to-indigo-500/20 text-violet-600 ring-1 ring-violet-500/20 dark:from-violet-950/60 dark:to-indigo-900/40 dark:text-violet-400 dark:ring-violet-500/30
                                @elseif($feat['color'] === 'indigo') bg-gradient-to-br from-indigo-500/10 to-blue-500/20 text-indigo-600 ring-1 ring-indigo-500/20 dark:from-indigo-950/60 dark:to-blue-900/40 dark:text-indigo-400 dark:ring-indigo-500/30
                                @elseif($feat['color'] === 'emerald') bg-gradient-to-br from-emerald-500/10 to-teal-500/20 text-emerald-600 ring-1 ring-emerald-500/20 dark:from-emerald-950/60 dark:to-teal-900/40 dark:text-emerald-400 dark:ring-emerald-500/30
                                @elseif($feat['color'] === 'amber')  bg-gradient-to-br from-amber-500/10 to-orange-500/20 text-amber-600 ring-1 ring-amber-500/20 dark:from-amber-950/60 dark:to-orange-900/40 dark:text-amber-400 dark:ring-amber-500/30
                                @elseif($feat['color'] === 'sky')    bg-gradient-to-br from-sky-500/10 to-blue-500/20 text-sky-600 ring-1 ring-sky-500/20 dark:from-sky-950/60 dark:to-blue-900/40 dark:text-sky-400 dark:ring-sky-500/30
                                @else                                bg-gradient-to-br from-rose-500/10 to-pink-500/20 text-rose-600 ring-1 ring-rose-500/20 dark:from-rose-950/60 dark:to-pink-900/40 dark:text-rose-400 dark:ring-rose-500/30
                                @endif
                                shadow-xs transition duration-200 group-hover:scale-105 group-hover:shadow-md">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">{!! $feat['icon'] !!}</svg>
                            </div>
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $feat['title'] }}</h3>
                        </div>
                        <p class="mt-3 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">{!! $feat['body'] !!}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ── BLOG PREVIEW ─────────────────────────────────── --}}
        <section class="py-16 sm:py-24">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <div class="flex items-end justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-violet-600 dark:text-violet-400">From the blog</span>
                        <h2 class="mt-1 font-display text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                            Learn how to track smarter
                        </h2>
                    </div>
                    <a href="https://ternis.link/pages/blog" class="hidden shrink-0 text-sm font-semibold text-violet-600 hover:text-violet-500 sm:inline-flex items-center gap-1 dark:text-violet-400 dark:hover:text-violet-300">
                        All posts
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </a>
                </div>

                <div class="mt-8 grid gap-5 sm:grid-cols-3">
                    @foreach ([
                        [
                            'slug'  => 'newsletter-click-tracking-without-cookies',
                            'date'  => 'Oct 2026',
                            'title' => 'Newsletter click tracking without cookies',
                            'desc'  => 'Why redirect-based tracking beats pixel tracking for privacy and deliverability — and how clicked.at implements it.',
                            'tag'   => 'Privacy',
                        ],
                        [
                            'slug'  => 'how-to-track-email-clicks-by-link',
                            'date'  => 'Oct 2026',
                            'title' => 'How to track email clicks by link (not just totals)',
                            'desc'  => 'Most email tools show open rates. clicked.at shows which specific link each reader tapped — a far more useful signal.',
                            'tag'   => 'Analytics',
                        ],
                        [
                            'slug'  => 'utm-parameters-and-short-links-for-newsletters',
                            'date'  => 'Oct 2026',
                            'title' => 'UTM parameters and short links for newsletters',
                            'desc'  => 'How to combine clicked.at\'s per-link stats with UTM tags so your analytics platform and your link dashboard agree.',
                            'tag'   => 'How-to',
                        ],
                    ] as $post)
                    <a href="https://ternis.link/pages/blog/{{ $post['slug'] }}" class="group rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:border-violet-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-violet-700">
                        <div class="mb-3 flex items-center gap-2">
                            <span class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-violet-700 dark:bg-violet-950/60 dark:text-violet-400">{{ $post['tag'] }}</span>
                            <span class="text-xs text-zinc-400">{{ $post['date'] }}</span>
                        </div>
                        <h3 class="text-sm font-semibold leading-snug text-zinc-900 group-hover:text-violet-700 transition dark:text-zinc-100 dark:group-hover:text-violet-400">{{ $post['title'] }}</h3>
                        <p class="mt-2 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $post['desc'] }}</p>
                    </a>
                    @endforeach
                </div>

                <div class="mt-6 text-center sm:hidden">
                    <a href="https://ternis.link/pages/blog" class="text-sm font-semibold text-violet-600 dark:text-violet-400">View all blog posts →</a>
                </div>
            </div>
        </section>

        {{-- ── FAQ ──────────────────────────────────────────── --}}
        <section id="faq" class="border-t border-zinc-200/80 bg-zinc-100/50 py-16 sm:py-24 dark:border-zinc-800/80 dark:bg-zinc-900/40">
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <div class="text-center">
                    <span class="text-xs font-semibold uppercase tracking-wider text-violet-600 dark:text-violet-400">Questions answered</span>
                    <h2 class="mt-2 font-display text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">FAQ</h2>
                    <p class="mx-auto mt-2 max-w-lg text-sm text-zinc-500 dark:text-zinc-400">
                        Everything about tracking clicks in newsletters and emails with clicked.at.
                    </p>
                </div>

                <div class="mt-10 space-y-3" x-data="{ active: null }">
                    @foreach ([
                        [1, 'How does clicked.at track newsletter clicks?', 'You replace links in your newsletter with clicked.at short links. When a reader clicks, the request hits our server first — we record the timestamp, referrer, and device — then instantly redirect to the destination. No pixel, no cookie, no script injected into your email.'],
                        [2, 'Do I need to change my email provider?',       'No. clicked.at works with any email provider or newsletter tool — Mailchimp, Substack, ConvertKit, beehiiv, or plain text. Just swap the URLs before you hit send.'],
                        [3, 'Is click tracking GDPR-compliant?',            'Yes. We never store raw IP addresses — only a one-way SHA-256 hash used for deduplication. No advertising cookies, no third-party data sharing. Your readers\' privacy is protected at the infrastructure level, not just the policy level.'],
                        [4, 'Can I use my own domain for tracked links?',   'Yes. Members on eligible plans can point a custom hostname (e.g. go.yourbrand.com) at the network. Every tracked link then carries your domain, not ours.'],
                        [5, 'What data does clicked.at collect per click?', 'Timestamp, referring domain, browser family, OS, and device type. Raw IPs are immediately hashed and discarded. No page content, no user identity, no cross-site profiling.'],
                        [6, 'Does it work with Substack / beehiiv?',        'Yes. Both platforms let you write your own links in the body. Paste clicked.at links where your destination URLs would normally go. Substack\'s own link tracker and ours are independent — both will record separately.'],
                    ] as [$n, $q, $a])
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
                        <button @click="active = active === {{ $n }} ? null : {{ $n }}" type="button" class="group flex w-full items-center justify-between text-left">
                            <h3 class="font-semibold text-zinc-900 transition group-hover:text-violet-600 dark:text-zinc-100 dark:group-hover:text-violet-400">{{ $q }}</h3>
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 transition-all duration-200 group-hover:bg-violet-50 group-hover:text-violet-600 dark:bg-zinc-800 dark:text-zinc-400 dark:group-hover:bg-violet-950/60 dark:group-hover:text-violet-400">
                                <svg :class="active === {{ $n }} ? 'rotate-180 text-violet-600 dark:text-violet-400' : ''" class="h-4 w-4 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                        <div x-show="active === {{ $n }}" x-transition x-cloak>
                            <p class="mt-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $a }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ── CTA ──────────────────────────────────────────── --}}
        <section class="px-4 py-16 sm:px-6">
            <div class="relative mx-auto max-w-4xl overflow-hidden rounded-3xl border border-zinc-200 bg-gradient-to-br from-zinc-900 via-zinc-900 to-violet-950 p-8 text-center text-white shadow-xl sm:p-14 dark:border-zinc-800">
                {{-- Ambient mesh --}}
                <svg class="pointer-events-none absolute inset-0 -z-0 h-full w-full stroke-white/[0.06] [mask-image:radial-gradient(85%_85%_at_50%_40%,white,transparent)]" viewBox="0 0 800 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="cta-mesh" width="36" height="36" patternUnits="userSpaceOnUse">
                            <path d="M36 0H0V36" fill="none" stroke-width="1"/>
                            <circle cx="36" cy="36" r="1.5" fill="white" fill-opacity="0.15"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#cta-mesh)" />
                </svg>
                {{-- Violet glow --}}
                <div class="pointer-events-none absolute -top-20 left-1/2 -translate-x-1/2 h-40 w-96 rounded-full bg-violet-600/25 blur-3xl"></div>

                <div class="relative z-10">
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-violet-500/40 bg-violet-500/10 px-3.5 py-1 text-xs font-semibold text-violet-300">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping-slow absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-violet-400"></span>
                        </span>
                        Free to start. No credit card.
                    </div>

                    <h2 class="font-display text-2xl font-bold tracking-tight sm:text-4xl">
                        Ready to know what<br class="hidden sm:inline"> your readers click?
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-zinc-400 sm:text-base">
                        Sign in with your ternis.link account and create your first tracked link in under a minute. Paste it into your next issue. Watch the clicks arrive.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                        <a href="{{ url('/login') }}" class="group inline-flex items-center gap-2 rounded-xl bg-violet-600 px-7 py-3.5 font-semibold text-white shadow-md shadow-violet-600/30 transition hover:bg-violet-500 active:scale-[0.98]">
                            Get started — it's free
                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </a>
                        <a href="https://docs.ternis.link" class="inline-flex items-center rounded-xl border border-zinc-700 bg-zinc-800/80 px-6 py-3.5 font-semibold text-zinc-300 transition hover:bg-zinc-800 hover:text-white">
                            Read the API docs
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    {{-- ── FOOTER ───────────────────────────────────────── --}}
    <footer class="border-t border-zinc-200 bg-white py-10 text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400">
        <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-6 px-4 text-center sm:flex-row sm:px-6 sm:text-left">
            <div class="flex items-center gap-2 flex-wrap justify-center sm:justify-start">
                <span class="inline-flex items-center gap-1.5 font-semibold text-zinc-800 dark:text-zinc-200">
                    <svg class="h-3.5 w-3.5 text-violet-600" viewBox="0 0 24 24" fill="none">
                        <path d="M9 3.5V2M5.06 5.06L4 4M3.5 9H2M5.06 12.94L4 14M12.94 5.06L14 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M9 9L20.5 14.5L14.5 16L12.5 22L9 9Z" fill="currentColor" fill-opacity="0.2" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    </svg>
                    clicked.at
                </span>
                <span class="text-zinc-400 dark:text-zinc-600">·</span>
                <span class="text-xs">&copy; {{ date('Y') }} Ternis — part of the <a href="https://ternis.link" class="hover:text-zinc-800 dark:hover:text-zinc-200">ternis.link</a> network</span>
            </div>
            <nav class="flex flex-wrap items-center justify-center gap-5 text-xs" aria-label="Footer links">
                <a href="https://ternis.link/pages/legal/privacy" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">Privacy</a>
                <a href="https://ternis.link/pages/legal/terms" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">Terms</a>
                <a href="https://ternis.dev/en/legal/imprint" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">Imprint</a>
                <a href="https://ternis.link/pages/blog" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">Blog</a>
                <a href="https://docs.ternis.link" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">API Docs</a>
                <a href="https://ternis.link/pages/stats" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">Network stats</a>
            </nav>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
