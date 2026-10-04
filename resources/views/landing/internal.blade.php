@php
$faqs = [
    [
        'q' => 'What is int.ternis.link used for?',
        'a' => '<strong>int.ternis.link</strong> is the internal routing gateway for the Ternis network. It serves internal application links, automated service redirects, deep links across dashboards and internal tools, and acts as the centralized gateway for legal notices like the site imprint.',
    ],
    [
        'q' => 'How does the imprint gateway work?',
        'a' => 'Network properties and external services link to <code>/imprint</code> or <code>/impressum</code> on int.ternis.link. The gateway resolves the referring domain context and language preferences, forwarding visitors directly to the canonical imprint disclosure on <a href="https://ternis.dev" class="underline underline-offset-2">ternis.dev</a>.',
    ],
    [
        'q' => 'Can anyone register or shorten links on int.ternis.link?',
        'a' => 'No. int.ternis.link is strictly reserved for internal application links and system routing. For public guest shortening, visit <a href="https://href.nz" class="underline underline-offset-2">href.nz</a>. Family and partners with accounts can create custom short links at <a href="https://ternis.link" class="underline underline-offset-2">ternis.link</a>.',
    ],
    [
        'q' => 'How is privacy handled across internal links?',
        'a' => 'All internal redirects resolve directly over secure TLS without intermediary tracking walls, profiling cookies, or third-party analytics scripts. IPs are hashed transiently strictly for rate limiting and abuse prevention.',
    ],
];

$faqJsonLd = array_map(fn ($faq) => [
    '@type' => 'Question',
    'name' => $faq['q'],
    'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($faq['a'])],
], $faqs);
@endphp

