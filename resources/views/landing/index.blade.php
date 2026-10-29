@php
$faqs = [
    [
        'q' => 'Do I need an account?',
        'a' => 'Not for a quick link: guests shorten on <a href="https://href.nz" class="underline underline-offset-2">href.nz</a> (up to 50 links a day, auto-generated slugs). A personal subdomain, custom slugs, analytics and API keys need a sign-in via Ternis Auth — no passwords, no registration forms.',
    ],
    [
        'q' => 'How do I claim my {name}.ternis.link subdomain?',
        'a' => 'Personal subdomains are reserved for the inner circle — family members, partners and admins. If that is you, sign in, open Domains in your dashboard and pick a name: one subdomain per account, verified instantly, no DNS setup on your side.',
    ],
    [
        'q' => 'What analytics do I get?',
        'a' => 'Every link reports referrers, countries, browsers and per-day charts, with CSV export for deeper digging — the same numbers the API serves.',
    ],
    [
        'q' => 'How is my privacy protected?',
        'a' => 'There are no local passwords to leak, visitor IPs are stored only as one-way hashes, and pages set no ad trackers. Public forms carry a bot check; everything else is first-party.',
    ],
    [
        'q' => 'How does the AI Spam and Threat Protection work?',
        'a' => 'Every new link is evaluated asynchronously by TypeSafe AI against phishing deception, spam campaigns, and malware payloads. High-confidence threats are quarantined instantly, while suspicious traffic is challenged via Cloudflare Turnstile on <a href="https://protected.ternis.link" class="underline underline-offset-2">protected.ternis.link</a> — with zero latency added to clean redirects.',
    ],
    [
        'q' => 'What happens when I deactivate a link?',
        'a' => 'It stops resolving immediately, while its stats stay in your dashboard — totals and history are never rewritten.',
    ],
    [
        'q' => 'Can I use my own domain or automate via API?',
        'a' => 'Eligible plans can bring any hostname (verified by DNS within minutes), and API keys unlock the versioned REST API — see the <a href="https://docs.ternis.link/api" class="underline underline-offset-2">API guide</a>.',
    ],
];
$faqJsonLd = array_map(fn ($faq) => [
    '@type' => 'Question',
    'name' => $faq['q'],
    'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($faq['a'])],
], $faqs);
@endphp
<x-layouts.app title="ternis.link — Personal short links for family & partners">
    <x-slot:head>
        <script type="application/ld+json">
        {!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', '@id' => 'https://ternis.link/#faq', 'mainEntity' => $faqJsonLd], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    </x-slot:head>

    <div class="relative mx-auto flex max-w-4xl flex-col items-center px-4 py-16 text-center sm:px-6 sm:py-24">
        {{-- Ambient decorative background glow --}}
        <div class="pointer-events-none absolute -top-24 left-1/2 -z-10 h-96 w-full -translate-x-1/2 max-w-4xl overflow-hidden blur-3xl" aria-hidden="true">
            <div class="mx-auto h-full w-3/4 rounded-full bg-gradient-to-tr from-neutral-200/50 via-neutral-100/40 to-transparent dark:from-neutral-800/40 dark:via-neutral-900/30"></div>
        </div>

        {{-- Live Status Badge --}}
        <x-ui.badge tone="solid" class="mb-6 inline-flex items-center gap-2 px-3 py-1 text-xs">
            <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
            </span>
            For family &amp; partners
        </x-ui.badge>

        {{-- Hero Headline --}}
        <h1 class="font-display text-5xl font-bold tracking-tight sm:text-6xl lg:text-7xl">Short links, <span class="text-neutral-400 dark:text-neutral-500">on your own name.</span></h1>
        <p class="mt-4 max-w-2xl text-lg text-neutral-500 dark:text-neutral-400">
            Claim your personal <code>{name}.ternis.link</code> subdomain, create branded short links with
            custom slugs, and see exactly how they perform — no DNS setup, no fuss.
        </p>

        {{-- Hero CTAs --}}
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            @auth
                <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/') }}" variant="primary" size="lg" class="shadow-sm">
                    <span>Go to Dashboard</span>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </x-ui.button>
            @else
                <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}" variant="primary" size="lg" class="shadow-sm">
                    <span>Get Started with Ternis Auth</span>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </x-ui.button>
            @endauth
            <x-ui.button href="#how-it-works" variant="secondary" size="lg">How it works</x-ui.button>
        </div>

        {{-- Quick Secondary Navigation --}}
        <p class="mt-4 text-sm text-neutral-500 dark:text-neutral-400">
            <a href="{{ url('/pages/stats') }}" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">Live network stats</a>
            <span aria-hidden="true" class="mx-1">·</span>
            <a href="{{ url('/pages/extension') }}" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">Browser extension</a>
            <span aria-hidden="true" class="mx-1">·</span>
            <a href="https://docs.ternis.link" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">Developer docs</a>
            <span aria-hidden="true" class="mx-1">·</span>
            <a href="#faq" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">FAQ</a>
        </p>

        {{-- Network & security telemetry markers --}}
        <div class="mt-12 grid w-full max-w-3xl grid-cols-2 gap-3 rounded-2xl border border-neutral-200/80 bg-neutral-50/50 p-4 text-center sm:grid-cols-4 dark:border-neutral-800/80 dark:bg-neutral-900/40">
            <div>
                <p class="inline-flex items-center justify-center gap-1.5 font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    <span>&lt; 15ms</span>
                </p>
                <p class="text-[11px] text-neutral-500 dark:text-neutral-400">Edge Redirect Latency</p>
            </div>
            <div>
                <p class="inline-flex items-center justify-center gap-1.5 font-mono text-xs font-bold text-purple-600 dark:text-purple-400">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span>AI Shield</span>
                </p>
                <p class="text-[11px] text-neutral-500 dark:text-neutral-400">Real-Time Threat Triage</p>
            </div>
            <div>
                <p class="inline-flex items-center justify-center gap-1.5 font-mono text-xs font-bold text-neutral-900 dark:text-neutral-100">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>Hashed IP</span>
                </p>
                <p class="text-[11px] text-neutral-500 dark:text-neutral-400">Cookie-Free Privacy</p>
            </div>
            <div>
                <p class="inline-flex items-center justify-center gap-1.5 font-mono text-xs font-bold text-blue-600 dark:text-blue-400">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    <span>Edge-First</span>
                </p>
                <p class="text-[11px] text-neutral-500 dark:text-neutral-400">Global DNS &amp; Routing</p>
            </div>
        </div>

        {{-- Feature Cards Grid --}}
        <div class="mt-16 grid w-full grid-cols-1 gap-4 text-left sm:grid-cols-2 lg:grid-cols-3">
            <x-ui.card class="group transition-all duration-200 hover:-translate-y-0.5 hover:border-neutral-300 hover:shadow-md dark:hover:border-neutral-700">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-900 shadow-xs ring-1 ring-neutral-200/60 transition-colors group-hover:bg-neutral-900 group-hover:text-white dark:bg-neutral-800 dark:text-neutral-100 dark:ring-neutral-700/60 dark:group-hover:bg-white dark:group-hover:text-neutral-900">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.9 5.7 3.9 9s-1.4 6.4-3.9 9c-2.5-2.6-3.9-5.7-3.9-9S9.5 5.6 12 3Z"/></svg>
                    </div>
                    <div class="font-display text-lg font-bold">Personal subdomain</div>
                </div>
                <p class="mt-3 text-sm text-neutral-500 dark:text-neutral-400">Your own <code>{name}.ternis.link</code>, verified instantly — one per account, ready for links right away.</p>
            </x-ui.card>

            <x-ui.card class="group transition-all duration-200 hover:-translate-y-0.5 hover:border-neutral-300 hover:shadow-md dark:hover:border-neutral-700">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-900 shadow-xs ring-1 ring-neutral-200/60 transition-colors group-hover:bg-neutral-900 group-hover:text-white dark:bg-neutral-800 dark:text-neutral-100 dark:ring-neutral-700/60 dark:group-hover:bg-white dark:group-hover:text-neutral-900">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 12V4.5A1 1 0 0 1 4.5 3.5H12L20.5 12 12 20.5Z"/><circle cx="8" cy="8" r="1.2" fill="currentColor" stroke="none"/></svg>
                    </div>
                    <div class="font-display text-lg font-bold">Custom slugs</div>
                </div>
                <p class="mt-3 text-sm text-neutral-500 dark:text-neutral-400">Memorable codes in your own words — or pick the auto-generated length, from 3 to 64 characters.</p>
            </x-ui.card>

            <x-ui.card class="group transition-all duration-200 hover:-translate-y-0.5 hover:border-neutral-300 hover:shadow-md dark:hover:border-neutral-700">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-900 shadow-xs ring-1 ring-neutral-200/60 transition-colors group-hover:bg-neutral-900 group-hover:text-white dark:bg-neutral-800 dark:text-neutral-100 dark:ring-neutral-700/60 dark:group-hover:bg-white dark:group-hover:text-neutral-900">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                    </div>
                    <div class="font-display text-lg font-bold">Descriptions &amp; tags</div>
                </div>
                <p class="mt-3 text-sm text-neutral-500 dark:text-neutral-400">Keep campaigns organised with free-text descriptions, searchable tags and link targeting.</p>
            </x-ui.card>

            <x-ui.card class="group transition-all duration-200 hover:-translate-y-0.5 hover:border-neutral-300 hover:shadow-md dark:hover:border-neutral-700">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-900 shadow-xs ring-1 ring-neutral-200/60 transition-colors group-hover:bg-neutral-900 group-hover:text-white dark:bg-neutral-800 dark:text-neutral-100 dark:ring-neutral-700/60 dark:group-hover:bg-white dark:group-hover:text-neutral-900">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="m7 14 4-4 4 3 5-7"/></svg>
                    </div>
                    <div class="font-display text-lg font-bold">Click analytics</div>
                </div>
                <p class="mt-3 text-sm text-neutral-500 dark:text-neutral-400">Referrers, countries and per-day charts per link, with CSV export for deeper digging.</p>
            </x-ui.card>

            <x-ui.card class="group transition-all duration-200 hover:-translate-y-0.5 hover:border-neutral-300 hover:shadow-md dark:hover:border-neutral-700">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-900 shadow-xs ring-1 ring-neutral-200/60 transition-colors group-hover:bg-neutral-900 group-hover:text-white dark:bg-neutral-800 dark:text-neutral-100 dark:ring-neutral-700/60 dark:group-hover:bg-white dark:group-hover:text-neutral-900">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="7" cy="12" r="4"/><path d="M11 12H21"/><path d="M17 12v4M20.5 12v3"/></svg>
                    </div>
                    <div class="font-display text-lg font-bold">API access</div>
                </div>
                <p class="mt-3 text-sm text-neutral-500 dark:text-neutral-400">Create and manage links programmatically with scoped API keys and a versioned REST API.</p>
            </x-ui.card>

            <x-ui.card class="group transition-all duration-200 hover:-translate-y-0.5 hover:border-neutral-300 hover:shadow-md dark:hover:border-neutral-700">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-900 shadow-xs ring-1 ring-neutral-200/60 transition-colors group-hover:bg-neutral-900 group-hover:text-white dark:bg-neutral-800 dark:text-neutral-100 dark:ring-neutral-700/60 dark:group-hover:bg-white dark:group-hover:text-neutral-900">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                    </div>
                    <div class="font-display text-lg font-bold">AI spam &amp; threat shield</div>
                </div>
                <p class="mt-3 text-sm text-neutral-500 dark:text-neutral-400">Powered by TypeSafe AI. Destination URLs are screened for phishing deception and malicious payloads, quarantining threats before harm occurs.</p>
            </x-ui.card>

            <x-ui.card class="group transition-all duration-200 hover:-translate-y-0.5 hover:border-neutral-300 hover:shadow-md dark:hover:border-neutral-700">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-900 shadow-xs ring-1 ring-neutral-200/60 transition-colors group-hover:bg-neutral-900 group-hover:text-white dark:bg-neutral-800 dark:text-neutral-100 dark:ring-neutral-700/60 dark:group-hover:bg-white dark:group-hover:text-neutral-900">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13.5a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 10.5a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>
                    </div>
                    <div class="font-display text-lg font-bold">Custom domains</div>
                </div>
                <p class="mt-3 text-sm text-neutral-500 dark:text-neutral-400">Bring your own hostname on eligible plans — verified by DNS and ready in minutes.</p>
            </x-ui.card>
        </div>

        {{-- How It Works --}}
        <div id="how-it-works" class="mt-20 w-full scroll-mt-8 text-left">
            <div class="text-center">
                <h2 class="font-display text-2xl font-bold tracking-tight sm:text-3xl">How it works</h2>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Three straightforward steps from sign-in to branded links.</p>
            </div>
            <ol class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <li class="relative rounded-xl border border-neutral-200 bg-white p-6 shadow-xs transition-all hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-700">
                    <div class="flex items-center gap-3">
                        <span aria-hidden="true" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-neutral-900 font-display text-base font-bold dark:border-white">1</span>
                        <p class="font-medium">Sign in with Ternis Auth</p>
                    </div>
                    <p class="mt-2 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">One account across the family of services — no new password.</p>
                </li>
                <li class="relative rounded-xl border border-neutral-200 bg-white p-6 shadow-xs transition-all hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-700">
                    <div class="flex items-center gap-3">
                        <span aria-hidden="true" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-neutral-900 font-display text-base font-bold dark:border-white">2</span>
                        <p class="font-medium">Claim your subdomain</p>
                    </div>
                    <p class="mt-2 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Pick your <code>{name}.ternis.link</code> on the Domains page — verified instantly.</p>
                </li>
                <li class="relative rounded-xl border border-neutral-200 bg-white p-6 shadow-xs transition-all hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-700">
                    <div class="flex items-center gap-3">
                        <span aria-hidden="true" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-neutral-900 font-display text-base font-bold dark:border-white">3</span>
                        <p class="font-medium">Share &amp; measure</p>
                    </div>
                    <p class="mt-2 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Create branded links, tag them, and watch the analytics roll in.</p>
                </li>
            </ol>
        </div>

        {{-- Family & Partners Use Cases --}}
        <div class="mt-12 grid w-full grid-cols-1 gap-4 text-left sm:grid-cols-2">
            <x-ui.card class="transition-all hover:border-neutral-300 dark:hover:border-neutral-700">
                <div class="flex items-center justify-between">
                    <div class="font-display text-lg font-bold">Family</div>
                    <span class="rounded-full bg-neutral-100 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">Personal &amp; Household</span>
                </div>
                <p class="mt-2 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Your corner of ternis.link — personal subdomains, generous quotas and full analytics for the whole household.</p>
            </x-ui.card>
            <x-ui.card class="transition-all hover:border-neutral-300 dark:hover:border-neutral-700">
                <div class="flex items-center justify-between">
                    <div class="font-display text-lg font-bold">Partners</div>
                    <span class="rounded-full bg-neutral-100 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">Team &amp; Tooling</span>
                </div>
                <p class="mt-2 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Branded short links on infrastructure you can trust, with API access for your own tooling.</p>
            </x-ui.card>
        </div>

        {{-- Live Telemetry from the Network --}}
        <div class="mt-12 w-full rounded-2xl border border-neutral-200 bg-white p-6 text-left shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-neutral-100 pb-4 dark:border-neutral-800">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    </span>
                    <span class="font-display text-lg font-bold">Live from the network</span>
                </div>
                <a href="{{ url('/pages/stats') }}" class="text-sm font-medium text-neutral-500 underline underline-offset-2 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">All stats →</a>
            </div>
            <dl class="mt-5 grid grid-cols-1 gap-4 text-center sm:grid-cols-3">
                <div class="rounded-xl bg-neutral-50 p-4 dark:bg-neutral-950/60">
                    <dd class="font-display text-3xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">{{ number_format($stats['total_links'] ?? 0) }}</dd>
                    <dt class="mt-1 text-xs font-medium text-neutral-500 dark:text-neutral-400">short links created</dt>
                </div>
                <div class="rounded-xl bg-neutral-50 p-4 dark:bg-neutral-950/60">
                    <dd class="font-display text-3xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">{{ number_format($stats['total_clicks'] ?? 0) }}</dd>
                    <dt class="mt-1 text-xs font-medium text-neutral-500 dark:text-neutral-400">redirects counted</dt>
                </div>
                <div class="rounded-xl bg-neutral-50 p-4 dark:bg-neutral-950/60">
                    <dd class="font-display text-3xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">{{ number_format($stats['links_today'] ?? 0) }}</dd>
                    <dt class="mt-1 text-xs font-medium text-neutral-500 dark:text-neutral-400">created today</dt>
                </div>
            </dl>
        </div>

        {{-- Frequently Asked Questions (Interactive Accordion) --}}
        <div id="faq" class="mt-20 w-full scroll-mt-8 text-left">
            <div class="text-center">
                <h2 class="font-display text-2xl font-bold tracking-tight sm:text-3xl">Frequently asked questions</h2>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Quick answers to common questions about accounts, privacy, and limits.</p>
            </div>
            <div class="mt-8 space-y-3">
                @foreach ($faqs as $index => $faq)
                    <details class="group rounded-xl border border-neutral-200 bg-white p-5 transition-colors hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-700" {{ $index === 0 ? 'open' : '' }}>
                        <summary class="flex cursor-pointer items-center justify-between gap-4 font-display text-base font-bold text-neutral-900 select-none dark:text-neutral-100">
                            <span class="flex items-center gap-3">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-xs font-bold text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">Q</span>
                                <span>{{ $faq['q'] }}</span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 text-neutral-400 transition-transform duration-200 group-open:rotate-180 dark:text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </summary>
                        <div class="mt-3 pl-9 pr-4 text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                            {!! $faq['a'] !!}
                        </div>
                    </details>
                @endforeach
            </div>
        </div>

        {{-- Guest and Network Footer Callout --}}
        <div class="mt-14 w-full rounded-2xl border border-dashed border-neutral-300 p-6 text-center dark:border-neutral-700">
            <p class="text-sm text-neutral-600 dark:text-neutral-300">
                Just need one quick link with no account? <a href="https://href.nz" class="font-semibold text-neutral-900 underline underline-offset-2 dark:text-neutral-100">Use href.nz</a>
            </p>
            <p class="mt-3 max-w-2xl text-xs text-neutral-400 dark:text-neutral-500">
                The wider Ternis network also covers German-language links on <a href="https://meinlink.at" class="underline underline-offset-2 hover:text-neutral-700 dark:hover:text-neutral-300">meinlink.at</a>,
                click tracking on <a href="https://clicked.at" class="underline underline-offset-2 hover:text-neutral-700 dark:hover:text-neutral-300">clicked.at</a>,
                and creator-focused links on <a href="https://href.yt" class="underline underline-offset-2 hover:text-neutral-700 dark:hover:text-neutral-300">href.yt</a>;
                <a href="https://href.re" class="underline underline-offset-2 hover:text-neutral-700 dark:hover:text-neutral-300">href.re</a> supports other Ternis applications.
            </p>
        </div>
    </div>
</x-layouts.app>
