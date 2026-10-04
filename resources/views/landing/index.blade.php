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
    <div class="mx-auto flex max-w-4xl flex-col items-center px-4 py-20 text-center sm:px-6">
        <x-ui.badge tone="solid" class="mb-6">For family &amp; partners</x-ui.badge>
        <h1 class="font-display text-5xl font-bold tracking-tight sm:text-6xl">Short links, <span class="text-neutral-400 dark:text-neutral-500">on your own name.</span></h1>
        <p class="mt-4 max-w-2xl text-lg text-neutral-500 dark:text-neutral-400">
            Claim your personal <code>{name}.ternis.link</code> subdomain, create branded short links with
            custom slugs, and see exactly how they perform — no DNS setup, no fuss.
        </p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            @auth
                <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/') }}" variant="primary" size="lg">Go to Dashboard</x-ui.button>
            @else
                <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}" variant="primary" size="lg">Get Started with Ternis Auth</x-ui.button>
            @endauth
            <x-ui.button href="#how-it-works" variant="secondary" size="lg">How it works</x-ui.button>
        </div>
        <p class="mt-4 text-sm text-neutral-500 dark:text-neutral-400">
            <a href="{{ url('/pages/stats') }}" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">Live network stats</a>
            <span aria-hidden="true" class="mx-1">·</span>
            <a href="{{ url('/pages/extension') }}" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">Browser extension</a>
            <span aria-hidden="true" class="mx-1">·</span>
            <a href="https://docs.ternis.link" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">Developer docs</a>
            <span aria-hidden="true" class="mx-1">·</span>
            <a href="#faq" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">FAQ</a>
        </p>

        <div class="mt-16 grid w-full grid-cols-1 gap-4 text-left sm:grid-cols-2 lg:grid-cols-3">
            <x-ui.card>
                <div class="flex items-center gap-3">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.9 5.7 3.9 9s-1.4 6.4-3.9 9c-2.5-2.6-3.9-5.7-3.9-9S9.5 5.6 12 3Z"/></svg>
                    <div class="font-display text-lg font-bold">Personal subdomain</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Your own <code>{name}.ternis.link</code>, verified instantly — one per account, ready for links right away.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center gap-3">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 12V4.5A1 1 0 0 1 4.5 3.5H12L20.5 12 12 20.5Z"/><circle cx="8" cy="8" r="1.2" fill="currentColor" stroke="none"/></svg>
                    <div class="font-display text-lg font-bold">Custom slugs</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Memorable codes in your own words — or pick the auto-generated length, from 3 to 64 characters.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center gap-3">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8.5 6H21M8.5 12H21M8.5 18H21"/><circle cx="4.5" cy="6" r="1.2" fill="currentColor" stroke="none"/><circle cx="4.5" cy="12" r="1.2" fill="currentColor" stroke="none"/><circle cx="4.5" cy="18" r="1.2" fill="currentColor" stroke="none"/></svg>
                    <div class="font-display text-lg font-bold">Descriptions &amp; tags</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Note what each link is for and organize with tags, so every short link stays findable.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center gap-3">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h16"/><path d="M7 20v-6M12 20V6M17 20v-9"/></svg>
                    <div class="font-display text-lg font-bold">Click analytics</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Referrers, countries and per-day charts per link, with CSV export for deeper digging.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center gap-3">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="7" cy="12" r="4"/><path d="M11 12H21"/><path d="M17 12v4M20.5 12v3"/></svg>
                    <div class="font-display text-lg font-bold">API access</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Create and manage links programmatically with scoped API keys and a versioned REST API.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center gap-3">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13.5a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 10.5a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>
                    <div class="font-display text-lg font-bold">Custom domains</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Bring your own hostname on eligible plans — verified by DNS and ready in minutes.</p>
            </x-ui.card>
        </div>

        <div id="how-it-works" class="mt-16 w-full scroll-mt-8 text-left">
            <h2 class="text-center font-display text-2xl font-bold tracking-tight">How it works</h2>
            <ol class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <li class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <span aria-hidden="true" class="inline-flex h-8 w-8 items-center justify-center rounded-full border-2 border-neutral-900 font-display text-base font-bold dark:border-white">1</span>
                    <p class="mt-3 font-medium">Sign in with Ternis Auth</p>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">One account across the family of services — no new password.</p>
                </li>
                <li class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <span aria-hidden="true" class="inline-flex h-8 w-8 items-center justify-center rounded-full border-2 border-neutral-900 font-display text-base font-bold dark:border-white">2</span>
                    <p class="mt-3 font-medium">Claim your subdomain</p>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Pick your <code>{name}.ternis.link</code> on the Domains page — verified instantly.</p>
                </li>
                <li class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <span aria-hidden="true" class="inline-flex h-8 w-8 items-center justify-center rounded-full border-2 border-neutral-900 font-display text-base font-bold dark:border-white">3</span>
                    <p class="mt-3 font-medium">Share &amp; measure</p>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Create branded links, tag them, and watch the analytics roll in.</p>
                </li>
            </ol>
        </div>

        <div class="mt-12 grid w-full grid-cols-1 gap-4 text-left sm:grid-cols-2">
            <x-ui.card>
                <div class="font-display text-lg font-bold">Family</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Your corner of ternis.link — personal subdomains, generous quotas and full analytics for the whole household.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="font-display text-lg font-bold">Partners</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Branded short links on infrastructure you can trust, with API access for your own tooling.</p>
            </x-ui.card>
        </div>

        <div class="mt-12 w-full rounded-xl border border-neutral-200 bg-white p-5 text-left dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <span class="font-display text-lg font-bold">Live from the network</span>
                <a href="{{ url('/pages/stats') }}" class="text-sm text-neutral-500 underline underline-offset-2 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">All stats →</a>
            </div>
            <dl class="mt-3 grid grid-cols-3 gap-4 text-center">
                <div>
                    <dd class="font-display text-2xl font-bold tracking-tight">{{ number_format($stats['total_links'] ?? 0) }}</dd>
                    <dt class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">short links created</dt>
                </div>
                <div>
                    <dd class="font-display text-2xl font-bold tracking-tight">{{ number_format($stats['total_clicks'] ?? 0) }}</dd>
                    <dt class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">redirects counted</dt>
                </div>
                <div>
                    <dd class="font-display text-2xl font-bold tracking-tight">{{ number_format($stats['links_today'] ?? 0) }}</dd>
                    <dt class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">created today</dt>
                </div>
            </dl>
        </div>

        <div id="faq" class="mt-16 w-full scroll-mt-8 text-left">
            <h2 class="text-center font-display text-2xl font-bold tracking-tight">Frequently asked questions</h2>
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($faqs as $faq)
                    <x-ui.card>
                        <h3 class="font-display text-base font-bold">{{ $faq['q'] }}</h3>
                        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{!! $faq['a'] !!}</p>
                    </x-ui.card>
                @endforeach
            </div>
        </div>

        <p class="mt-10 text-sm text-neutral-500 dark:text-neutral-400">
            Just need one quick link with no account? <a href="https://href.nz" class="font-semibold text-neutral-900 underline underline-offset-2 dark:text-neutral-100">Use href.nz</a>
        </p>
    </div>
</x-layouts.app>