<x-layouts.app title="int.ternis.link — Internal application links & gateway">
    <x-slot:head>
        <meta name="description" content="int.ternis.link — Internal application routing, service dispatch, and centralized legal disclosures for the Ternis ecosystem.">
        <link rel="canonical" href="https://int.ternis.link/">
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => 'https://int.ternis.link/#website',
                    'url' => 'https://int.ternis.link/',
                    'name' => 'int.ternis.link',
                    'description' => 'Internal application routing and gateway for the Ternis ecosystem.',
                ],
                [
                    '@type' => 'FAQPage',
                    '@id' => 'https://int.ternis.link/#faq',
                    'mainEntity' => $faqJsonLd,
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    </x-slot:head>

    <div class="mx-auto flex max-w-4xl flex-col items-center px-4 py-16 text-center sm:px-6 sm:py-20">
        <x-ui.badge tone="solid" class="mb-6">Internal Infrastructure · Gateway</x-ui.badge>
        <h1 class="font-display text-5xl font-bold tracking-tight sm:text-6xl">Internal application links, <span class="text-neutral-400 dark:text-neutral-500">centralized.</span></h1>
        <p class="mt-4 max-w-2xl text-lg text-neutral-500 dark:text-neutral-400">
            <strong>int.ternis.link</strong> is the internal routing gateway for the Ternis network. It manages internal application-links, system routing, and centralized legal gateways such as the site imprint.
        </p>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <x-ui.button href="{{ url('/imprint') }}" variant="primary" size="lg">Imprint Gateway</x-ui.button>
            <x-ui.button href="https://ternis.link" variant="secondary" size="lg">ternis.link Home</x-ui.button>
            <x-ui.button href="#gateways" variant="secondary" size="lg">Routing &amp; Endpoints</x-ui.button>
        </div>

        <p class="mt-4 text-sm text-neutral-500 dark:text-neutral-400">
            <a href="{{ url('/impressum') }}" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">German Imprint (/impressum)</a>
            <span aria-hidden="true" class="mx-1">·</span>
            <a href="https://ternis.dev" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">ternis.dev</a>
            <span aria-hidden="true" class="mx-1">·</span>
            <a href="https://dash.ternis.link" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">Dashboard</a>
            <span aria-hidden="true" class="mx-1">·</span>
            <a href="#faq" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">FAQ</a>
        </p>

        <div id="use-cases" class="mt-16 grid w-full grid-cols-1 gap-4 text-left sm:grid-cols-2">
            <x-ui.card>
                <div class="flex items-center gap-3">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="8" x="2" y="2" rx="2" ry="2"/><rect width="20" height="8" x="2" y="14" rx="2" ry="2"/><line x1="6" x2="6.01" y1="6" y2="6"/><line x1="6" x2="6.01" y1="18" y2="18"/></svg>
                    <div class="font-display text-lg font-bold">Internal application links</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Provides reliable deep-linking and routing between Ternis internal applications, administration panels, and automated background systems.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center gap-3">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10M6 10h10"/></svg>
                    <div class="font-display text-lg font-bold">Imprint &amp; legal gateway</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Serves as the unified legal resolver (<code>/imprint</code> and <code>/impressum</code>) for all network domains, auto-detecting language and domain provenance.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center gap-3">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16 3 4 4-4 4"/><path d="M20 7H4"/><path d="m8 21-4-4 4-4"/><path d="M4 17h16"/></svg>
                    <div class="font-display text-lg font-bold">System routing &amp; dispatch</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Persistent routing aliases that shield notification templates, transactional emails, and client code from downstream server migrations.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center gap-3">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                    <div class="font-display text-lg font-bold">Privacy-first design</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Zero third-party trackers, no cookie walls, and non-reversible IP hashing solely for edge rate-limiting and infrastructure security.</p>
            </x-ui.card>
        </div>

        <div id="gateways" class="mt-12 w-full rounded-xl border border-neutral-200 bg-white p-6 text-left dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <span class="font-display text-lg font-bold">Active Gateway Endpoints</span>
                <span class="text-xs text-neutral-500 dark:text-neutral-400">Infrastructure services</span>
            </div>
            <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                The gateway routes incoming requests according to referring hostname and client parameters:
            </p>
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-lg border border-neutral-100 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-950">
                    <div class="font-mono text-sm font-semibold text-neutral-900 dark:text-neutral-100">/imprint</div>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Redirects to canonical English imprint with domain provenance query parameter.</p>
                    <a href="{{ url('/imprint?domain=int.ternis.link&lang=en') }}" class="mt-2 inline-block text-xs text-neutral-900 underline underline-offset-2 dark:text-neutral-100">Test /imprint &rarr;</a>
                </div>
                <div class="rounded-lg border border-neutral-100 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-950">
                    <div class="font-mono text-sm font-semibold text-neutral-900 dark:text-neutral-100">/impressum</div>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Redirects to canonical German Impressum with domain provenance query parameter.</p>
                    <a href="{{ url('/impressum?domain=int.ternis.link&lang=de') }}" class="mt-2 inline-block text-xs text-neutral-900 underline underline-offset-2 dark:text-neutral-100">Test /impressum &rarr;</a>
                </div>
            </div>
        </div>

        <div class="mt-12 w-full text-left">
            <h2 class="text-center font-display text-2xl font-bold tracking-tight">Connected Ecosystem</h2>
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-ui.card>
                    <div class="font-display text-base font-bold">ternis.link</div>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Short links for family &amp; verified partners with personal subdomains.</p>
                    <a href="https://ternis.link" class="mt-3 inline-block text-xs font-semibold text-neutral-900 underline underline-offset-2 dark:text-neutral-100">Visit ternis.link &rarr;</a>
                </x-ui.card>
                <x-ui.card>
                    <div class="font-display text-base font-bold">href.re</div>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Official business redirects, invoice links, and corporate operations.</p>
                    <a href="https://href.re" class="mt-3 inline-block text-xs font-semibold text-neutral-900 underline underline-offset-2 dark:text-neutral-100">Visit href.re &rarr;</a>
                </x-ui.card>
                <x-ui.card>
                    <div class="font-display text-base font-bold">href.nz</div>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Public anonymous URL shortener and instant QR code generator.</p>
                    <a href="https://href.nz" class="mt-3 inline-block text-xs font-semibold text-neutral-900 underline underline-offset-2 dark:text-neutral-100">Visit href.nz &rarr;</a>
                </x-ui.card>
            </div>
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
            Looking for public short links? <a href="https://href.nz" class="font-semibold text-neutral-900 underline underline-offset-2 dark:text-neutral-100">Open href.nz</a>
        </p>
    </div>
</x-layouts.app>
