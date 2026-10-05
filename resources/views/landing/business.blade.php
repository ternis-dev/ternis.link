@php
$stats = $stats ?? \App\Support\NetworkStats::overview();

$faqs = [
    [
        'q' => 'Why does Ternis maintain href.re separately from href.nz?',
        'a' => 'href.nz is an open, anonymous shortener designed for casual links with automatic rate limits. In contrast, <strong>href.re</strong> is reserved strictly for official Ternis business, corporate operations, invoices, and verified partner channels. No public guest shortening is allowed, keeping the domain reputation clean and trusted by spam filters.',
    ],
    [
        'q' => 'Can anyone shorten a link on href.re?',
        'a' => 'No. Public guest shortening is disabled on this domain. Only authenticated team members via Ternis Auth SSO or provisioned API keys can issue redirects on href.re. Guests looking to shorten links can use <a href="https://href.nz" class="underline underline-offset-2">href.nz</a>.',
    ],
    [
        'q' => 'I received an href.re link. How do I know it is safe?',
        'a' => 'All href.re redirects are access-controlled and audited by the Ternis team. Every link resolves directly over TLS 1.3 to its destination without intermediary advertising, tracker walls, or script injection.',
    ],
    [
        'q' => 'How does href.re protect recipient privacy?',
        'a' => 'Visitor IP addresses are never stored in raw form: they are hashed immediately using SHA-256 for abuse counters and discarded. href.re sets no third-party tracking cookies or advertising pixels.',
    ],
    [
        'q' => 'Can our automated services create href.re links via API?',
        'a' => 'Yes. Authorized accounts can provision links programmatically using the versioned REST API at <code>links.t-api.de/v1/links</code> with scoped Bearer tokens. See the <a href="https://docs.ternis.link/api" class="underline underline-offset-2">API guide</a> for details.',
    ],
    [
        'q' => 'What happens when a business link is deactivated or expires?',
        'a' => 'It stops resolving immediately and returns a clean not-found response. Historical click aggregates and creation records are preserved permanently in dashboard analytics so reports remain accurate.',
    ],
];

