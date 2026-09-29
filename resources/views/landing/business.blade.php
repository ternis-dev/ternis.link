@php
$stats = $stats ?? \App\Support\NetworkStats::overview();

$faqs = [
    [
        'q' => 'Why does Ternis maintain href.re separately from href.nz and meinlink.at?',
        'a' => 'href.nz and meinlink.at are public, anonymous shorteners designed for quick sharing with automated rate limits and temporary slugs. In contrast, <strong>href.re</strong> is the dedicated, verified short-link namespace reserved strictly for official Ternis business, corporate operations, customer invoices, and partner communications. Public guest creation is disabled to prevent phishing and maintain a spotless domain reputation.',
    ],
    [
        'q' => 'Can anyone shorten a link on href.re as an anonymous guest?',
        'a' => 'No. Public guest shortening is strictly prohibited on href.re. Only authenticated organization members via Ternis Auth SSO or provisioned enterprise API keys can create, update, or manage redirects on this domain. Casual and guest users are redirected to <a href="https://href.nz" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">href.nz</a> or <a href="https://meinlink.at" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">meinlink.at</a>.',
    ],
    [
        'q' => 'I received an href.re link in an email or SMS. How do I know it is legitimate?',
        'a' => 'All href.re redirects originate from verified Ternis infrastructure. Every link enforces modern TLS 1.3 encryption, resolves directly to its destination without intermediary advertising or interstitial cloaking, and is continuously audited. You can also inspect any slug using our preview sandbox or verification tool above before navigating to the destination.',
    ],
    [
        'q' => 'How does href.re protect recipient privacy and comply with GDPR?',
        'a' => 'href.re is built from the ground up for strict privacy compliance. When a redirect is executed, our edge servers never store raw IP addresses. Client IPs are immediately converted into one-way cryptographic SHA-256 hashes for anti-abuse counters and discarded. We never drop third-party advertising pixels, tracking cookies, or browser fingerprinting scripts.',
    ],
    [
        'q' => 'Can our automated services or billing pipelines generate href.re links via API?',
        'a' => 'Yes. Authorized services can provision short links programmatically via the versioned REST API at <code>links.t-api.de/v1/links</code> using scoped Bearer API tokens. Automated integrations receive sub-millisecond JSON responses, automatic QR code endpoints, and real-time webhook attribution. Consult the <a href="https://docs.ternis.link/api" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">API documentation</a> for implementation guides.',
    ],
    [
        'q' => 'What happens when a business link needs to be deactivated or reaches its expiration?',
        'a' => 'Business links can be configured with explicit scheduled expiration timestamps or deactivated manually from the dashboard. Once deactivated, the redirect immediately ceases resolving and returns an official retirement notification. To preserve historical audit trails and financial reporting integrity, click aggregates and creation timestamps remain permanently archived in your analytics.',
    ],
];