$faqJsonLd = array_map(fn ($faq) => [
    '@type' => 'Question',
    'name' => $faq['q'],
    'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($faq['a'])],
], $faqs);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>href.re — Official business links</title>
    <meta name="description" content="href.re — reserved for official ternis business links. Verified, trusted, analytics-backed.">
    <link rel="canonical" href="https://href.re/">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <script type="application/ld+json">
    {!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', '@id' => 'https://href.re/#faq', 'mainEntity' => $faqJsonLd], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
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
<body class="flex min-h-screen flex-col">
    <header class="border-b border-neutral-200 dark:border-neutral-800">
        <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="/" class="font-display text-2xl font-bold tracking-tight">href<span>.re</span></a>
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    data-theme-toggle
                    class="theme-toggle inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-neutral-300 text-neutral-600 transition-colors hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-900"
                    aria-label="Toggle color theme"
                >
                    <svg class="icon-moon h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                    <svg class="icon-sun h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>
                </button>
                <a href="https://href.nz" class="text-xs text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white transition-colors" rel="noopener">Public Shortener (href.nz) →</a>
                <x-ui.badge tone="solid">Official · Business only</x-ui.badge>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-4xl flex-1 px-4 sm:px-6">
        <section class="py-14 text-center sm:py-20">
            <x-ui.badge tone="solid" class="mb-4">Official Infrastructure</x-ui.badge>
            <h1 class="font-display text-5xl font-bold tracking-tight sm:text-6xl">Official links, <span class="text-neutral-400 dark:text-neutral-500">recognizable.</span></h1>
            <p class="mx-auto mt-4 max-w-xl text-neutral-500 dark:text-neutral-400"><strong class="text-neutral-900 dark:text-white">href.re</strong> is reserved for official ternis business links. No public shortening here — every redirect is provisioned and audited by the ternis team.</p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                @auth
                    <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/') }}" variant="primary" size="lg">Go to Dashboard</x-ui.button>
                @else
                    <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}" variant="primary" size="lg">Sign in with Ternis Auth</x-ui.button>
                @endauth
                <x-ui.button href="#how-it-works" variant="secondary" size="lg">How it works</x-ui.button>
                <x-ui.button href="https://href.nz" variant="secondary" size="lg" rel="noopener">Open href.nz</x-ui.button>
            </div>
            <p class="mt-4 text-sm text-neutral-500 dark:text-neutral-400">
                <a href="#principles" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">Core principles</a>
                <span aria-hidden="true" class="mx-1">·</span>
                <a href="https://docs.ternis.link/api" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">API guide</a>
                <span aria-hidden="true" class="mx-1">·</span>
                <a href="https://ternis.link/pages/stats" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">Network stats</a>
                <span aria-hidden="true" class="mx-1">·</span>
                <a href="#faq" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">FAQ</a>
            </p>
            <ul class="mt-8 flex flex-wrap justify-center gap-2 text-xs text-neutral-500 dark:text-neutral-400">
                <li class="rounded-full bg-neutral-100 px-3 py-1 dark:bg-neutral-900">✓ Verified sender</li>
                <li class="rounded-full bg-neutral-100 px-3 py-1 dark:bg-neutral-900">✓ Click analytics</li>
                <li class="rounded-full bg-neutral-100 px-3 py-1 dark:bg-neutral-900">✓ Abuse-monitored</li>
                <li class="rounded-full bg-neutral-100 px-3 py-1 dark:bg-neutral-900">✓ Zero ad trackers</li>
                <li class="rounded-full bg-neutral-100 px-3 py-1 dark:bg-neutral-900">✓ Sub-millisecond edge</li>
            </ul>
        </section>

        <section id="principles" class="scroll-mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-ui.card>
                <div class="flex items-center gap-2.5">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <div class="font-display text-lg font-bold">Business only</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Public guest shortening is disabled on this domain. Need a quick public link? Use <a href="https://href.nz" class="font-medium text-neutral-900 underline underline-offset-2 hover:text-black dark:text-white dark:hover:text-neutral-200" rel="noopener">href.nz</a>.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center gap-2.5">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                    <div class="font-display text-lg font-bold">Trusted by default</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Recipients can trust href.re redirects — they are issued internally and access-controlled via <a href="https://ternis.link" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white" rel="noopener">ternis.link</a>.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center gap-2.5">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h16"/><path d="M7 20v-6M12 20V6M17 20v-9"/></svg>
                    <div class="font-display text-lg font-bold">Measured</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Every business redirect logs referrer, country and timestamp asynchronously for insights without tracking cookies.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center gap-2.5">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 12V4.5A1 1 0 0 1 4.5 3.5H12L20.5 12 12 20.5Z"/><circle cx="8" cy="8" r="1.2" fill="currentColor" stroke="none"/></svg>
                    <div class="font-display text-lg font-bold">Custom slugs</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Semantic aliases for invoices and partner notices — like <code>href.re/inv-2026-0891</code> — that look sharp and recognizable.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center gap-2.5">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="7" cy="12" r="4"/><path d="M11 12H21"/><path d="M17 12v4M20.5 12v3"/></svg>
                    <div class="font-display text-lg font-bold">API access</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Provision links programmatically from billing engines, notifications, and CI/CD pipelines via the versioned REST API.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center gap-2.5">
                    <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <div class="font-display text-lg font-bold">Lifecycle governance</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Permanent invariants for contracts and documentation that never rot, or scheduled expiration for time-bound notices.</p>
            </x-ui.card>
        </section>

        <div id="how-it-works" class="mt-16 w-full scroll-mt-8 text-left">
            <h2 class="text-center font-display text-2xl font-bold tracking-tight">How it works</h2>
            <ol class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <li class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <span aria-hidden="true" class="inline-flex h-8 w-8 items-center justify-center rounded-full border-2 border-neutral-900 font-display text-base font-bold dark:border-white">1</span>
                    <p class="mt-3 font-medium">Authenticated provisioning</p>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Sign in with Ternis Auth SSO or authenticate automated pipelines via scoped Bearer API tokens.</p>
                </li>
                <li class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <span aria-hidden="true" class="inline-flex h-8 w-8 items-center justify-center rounded-full border-2 border-neutral-900 font-display text-base font-bold dark:border-white">2</span>
                    <p class="mt-3 font-medium">Audited redirect setup</p>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Pick a business slug or generate one, set optional expiration, and verify the destination URL.</p>
                </li>
                <li class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <span aria-hidden="true" class="inline-flex h-8 w-8 items-center justify-center rounded-full border-2 border-neutral-900 font-display text-base font-bold dark:border-white">3</span>
                    <p class="mt-3 font-medium">Deliver &amp; measure</p>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Distribute in invoices, emails, or SMS, with sub-millisecond Caddy &amp; Redis edge routing.</p>
                </li>
            </ol>
        </div>

        <div class="mt-12 grid w-full grid-cols-1 gap-4 text-left sm:grid-cols-2">
            <x-ui.card>
                <div class="font-display text-lg font-bold">Invoicing &amp; Billing</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Clean, tamper-evident links inside automated PDF invoices and billing statements that bypass spam filters.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="font-display text-lg font-bold">Partners &amp; Developers</div>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Scoped redirects for B2B portals and automated transactional notifications with reliable referrer tracking.</p>
            </x-ui.card>
        </div>

        <div class="mt-12 w-full rounded-xl border border-neutral-200 bg-white p-5 text-left dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <span class="font-display text-lg font-bold">Live from the network</span>
                <a href="https://ternis.link/pages/stats" class="text-sm text-neutral-500 underline underline-offset-2 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">All stats →</a>
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

        <p class="mt-10 text-center text-sm text-neutral-500 dark:text-neutral-400">
            Just need a quick link with no account? <a href="https://href.nz" class="font-semibold text-neutral-900 underline underline-offset-2 dark:text-neutral-100">Use href.nz</a>
        </p>
    </main>

    <footer class="mt-16 border-t border-neutral-200 py-6 dark:border-neutral-800">
        <p class="text-center text-xs text-neutral-500 dark:text-neutral-400">
            href.re — official business shortener by <a href="https://ternis.link" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white" rel="noopener">ternis.link</a> · 
            public links: <a href="https://href.nz" class="font-semibold underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white" rel="noopener">href.nz</a> · 
            <a href="https://ternis.link/pages/legal/privacy" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">privacy</a> · 
            <a href="https://ternis.link/pages/legal/terms" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">terms</a> · 
            <a href="{{ \App\Support\DomainUrls::impressum('href.re', 'en') }}" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">imprint</a>
        </p>
    </footer>
</body>
</html>