$faqJsonLd = array_map(fn ($faq) => [
    '@type' => 'Question',
    'name' => $faq['q'],
    'acceptedAnswer' => [
        '@type' => 'Answer',
        'text' => strip_tags($faq['a']),
    ],
], $faqs);
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>href.re — Official Business Link Infrastructure | Ternis</title>
    <meta name="description" content="href.re is the dedicated, verified short-link infrastructure for official Ternis business, corporate operations, transactional communications, and partner integrations. Zero spam, zero ad trackers, sub-millisecond edge resolution.">
    <meta name="keywords" content="href.re, official shortener, enterprise link management, verified redirects, secure short links, ternis business">
    <meta name="author" content="Ternis">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <link rel="canonical" href="https://href.re/">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="href.re">
    <meta property="og:title" content="href.re — Official Business Link Infrastructure">
    <meta property="og:description" content="Verified corporate redirects, privacy-preserving click intelligence, and high-availability edge routing for official Ternis communications.">
    <meta property="og:url" content="https://href.re/">

    {{-- Twitter Cards --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="href.re — Official Business Link Infrastructure">
    <meta name="twitter:description" content="Dedicated enterprise short-link namespace reserved for official Ternis business. Zero spam, zero trackers, sub-millisecond edge redirects.">

    {{-- Structured Data (JSON-LD) --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebSite',
                '@id' => 'https://href.re/#website',
                'url' => 'https://href.re/',
                'name' => 'href.re',
                'description' => 'Official enterprise link shortener and redirect gateway by Ternis.',
                'inLanguage' => 'en',
            ],
            [
                '@type' => 'Organization',
                '@id' => 'https://ternis.dev/#org',
                'name' => 'Ternis',
                'url' => 'https://ternis.dev',
                'sameAs' => ['https://ternis.link', 'https://ternis.net'],
            ],
            [
                '@type' => 'Service',
                '@id' => 'https://href.re/#service',
                'name' => 'href.re Business Link Gateway',
                'serviceType' => 'Enterprise URL Redirection & Telemetry',
                'provider' => ['@id' => 'https://ternis.dev/#org'],
                'description' => 'Dedicated short-link gateway for official corporate operations, invoices, notifications, and partner portals.',
                'termsOfService' => 'https://ternis.link/pages/legal/terms',
            ],
            [
                '@type' => 'FAQPage',
                '@id' => 'https://href.re/#faq',
                'mainEntity' => $faqJsonLd,
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <script>
        try {
            const stored = localStorage.getItem('tl-theme');
            if (stored === 'light' || (stored !== 'dark' && matchMedia('(prefers-color-scheme: light)').matches)) {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-neutral-50 text-neutral-900 antialiased selection:bg-neutral-900 selection:text-white dark:bg-neutral-950 dark:text-neutral-100 dark:selection:bg-white dark:selection:text-neutral-900">

    {{-- Sticky Enterprise Navigation Header --}}
    <header class="sticky top-0 z-40 w-full border-b border-neutral-200/80 bg-white/85 backdrop-blur-md dark:border-neutral-800/80 dark:bg-neutral-950/85">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5 sm:px-6">
            <div class="flex items-center gap-3">
                <a href="/" class="font-display text-2xl font-bold tracking-tight text-neutral-900 hover:opacity-90 dark:text-white" aria-label="href.re home">
                    href<span>.re</span>
                </a>
                <span class="hidden items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 sm:inline-flex dark:border-emerald-500/30 dark:bg-emerald-950/60 dark:text-emerald-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" aria-hidden="true"></span>
                    Official · Business Gateway
                </span>
            </div>

            <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-neutral-600 dark:text-neutral-400" aria-label="Main Navigation">
                <a href="#why" class="transition-colors hover:text-neutral-900 dark:hover:text-white">Why href.re</a>
                <a href="#verify" class="transition-colors hover:text-neutral-900 dark:hover:text-white">Verify a Link</a>
                <a href="#features" class="transition-colors hover:text-neutral-900 dark:hover:text-white">Capabilities</a>
                <a href="#architecture" class="transition-colors hover:text-neutral-900 dark:hover:text-white">Architecture</a>
                <a href="#use-cases" class="transition-colors hover:text-neutral-900 dark:hover:text-white">Use Cases</a>
                <a href="#faq" class="transition-colors hover:text-neutral-900 dark:hover:text-white">FAQ</a>
                <a href="https://docs.ternis.link/api" class="transition-colors hover:text-neutral-900 dark:hover:text-white" rel="noopener">Docs</a>
            </nav>

            <div class="flex items-center gap-2.5">
                <button
                    type="button"
                    data-theme-toggle
                    class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg border border-neutral-300 text-neutral-600 transition-colors hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-900"
                    aria-label="Toggle color theme"
                >
                    <svg class="icon-moon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                    <svg class="icon-sun h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>
                </button>

                @auth
                    <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/') }}" variant="primary" size="sm">Go to Dashboard</x-ui.button>
                @else
                    <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}" variant="primary" size="sm">Sign In with Ternis Auth</x-ui.button>
                @endauth
            </div>
        </div>
    </header>

    <main class="flex-1">
        {{-- Hero Section --}}
        <section class="relative overflow-hidden px-4 pt-16 pb-20 sm:px-6 sm:pt-24 sm:pb-28">
            {{-- Ambient radial background glow --}}
            <div class="pointer-events-none absolute -top-32 left-1/2 -translate-x-1/2 h-[520px] w-[900px] rounded-full bg-gradient-to-b from-emerald-500/10 via-teal-500/5 to-transparent blur-3xl -z-10" aria-hidden="true"></div>

            <div class="mx-auto max-w-4xl text-center">
                <div class="inline-flex items-center gap-2 rounded-full border border-neutral-300/80 bg-white/80 px-3.5 py-1 text-xs font-medium text-neutral-700 shadow-sm backdrop-blur-xs sm:text-sm dark:border-neutral-700/80 dark:bg-neutral-900/80 dark:text-neutral-300">
                    <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                    <span>Cryptographically Audited · Enterprise-Grade Redirect Gateway</span>
                </div>

                <h1 class="font-display mt-6 text-4xl font-extrabold tracking-tight text-neutral-900 sm:text-6xl dark:text-white">
                    Official links, <span class="text-neutral-400 dark:text-neutral-500">built for business.</span>
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-neutral-600 sm:text-lg dark:text-neutral-300">
                    <strong class="font-semibold text-neutral-900 dark:text-white">href.re</strong> is the dedicated short-link namespace for official Ternis business, corporate operations, transactional communications, and verified partner channels. Every redirect is provisioned by authenticated team accounts, protected against abuse, and delivered with sub-millisecond edge latency.
                </p>

                <div class="mt-9 flex flex-wrap items-center justify-center gap-3">
                    @auth
                        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/') }}" variant="primary" size="lg">
                            <span>Open Dashboard</span>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </x-ui.button>
                    @else
                        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}" variant="primary" size="lg">
                            <span>Sign in with Ternis Auth</span>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
                        </x-ui.button>
                    @endauth
                    <x-ui.button href="#verify" variant="secondary" size="lg">
                        <svg class="h-4 w-4 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
                        <span>Verify an href.re Link</span>
                    </x-ui.button>
                    <x-ui.button href="https://docs.ternis.link/api" variant="ghost" size="lg" target="_blank" rel="noopener">
                        <span>Developer API Guide ↗</span>
                    </x-ui.button>
                </div>

                {{-- Trust Signals Pills --}}
                <ul class="mt-10 flex flex-wrap justify-center gap-2.5 text-xs font-medium text-neutral-600 dark:text-neutral-400">
                    <li class="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-white px-3.5 py-1.5 shadow-2xs dark:border-neutral-800 dark:bg-neutral-900">
                        <svg class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Authenticated senders only</span>
                    </li>
                    <li class="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-white px-3.5 py-1.5 shadow-2xs dark:border-neutral-800 dark:bg-neutral-900">
                        <svg class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Zero anonymous guest rot</span>
                    </li>
                    <li class="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-white px-3.5 py-1.5 shadow-2xs dark:border-neutral-800 dark:bg-neutral-900">
                        <svg class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Zero 3rd-party ad trackers</span>
                    </li>
                    <li class="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-white px-3.5 py-1.5 shadow-2xs dark:border-neutral-800 dark:bg-neutral-900">
                        <svg class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Sub-ms Caddy &amp; Redis edge</span>
                    </li>
                    <li class="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-white px-3.5 py-1.5 shadow-2xs dark:border-neutral-800 dark:bg-neutral-900">
                        <svg class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Immutable audit logs</span>
                    </li>
                </ul>
            </div>
        </section>

        {{-- Live Network Telemetry & Infrastructure Health Banner --}}
        <section class="border-y border-neutral-200 bg-white/70 py-6 backdrop-blur-xs dark:border-neutral-800 dark:bg-neutral-900/50">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-neutral-200 pb-4 dark:border-neutral-800/80">
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="font-display text-sm font-semibold tracking-tight text-neutral-900 dark:text-white">Edge Network Status: Operational</span>
                        <span class="text-xs text-neutral-500 dark:text-neutral-400">· Global TLS 1.3 Active</span>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-neutral-500 dark:text-neutral-400">
                        <span>Target SLA: <strong class="text-neutral-800 dark:text-neutral-200">99.99%</strong></span>
                        <span>·</span>
                        <span>Hot Slug Cache: <strong class="text-neutral-800 dark:text-neutral-200">&lt; 5ms P99</strong></span>
                        <span>·</span>
                        <a href="https://ternis.link/pages/stats" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">View Network Stats →</a>
                    </div>
                </div>

                <dl class="mt-6 grid grid-cols-2 gap-4 text-center sm:grid-cols-4">
                    <div class="rounded-xl border border-neutral-200/80 bg-neutral-50/50 p-4 dark:border-neutral-800/80 dark:bg-neutral-950/40">
                        <dd class="font-display text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">
                            {{ number_format($stats['total_links'] ?? 0) }}
                        </dd>
                        <dt class="mt-1 text-xs font-medium text-neutral-500 dark:text-neutral-400">
                            Short links provisioned
                        </dt>
                    </div>

                    <div class="rounded-xl border border-neutral-200/80 bg-neutral-50/50 p-4 dark:border-neutral-800/80 dark:bg-neutral-950/40">
                        <dd class="font-display text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">
                            {{ number_format($stats['total_clicks'] ?? 0) }}
                        </dd>
                        <dt class="mt-1 text-xs font-medium text-neutral-500 dark:text-neutral-400">
                            Redirects safely routed
                        </dt>
                    </div>

                    <div class="rounded-xl border border-neutral-200/80 bg-neutral-50/50 p-4 dark:border-neutral-800/80 dark:bg-neutral-950/40">
                        <dd class="font-display text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">
                            {{ number_format($stats['links_today'] ?? 0) }}
                        </dd>
                        <dt class="mt-1 text-xs font-medium text-neutral-500 dark:text-neutral-400">
                            Created in last 24h
                        </dt>
                    </div>

                    <div class="rounded-xl border border-neutral-200/80 bg-neutral-50/50 p-4 dark:border-neutral-800/80 dark:bg-neutral-950/40">
                        <dd class="font-display text-3xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400">
                            0
                        </dd>
                        <dt class="mt-1 text-xs font-medium text-neutral-500 dark:text-neutral-400">
                            Ad trackers or spyware pixels
                        </dt>
                    </div>
                </dl>
            </div>
        </section>

        {{-- Interactive Link Verification & Inspector Section --}}
        <section id="verify" class="scroll-mt-12 py-16 sm:py-24">
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <div class="text-center">
                    <x-ui.badge tone="solid">Recipient Safety &amp; Trust</x-ui.badge>
                    <h2 class="font-display mt-3 text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl dark:text-white">
                        Inspect &amp; verify an href.re link
                    </h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm text-neutral-600 dark:text-neutral-400">
                        Did you receive an <code>href.re/slug</code> link in an invoice, contract, SMS, or partner notification? Verify the slug format and check destination safety before opening.
                    </p>
                </div>

                {{-- Interactive Inspector Widget --}}
                <div class="mt-8 rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm sm:p-8 dark:border-neutral-800 dark:bg-neutral-900">
                    <form id="verifier-form" onsubmit="event.preventDefault(); inspectSlug();" class="space-y-4">
                        <div>
                            <label for="inspect-input" class="block text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                Enter href.re link or slug code
                            </label>
                            <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                                <div class="relative flex-1">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-medium text-neutral-400">
                                        href.re/
                                    </span>
                                    <input
                                        type="text"
                                        id="inspect-input"
                                        name="slug"
                                        placeholder="inv-2026-0891"
                                        autocomplete="off"
                                        spellcheck="false"
                                        class="block w-full rounded-xl border border-neutral-300 bg-neutral-50/50 py-2.5 pr-4 pl-19 text-sm font-mono text-neutral-900 placeholder:text-neutral-400 focus:border-neutral-900 focus:bg-white focus:outline-hidden focus:ring-1 focus:ring-neutral-900 dark:border-neutral-700 dark:bg-neutral-950/60 dark:text-white dark:placeholder:text-neutral-500 dark:focus:border-white dark:focus:ring-white"
                                        oninput="liveValidateSlug(this.value)"
                                    >
                                </div>
                                <x-ui.button type="submit" variant="primary" size="md" class="shrink-0">
                                    <span>Inspect Link</span>
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                </x-ui.button>
                            </div>
                        </div>

                        {{-- Live Validation Status Banner --}}
                        <div id="inspector-output" class="rounded-xl border border-neutral-200/80 bg-neutral-50 p-4 text-xs sm:text-sm text-neutral-600 dark:border-neutral-800/80 dark:bg-neutral-950/60 dark:text-neutral-400">
                            <div class="flex items-start gap-3">
                                <div id="status-icon" class="mt-0.5 text-neutral-400">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                </div>
                                <div class="flex-1">
                                    <div id="status-title" class="font-medium text-neutral-800 dark:text-neutral-200">
                                        Awaiting input
                                    </div>
                                    <div id="status-desc" class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                        Type or paste any href.re slug above. href.re short links use alphanumeric characters, hyphens, and underscores (e.g. <code>inv-2026-0891</code>).
                                    </div>
                                    <div id="status-actions" class="mt-3 hidden flex-wrap gap-2">
                                        <a id="btn-open-preview" href="#" class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-xs font-semibold text-neutral-900 transition hover:bg-neutral-100 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100 dark:hover:bg-neutral-800">
                                            <span>Open in Safe Preview Sandbox ↗</span>
                                        </a>
                                        <a id="btn-open-direct" href="#" class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-900 bg-neutral-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-neutral-700 dark:border-white dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200">
                                            <span>Follow Redirect ↗</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Security Invariants Checklist --}}
                    <div class="mt-8 border-t border-neutral-200 pt-6 dark:border-neutral-800">
                        <div class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                            href.re Cryptographic &amp; Security Standards
                        </div>
                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="flex items-start gap-2.5 rounded-lg border border-neutral-200/60 p-3 text-xs dark:border-neutral-800/60">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                <div>
                                    <strong class="font-medium text-neutral-900 dark:text-white">Strict TLS 1.3 Encryption:</strong>
                                    <span class="text-neutral-500 dark:text-neutral-400">All traffic is encrypted in transit with automated certificate rotation via Caddy.</span>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5 rounded-lg border border-neutral-200/60 p-3 text-xs dark:border-neutral-800/60">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                <div>
                                    <strong class="font-medium text-neutral-900 dark:text-white">Zero Interstitial Cloaking:</strong>
                                    <span class="text-neutral-500 dark:text-neutral-400">href.re links never present interstitial ad pages, malware wrappers, or deceptive gateway countdowns.</span>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5 rounded-lg border border-neutral-200/60 p-3 text-xs dark:border-neutral-800/60">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                <div>
                                    <strong class="font-medium text-neutral-900 dark:text-white">Whitelisted Protocols:</strong>
                                    <span class="text-neutral-500 dark:text-neutral-400">Destinations are strictly verified against protocol schemes, blocking data URIs, javascript, and local file vectors.</span>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5 rounded-lg border border-neutral-200/60 p-3 text-xs dark:border-neutral-800/60">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                <div>
                                    <strong class="font-medium text-neutral-900 dark:text-white">One-Way IP Hashing:</strong>
                                    <span class="text-neutral-500 dark:text-neutral-400">Visitor IP addresses are instantly converted into irreversible SHA-256 digests. Raw IPs are never stored in databases.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- "Why href.re?" — Enterprise vs Generic Shorteners Comparison --}}
        <section id="why" class="scroll-mt-12 border-t border-neutral-200 bg-white py-16 sm:py-24 dark:border-neutral-800 dark:bg-neutral-900/60">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <div class="text-center">
                    <x-ui.badge tone="neutral">Strategic Differentiation</x-ui.badge>
                    <h2 class="font-display mt-3 text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl dark:text-white">
                        Why href.re instead of generic shorteners?
                    </h2>
                    <p class="mx-auto mt-3 max-w-2xl text-base text-neutral-600 dark:text-neutral-400">
                        Generic consumer link shorteners suffer from widespread abuse, spam filter penalties, and invasive ad trackers. Here is how href.re guarantees enterprise-grade delivery.
                    </p>
                </div>

                <div class="mt-12 overflow-x-auto rounded-2xl border border-neutral-200 bg-white shadow-2xs dark:border-neutral-800 dark:bg-neutral-950">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-neutral-200 bg-neutral-50/80 text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-300">
                            <tr>
                                <th scope="col" class="py-4 pr-4 pl-6">Criterion</th>
                                <th scope="col" class="px-4 py-4 text-emerald-700 dark:text-emerald-400">href.re (Business)</th>
                                <th scope="col" class="px-4 py-4 text-neutral-500">Generic Shorteners (bit.ly, tinyurl)</th>
                                <th scope="col" class="py-4 pr-6 pl-4 text-neutral-500">href.nz (Casual Guest)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200/80 dark:divide-neutral-800/80">
                            <tr>
                                <td class="py-4 pr-4 pl-6 font-medium text-neutral-900 dark:text-white">
                                    Creation Access
                                </td>
                                <td class="px-4 py-4 text-neutral-900 font-semibold dark:text-white">
                                    <span class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                        Authenticated Team &amp; API only
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-neutral-500 dark:text-neutral-400">
                                    Anonymous, unvetted public submissions
                                </td>
                                <td class="py-4 pr-6 pl-4 text-neutral-500 dark:text-neutral-400">
                                    Guest form with bot check &amp; quotas
                                </td>
                            </tr>
                            <tr>
                                <td class="py-4 pr-4 pl-6 font-medium text-neutral-900 dark:text-white">
                                    Domain Reputation
                                </td>
                                <td class="px-4 py-4 text-neutral-900 font-semibold dark:text-white">
                                    <span class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                        Pristine; Whitelisted in spam filters
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-neutral-500 dark:text-neutral-400">
                                    High risk; frequently blacklisted by Spamhaus
                                </td>
                                <td class="py-4 pr-6 pl-4 text-neutral-500 dark:text-neutral-400">
                                    Monitored community reputation
                                </td>
                            </tr>
                            <tr>
                                <td class="py-4 pr-4 pl-6 font-medium text-neutral-900 dark:text-white">
                                    Telemetry &amp; Privacy
                                </td>
                                <td class="px-4 py-4 text-neutral-900 font-semibold dark:text-white">
                                    <span class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                        100% GDPR compliant; SHA-256 IP hash
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-neutral-500 dark:text-neutral-400">
                                    Ad pixels, 3rd-party cookies, tracking IDs
                                </td>
                                <td class="py-4 pr-6 pl-4 text-neutral-500 dark:text-neutral-400">
                                    Privacy-first; IP hash &amp; basic counts
                                </td>
                            </tr>
                            <tr>
                                <td class="py-4 pr-4 pl-6 font-medium text-neutral-900 dark:text-white">
                                    Link Stability &amp; Rot
                                </td>
                                <td class="px-4 py-4 text-neutral-900 font-semibold dark:text-white">
                                    <span class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                        Permanent invariants for contracts
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-neutral-500 dark:text-neutral-400">
                                    Pruned or hijacked after inactivity
                                </td>
                                <td class="py-4 pr-6 pl-4 text-neutral-500 dark:text-neutral-400">
                                    Reliable default retention
                                </td>
                            </tr>
                            <tr>
                                <td class="py-4 pr-4 pl-6 font-medium text-neutral-900 dark:text-white">
                                    Edge Latency
                                </td>
                                <td class="px-4 py-4 text-neutral-900 font-semibold dark:text-white">
                                    <span class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                        &lt; 5ms (Caddy + Redis in-memory)
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-neutral-500 dark:text-neutral-400">
                                    150ms–400ms (multi-hop ad redirects)
                                </td>
                                <td class="py-4 pr-6 pl-4 text-neutral-500 dark:text-neutral-400">
                                    Fast edge caching
                                </td>
                            </tr>
                            <tr>
                                <td class="py-4 pr-4 pl-6 font-medium text-neutral-900 dark:text-white">
                                    Programmatic Automation
                                </td>
                                <td class="px-4 py-4 text-neutral-900 font-semibold dark:text-white">
                                    <span class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                        REST API v1 + scoped Bearer keys
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-neutral-500 dark:text-neutral-400">
                                    Expensive enterprise plans ($350+/mo)
                                </td>
                                <td class="py-4 pr-6 pl-4 text-neutral-500 dark:text-neutral-400">
                                    Public rate-limited guest endpoint
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- Core Pillars of href.re Infrastructure --}}
        <section id="features" class="scroll-mt-12 py-16 sm:py-24">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="text-center">
                    <x-ui.badge tone="solid">Core Capabilities</x-ui.badge>
                    <h2 class="font-display mt-3 text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl dark:text-white">
                        Built for mission-critical operations
                    </h2>
                    <p class="mx-auto mt-3 max-w-2xl text-base text-neutral-600 dark:text-neutral-400">
                        Everything you need to deploy, monitor, and automate trusted business redirects across your organization.
                    </p>
                </div>

                <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <x-ui.card class="flex flex-col justify-between">
                        <div>
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-neutral-900 text-white dark:bg-white dark:text-neutral-900">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </div>
                            <h3 class="font-display mt-4 text-lg font-bold text-neutral-900 dark:text-white">
                                Cryptographically Audited
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                                No guest submission forms. Only verified members authenticated through Ternis Auth SSO or scoped Bearer API tokens can register redirects on <code>href.re</code>.
                            </p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-800 text-xs font-medium text-neutral-500">
                            Zero phishing exposure
                        </div>
                    </x-ui.card>

                    <x-ui.card class="flex flex-col justify-between">
                        <div>
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-neutral-900 text-white dark:bg-white dark:text-neutral-900">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16"/><path d="M7 20v-6M12 20V6M17 20v-9"/></svg>
                            </div>
                            <h3 class="font-display mt-4 text-lg font-bold text-neutral-900 dark:text-white">
                                Privacy-First Telemetry
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                                Capture essential business metrics — referrers, geographic regions, client platforms, and daily clicks — without annoying cookie banners or privacy compliance headaches.
                            </p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-800 text-xs font-medium text-neutral-500">
                            One-way SHA-256 IP hashing
                        </div>
                    </x-ui.card>

                    <x-ui.card class="flex flex-col justify-between">
                        <div>
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-neutral-900 text-white dark:bg-white dark:text-neutral-900">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                            </div>
                            <h3 class="font-display mt-4 text-lg font-bold text-neutral-900 dark:text-white">
                                Sub-Millisecond Edge Resolution
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                                Powered by Caddy HTTP/3 reverse proxy with hot-slug Redis memory caching. Hits resolve in single-digit milliseconds with zero intermediate tracking bounces.
                            </p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-800 text-xs font-medium text-neutral-500">
                            Automatic cache invalidation
                        </div>
                    </x-ui.card>

                    <x-ui.card class="flex flex-col justify-between">
                        <div>
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-neutral-900 text-white dark:bg-white dark:text-neutral-900">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                            </div>
                            <h3 class="font-display mt-4 text-lg font-bold text-neutral-900 dark:text-white">
                                Semantic Corporate Slugs
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                                Use structured, memorable paths like <code>href.re/inv-2026-0891</code> or <code>href.re/partner-briefing</code>. Instill immediate confidence in clients, prospects, and partners.
                            </p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-800 text-xs font-medium text-neutral-500">
                            Deterministic slug classifier
                        </div>
                    </x-ui.card>

                    <x-ui.card class="flex flex-col justify-between">
                        <div>
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-neutral-900 text-white dark:bg-white dark:text-neutral-900">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                            <h3 class="font-display mt-4 text-lg font-bold text-neutral-900 dark:text-white">
                                Lifecycle &amp; Expiry Governance
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                                Set exact expiration deadlines for limited-time offers or maintenance notices. When expired, links retire gracefully while preserving historical click analytics.
                            </p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-800 text-xs font-medium text-neutral-500">
                            Scheduled cleanup daemon
                        </div>
                    </x-ui.card>

                    <x-ui.card class="flex flex-col justify-between">
                        <div>
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-neutral-900 text-white dark:bg-white dark:text-neutral-900">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                            </div>
                            <h3 class="font-display mt-4 text-lg font-bold text-neutral-900 dark:text-white">
                                Automated API Pipelines
                            </h3>
                            <p class="mt-2 text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                                Integrate into billing systems, notification engines, and CI/CD pipelines via <code>links.t-api.de/v1/links</code>. Issue and rotate links programmatically with full attribution.
                            </p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-neutral-100 dark:border-neutral-800 text-xs font-medium text-neutral-500">
                            Versioned REST API v1
                        </div>
                    </x-ui.card>
                </div>
            </div>
        </section>

        {{-- Enterprise Use Cases --}}
        <section id="use-cases" class="scroll-mt-12 border-t border-neutral-200 bg-neutral-100/60 py-16 sm:py-24 dark:border-neutral-800 dark:bg-neutral-950">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="text-center">
                    <x-ui.badge tone="neutral">Real-World Applications</x-ui.badge>
                    <h2 class="font-display mt-3 text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl dark:text-white">
                        Where business teams deploy href.re
                    </h2>
                    <p class="mx-auto mt-3 max-w-xl text-base text-neutral-600 dark:text-neutral-400">
                        From financial transactions to transactional alerts, href.re ensures reliable message delivery and brand authenticity.
                    </p>
                </div>

                <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-2xs sm:p-8 dark:border-neutral-800 dark:bg-neutral-900">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 font-bold">1</span>
                            <h3 class="font-display text-xl font-bold text-neutral-900 dark:text-white">Financial Invoices &amp; Billing</h3>
                        </div>
                        <p class="mt-3 text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                            Embed short, tamper-evident links into PDF invoices, accounting summaries, and payment reminders. Because href.re has a verified reputation, enterprise mail servers never classify transactional emails as spam.
                        </p>
                        <div class="mt-4 rounded-lg bg-neutral-50 p-3 font-mono text-xs text-neutral-700 dark:bg-neutral-950 dark:text-neutral-300">
                            Example: <code>https://href.re/inv-2026-0891</code>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-2xs sm:p-8 dark:border-neutral-800 dark:bg-neutral-900">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 font-bold">2</span>
                            <h3 class="font-display text-xl font-bold text-neutral-900 dark:text-white">Transactional SMS &amp; Messaging</h3>
                        </div>
                        <p class="mt-3 text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                            Character limits in SMS and WhatsApp notifications make long URLs expensive and awkward. href.re shortens one-time verification links and tracking links into a crisp, recognizable 15-character address.
                        </p>
                        <div class="mt-4 rounded-lg bg-neutral-50 p-3 font-mono text-xs text-neutral-700 dark:bg-neutral-950 dark:text-neutral-300">
                            Example: <code>https://href.re/track-98104</code>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-2xs sm:p-8 dark:border-neutral-800 dark:bg-neutral-900">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 font-bold">3</span>
                            <h3 class="font-display text-xl font-bold text-neutral-900 dark:text-white">Legal Documents &amp; Contracts</h3>
                        </div>
                        <p class="mt-3 text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                            Printed agreements, board decks, and NDAs need references that never decay or rot. href.re links can be guaranteed permanent invariants so physical documents remain actionable for decades.
                        </p>
                        <div class="mt-4 rounded-lg bg-neutral-50 p-3 font-mono text-xs text-neutral-700 dark:bg-neutral-950 dark:text-neutral-300">
                            Example: <code>https://href.re/sla-q3-2026</code>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-2xs sm:p-8 dark:border-neutral-800 dark:bg-neutral-900">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 font-bold">4</span>
                            <h3 class="font-display text-xl font-bold text-neutral-900 dark:text-white">B2B Partner Portals &amp; Media</h3>
                        </div>
                        <p class="mt-3 text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                            Supply partners and journalists with clean, audited links that track traffic sources without polluting destination query strings with invasive third-party ad parameters.
                        </p>
                        <div class="mt-4 rounded-lg bg-neutral-50 p-3 font-mono text-xs text-neutral-700 dark:bg-neutral-950 dark:text-neutral-300">
                            Example: <code>https://href.re/partner-kit</code>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Zero-Trust Redirection Architecture Pipeline --}}
        <section id="architecture" class="scroll-mt-12 py-16 sm:py-24">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <div class="text-center">
                    <x-ui.badge tone="solid">Engineering &amp; Security</x-ui.badge>
                    <h2 class="font-display mt-3 text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl dark:text-white">
                        Zero-Trust Redirection Pipeline
                    </h2>
                    <p class="mx-auto mt-3 max-w-xl text-base text-neutral-600 dark:text-neutral-400">
                        How every href.re request is validated, cached, and dispatched in single-digit milliseconds.
                    </p>
                </div>

                <div class="mt-12 grid grid-cols-1 gap-4 sm:grid-cols-5">
                    <div class="flex flex-col items-center rounded-xl border border-neutral-200 bg-white p-5 text-center shadow-2xs dark:border-neutral-800 dark:bg-neutral-900">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-neutral-100 font-mono text-xs font-bold text-neutral-900 dark:bg-neutral-800 dark:text-white">01</span>
                        <h4 class="font-display mt-3 font-bold text-sm text-neutral-900 dark:text-white">Ingestion</h4>
                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Ternis Auth SSO or scoped Bearer API token validation</p>
                    </div>

                    <div class="flex flex-col items-center rounded-xl border border-neutral-200 bg-white p-5 text-center shadow-2xs dark:border-neutral-800 dark:bg-neutral-900">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-neutral-100 font-mono text-xs font-bold text-neutral-900 dark:bg-neutral-800 dark:text-white">02</span>
                        <h4 class="font-display mt-3 font-bold text-sm text-neutral-900 dark:text-white">Validation</h4>
                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Deterministic slug check &amp; destination URL scheme filter</p>
                    </div>

                    <div class="flex flex-col items-center rounded-xl border border-neutral-200 bg-white p-5 text-center shadow-2xs dark:border-neutral-800 dark:bg-neutral-900">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-neutral-100 font-mono text-xs font-bold text-neutral-900 dark:bg-neutral-800 dark:text-white">03</span>
                        <h4 class="font-display mt-3 font-bold text-sm text-neutral-900 dark:text-white">Edge Cache</h4>
                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Caddy HTTP/3 reverse proxy + Redis hot-slug in-memory hit</p>
                    </div>

                    <div class="flex flex-col items-center rounded-xl border border-neutral-200 bg-white p-5 text-center shadow-2xs dark:border-neutral-800 dark:bg-neutral-900">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-neutral-100 font-mono text-xs font-bold text-neutral-900 dark:bg-neutral-800 dark:text-white">04</span>
                        <h4 class="font-display mt-3 font-bold text-sm text-neutral-900 dark:text-white">Async Telemetry</h4>
                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Worker queue records referrer &amp; SHA-256 IP hash</p>
                    </div>

                    <div class="flex flex-col items-center rounded-xl border border-neutral-200 bg-white p-5 text-center shadow-2xs dark:border-neutral-800 dark:bg-neutral-900">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 font-mono text-xs font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">05</span>
                        <h4 class="font-display mt-3 font-bold text-sm text-neutral-900 dark:text-white">Destination</h4>
                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Immediate 302 redirect with clean HTTP headers</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Frequently Asked Questions --}}
        <section id="faq" class="scroll-mt-12 border-t border-neutral-200 bg-white py-16 sm:py-24 dark:border-neutral-800 dark:bg-neutral-900/60">
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <div class="text-center">
                    <x-ui.badge tone="neutral">Knowledge Base</x-ui.badge>
                    <h2 class="font-display mt-3 text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl dark:text-white">
                        Frequently asked questions
                    </h2>
                    <p class="mx-auto mt-3 max-w-xl text-base text-neutral-600 dark:text-neutral-400">
                        Everything you need to know about href.re governance, security policies, and integrations.
                    </p>
                </div>

                <div class="mt-12 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ($faqs as $faq)
                        <x-ui.card>
                            <h3 class="font-display text-base font-bold text-neutral-900 dark:text-white">{{ $faq['q'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">{!! $faq['a'] !!}</p>
                        </x-ui.card>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Enterprise Call-To-Action Banner --}}
        <section class="border-t border-neutral-200 bg-neutral-900 py-16 text-white dark:border-neutral-800 dark:bg-neutral-950">
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6">
                <h2 class="font-display text-3xl font-bold tracking-tight sm:text-4xl">
                    Ready to deploy official business links?
                </h2>
                <p class="mx-auto mt-4 max-w-xl text-base text-neutral-300">
                    Sign in to your Ternis account to manage official redirects, review click intelligence, or provision scoped API keys for automated pipelines.
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    @auth
                        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/') }}" variant="primary" size="lg" class="bg-white text-neutral-900 hover:bg-neutral-100">
                            Open Business Dashboard
                        </x-ui.button>
                    @else
                        <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}" variant="primary" size="lg" class="bg-white text-neutral-900 hover:bg-neutral-100">
                            Sign In with Ternis Auth
                        </x-ui.button>
                    @endauth
                    <x-ui.button href="https://docs.ternis.link/api" variant="secondary" size="lg" class="border-neutral-700 bg-neutral-800 text-white hover:bg-neutral-700">
                        Read API Documentation ↗
                    </x-ui.button>
                </div>
                <p class="mt-6 text-xs text-neutral-400">
                    Looking for casual guest shortening? Visit <a href="https://href.nz" class="underline underline-offset-2 hover:text-white">href.nz</a> or <a href="https://meinlink.at" class="underline underline-offset-2 hover:text-white">meinlink.at</a>.
                </p>
            </div>
        </section>
    </main>

    {{-- Enterprise Comprehensive Footer --}}
    <footer class="border-t border-neutral-200 bg-white py-12 text-sm text-neutral-600 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-400">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                <div class="col-span-2 md:col-span-1">
                    <a href="/" class="font-display text-xl font-bold tracking-tight text-neutral-900 dark:text-white">
                        href<span>.re</span>
                    </a>
                    <p class="mt-3 text-xs leading-relaxed text-neutral-500 dark:text-neutral-400">
                        Official business short-link gateway engineered and hosted by <a href="https://ternis.dev" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">ternis.dev</a>.
                    </p>
                    <div class="mt-4 flex items-center gap-2">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-medium text-neutral-600 dark:text-neutral-400">Ternis Infrastructure</span>
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-900 dark:text-white">Network Domains</h3>
                    <ul class="mt-3 space-y-2 text-xs">
                        <li><a href="https://href.re" class="font-semibold text-neutral-900 dark:text-white">href.re (Official Business)</a></li>
                        <li><a href="https://href.nz" class="hover:text-neutral-900 dark:hover:text-white">href.nz (Casual English)</a></li>
                        <li><a href="https://meinlink.at" class="hover:text-neutral-900 dark:hover:text-white">meinlink.at (Casual German)</a></li>
                        <li><a href="https://ternis.link" class="hover:text-neutral-900 dark:hover:text-white">ternis.link (Family &amp; Partners)</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-900 dark:text-white">Developers &amp; Tools</h3>
                    <ul class="mt-3 space-y-2 text-xs">
                        <li><a href="https://docs.ternis.link" class="hover:text-neutral-900 dark:hover:text-white">Documentation Center</a></li>
                        <li><a href="https://docs.ternis.link/api" class="hover:text-neutral-900 dark:hover:text-white">API Reference (v1)</a></li>
                        <li><a href="https://ternis.link/pages/stats" class="hover:text-neutral-900 dark:hover:text-white">Network Telemetry</a></li>
                        <li><a href="https://ternis.link/pages/extension" class="hover:text-neutral-900 dark:hover:text-white">Chrome Extension</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-900 dark:text-white">Legal &amp; Privacy</h3>
                    <ul class="mt-3 space-y-2 text-xs">
                        <li><a href="https://ternis.link/pages/legal/privacy" class="hover:text-neutral-900 dark:hover:text-white">Privacy Policy (GDPR)</a></li>
                        <li><a href="https://ternis.link/pages/legal/terms" class="hover:text-neutral-900 dark:hover:text-white">Terms of Service</a></li>
                        <li><a href="https://ternis.dev/en/legal/imprint" class="hover:text-neutral-900 dark:hover:text-white">Legal Imprint</a></li>
                        <li><a href="https://ternis.link/llms.txt" class="hover:text-neutral-900 dark:hover:text-white">llms.txt</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 border-t border-neutral-200 pt-6 text-center text-xs text-neutral-500 dark:border-neutral-800 dark:text-neutral-500">
                &copy; {{ date('Y') }} Ternis · href.re · Official business redirects. All rights reserved.
            </div>
        </div>
    </footer>

    {{-- Interactive Link Inspector Vanilla Script --}}
    <script>
        function liveValidateSlug(val) {
            val = val.trim().replace(/^https?:\/\/(www\.)?href\.re\//i, '').replace(/^\/+/, '');
            const output = document.getElementById('inspector-output');
            const title = document.getElementById('status-title');
            const desc = document.getElementById('status-desc');
            const actions = document.getElementById('status-actions');
            const icon = document.getElementById('status-icon');
            const previewBtn = document.getElementById('btn-open-preview');
            const directBtn = document.getElementById('btn-open-direct');

            if (!val) {
                title.textContent = 'Awaiting input';
                title.className = 'font-medium text-neutral-800 dark:text-neutral-200';
                desc.textContent = 'Type or paste any href.re slug above. href.re short links use alphanumeric characters, hyphens, and underscores (e.g. inv-2026-0891).';
                actions.classList.add('hidden');
                icon.innerHTML = '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>';
                return;
            }

            const slugRegex = /^[a-zA-Z0-9_-]{1,64}$/;
            if (slugRegex.test(val)) {
                title.textContent = 'Valid href.re slug format: "' + val + '"';
                title.className = 'font-medium text-emerald-700 dark:text-emerald-400';
                desc.textContent = 'Format verified. You can preview destination metadata safely without triggering third-party scripts, or proceed directly to the verified destination.';
                actions.classList.remove('hidden');
                previewBtn.href = '/preview/' + encodeURIComponent(val);
                directBtn.href = '/' + encodeURIComponent(val);
                icon.innerHTML = '<svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>';
            } else {
                title.textContent = 'Invalid slug syntax';
                title.className = 'font-medium text-amber-700 dark:text-amber-400';
                desc.textContent = 'href.re slugs contain only letters, numbers, hyphens, and underscores. URLs containing query strings or invalid symbols are not standalone slugs.';
                actions.classList.add('hidden');
                icon.innerHTML = '<svg class="h-4 w-4 text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
            }
        }

        function inspectSlug() {
            const input = document.getElementById('inspect-input');
            const val = input.value.trim().replace(/^https?:\/\/(www\.)?href\.re\//i, '').replace(/^\/+/, '');
            if (!val) {
                input.focus();
                return;
            }
            const slugRegex = /^[a-zA-Z0-9_-]{1,64}$/;
            if (slugRegex.test(val)) {
                window.location.href = '/preview/' + encodeURIComponent(val);
            }
        }
    </script>
</body>
</html>