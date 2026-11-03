<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>protected.ternis.link — Zero-Trust Link & Bot Protection</title>
    <meta name="description" content="Centralized verification gateway for the ternis.link network: zero-trust bot protection, edge rate-limiting, and privacy-first human challenges.">
    <link rel="icon" href="{{ asset('favicon-protected.svg') }}">
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
<body class="flex min-h-screen flex-col bg-neutral-50 text-neutral-900 antialiased dark:bg-neutral-950 dark:text-neutral-100 selection:bg-neutral-900 selection:text-white dark:selection:bg-white dark:selection:text-neutral-900">

    {{-- Top Header without Brand Icon --}}
    <header class="sticky top-0 z-40 border-b border-neutral-200/80 bg-white/90 backdrop-blur-md dark:border-neutral-800/80 dark:bg-neutral-900/90">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
            {{-- Typographic brand only, NO brand icon --}}
            <div class="flex items-center gap-3">
                <a href="{{ route('protected.index') }}" class="font-display text-base font-bold tracking-tight text-neutral-900 hover:opacity-80 transition-opacity dark:text-white sm:text-lg">
                    protected<span class="text-neutral-400 dark:text-neutral-500">.ternis.link</span>
                </a>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700 dark:text-emerald-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Gateway Active
                </span>
            </div>

            <div class="flex items-center gap-3">
                @if ($isAdmin)
                    <a
                        href="{{ route('protected.debug') }}"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-500/20 dark:text-amber-300 transition-colors"
                        title="Administrator diagnostics console"
                    >
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        Admin Debug Mode
                    </a>
                @endif

                <a
                    href="https://docs.ternis.link"
                    class="hidden sm:inline-flex text-xs font-medium text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white transition-colors"
                >
                    Documentation
                </a>
                <a
                    href="https://ternis.link"
                    class="hidden sm:inline-flex text-xs font-medium text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white transition-colors"
                >
                    ternis.link
                </a>

                <button
                    type="button"
                    data-theme-toggle
                    class="theme-toggle inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-neutral-300 text-neutral-600 transition-colors hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800"
                    aria-label="Toggle color theme"
                >
                    <svg class="icon-moon h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                    <svg class="icon-sun h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>
                </button>
            </div>
        </div>
    </header>

    <main class="mx-auto flex w-full max-w-6xl flex-1 flex-col px-4 py-12 sm:px-6 sm:py-16">

        {{-- Hero Section --}}
        <section class="mx-auto max-w-3xl text-center">
            <x-ui.badge tone="solid" class="mb-4">Zero-Trust Link &amp; Bot Protection</x-ui.badge>
            <h1 class="font-display text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                Zero-Trust <span class="text-neutral-400 dark:text-neutral-500">Gateway Protection.</span>
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-base leading-relaxed text-neutral-600 dark:text-neutral-400 sm:text-lg">
                <strong class="text-neutral-900 dark:text-white">protected.ternis.link</strong> is the centralized verification gateway for the Ternis short link network. It isolates bot traffic, filters suspicious automated scripts, and enforces Cloudflare Turnstile human verification.
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a
                    href="#signature-inspector"
                    class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-neutral-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100 transition-all active:scale-[0.99]"
                >
                    Test Client Signature
                </a>
                <a
                    href="#rid-inspector"
                    class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-5 py-2.5 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800 transition-all"
                >
                    Request ID Lookup
                </a>
                <a
                    href="https://ternis.link"
                    class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-5 py-2.5 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800 transition-all"
                >
                    ternis.link Home
                </a>
                @if ($isAdmin)
                    <a
                        href="{{ route('protected.debug') }}"
                        class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-amber-500/10 border border-amber-500/30 px-5 py-2.5 text-sm font-semibold text-amber-800 dark:text-amber-300 hover:bg-amber-500/20 transition-all"
                    >
                        ⚡ Admin Debug Console
                    </a>
                @endif
            </div>
        </section>

        {{-- Live Edge Telemetry Strip --}}
        <section class="mt-14 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-2xl border border-neutral-200/80 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Human Verification</span>
                <p class="mt-1 font-display text-lg font-bold text-neutral-900 dark:text-white sm:text-xl">Cloudflare Turnstile</p>
                <p class="mt-1 text-xs text-neutral-500">Privacy-preserving challenge</p>
            </div>
            <div class="rounded-2xl border border-neutral-200/80 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Client Triage</span>
                <p class="mt-1 font-display text-lg font-bold text-neutral-900 dark:text-white sm:text-xl">AI Signature Scoring</p>
                <p class="mt-1 text-xs text-neutral-500">Behavioral classification</p>
            </div>
            <div class="rounded-2xl border border-neutral-200/80 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Smart Memory</span>
                <p class="mt-1 font-display text-lg font-bold text-neutral-900 dark:text-white sm:text-xl">{{ $stats['remember_minutes'] }} Minutes</p>
                <p class="mt-1 text-xs text-neutral-500">Encrypted HMAC cookie</p>
            </div>
            <div class="rounded-2xl border border-neutral-200/80 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Velocity Shield</span>
                <p class="mt-1 font-display text-lg font-bold text-neutral-900 dark:text-white sm:text-xl">Sliding-Window</p>
                <p class="mt-1 text-xs text-neutral-500">Sub-15ms edge routing</p>
            </div>
        </section>

        {{-- Admin Diagnostic Banner (If signed in as Admin) --}}
        @if ($isAdmin)
            <section class="mt-8 rounded-2xl border border-amber-500/30 bg-amber-50/50 p-5 dark:border-amber-500/20 dark:bg-amber-950/20">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-800 dark:text-amber-300">Admin Diagnostics Active</span>
                        </div>
                        <p class="mt-1 text-sm text-amber-900 dark:text-amber-200">
                            You are signed in as an administrator. You have full access to real-time request inspection, HMAC signature generation, AI request analysis, and rate-limit controls.
                        </p>
                    </div>
                    <a
                        href="{{ route('protected.debug') }}"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-amber-500 transition-colors"
                    >
                        Launch Debug Console ↗
                    </a>
                </div>
            </section>
        @endif

        {{-- Interactive Tool 1: Client Signature & Bot Classifier --}}
        <section id="signature-inspector" class="mt-12 rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-8">
            <div class="max-w-2xl">
                <span class="text-xs font-bold uppercase tracking-wider text-violet-600 dark:text-violet-400">Interactive Security Utility</span>
                <h2 class="mt-1 font-display text-2xl font-bold tracking-tight">Client Signature &amp; Bot Classifier</h2>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                    Test your current browser signature or evaluate custom User-Agent strings against the edge gateway triage pipeline.
                </p>
            </div>

            <div class="mt-6 space-y-4">
                <div>
                    <label for="test-ua-input" class="block text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:text-neutral-400">
                        User-Agent String to Classify
                    </label>
                    <div class="mt-1.5 flex flex-col gap-2 sm:flex-row">
                        <input
                            type="text"
                            id="test-ua-input"
                            value="{{ $stats['visitor_ua'] }}"
                            class="flex-1 rounded-xl border border-neutral-300 bg-neutral-50 px-3.5 py-2.5 font-mono text-xs text-neutral-900 placeholder:text-neutral-400 focus:border-neutral-900 focus:bg-white focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white dark:focus:border-white"
                        >
                        <button
                            type="button"
                            id="run-classify-btn"
                            class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-neutral-900 px-5 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100 transition-all active:scale-[0.98]"
                        >
                            Analyze Signature
                        </button>
                    </div>
                </div>

                {{-- Presets --}}
                <div class="flex flex-wrap items-center gap-2 pt-1 text-xs text-neutral-500">
                    <span class="font-medium">Presets:</span>
                    <button type="button" class="preset-btn rounded-lg border border-neutral-200 bg-neutral-100 px-2.5 py-1 text-xs hover:border-neutral-400 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300" data-ua="{{ $stats['visitor_ua'] }}">Current Browser</button>
                    <button type="button" class="preset-btn rounded-lg border border-neutral-200 bg-neutral-100 px-2.5 py-1 text-xs hover:border-neutral-400 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300" data-ua="curl/8.4.0">cURL 8.4</button>
                    <button type="button" class="preset-btn rounded-lg border border-neutral-200 bg-neutral-100 px-2.5 py-1 text-xs hover:border-neutral-400 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300" data-ua="python-requests/2.31.0">Python Requests</button>
                    <button type="button" class="preset-btn rounded-lg border border-neutral-200 bg-neutral-100 px-2.5 py-1 text-xs hover:border-neutral-400 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300" data-ua="Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)">Googlebot</button>
                    <button type="button" class="preset-btn rounded-lg border border-neutral-200 bg-neutral-100 px-2.5 py-1 text-xs hover:border-neutral-400 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300" data-ua="Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 HeadlessChrome/120.0.0.0 Safari/537.36">HeadlessChrome</button>
                    <button type="button" class="preset-btn rounded-lg border border-neutral-200 bg-neutral-100 px-2.5 py-1 text-xs hover:border-neutral-400 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300" data-ua="sqlmap/1.7.11#stable (https://sqlmap.org)">sqlmap exploit scanner</button>
                </div>

                {{-- Live Results Card --}}
                <div id="classify-result" class="mt-4 hidden rounded-2xl border border-neutral-200 bg-neutral-50 p-5 dark:border-neutral-800 dark:bg-neutral-950">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200/80 pb-4 dark:border-neutral-800">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-neutral-400">Classified Client Type</span>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span id="result-verdict-badge" class="rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wide"></span>
                                <span id="result-mode-badge" class="text-[11px] font-mono text-neutral-500"></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-neutral-400">Gateway Decision</span>
                            <p id="result-decision" class="font-display text-sm font-bold sm:text-base"></p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <span class="text-xs font-semibold text-neutral-600 dark:text-neutral-400">Bot Confidence Probability:</span>
                            <div class="mt-2 flex items-center gap-3">
                                <div class="h-2.5 flex-1 rounded-full bg-neutral-200 dark:bg-neutral-800 overflow-hidden">
                                    <div id="result-score-bar" class="h-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                                <span id="result-score-text" class="font-mono text-xs font-bold text-neutral-900 dark:text-white">0%</span>
                            </div>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-neutral-600 dark:text-neutral-400">Edge Signal Assessment:</span>
                            <ul id="result-reasons-list" class="mt-1 list-disc list-inside text-xs text-neutral-600 dark:text-neutral-400 space-y-1"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Interactive Tool 2: Request ID (rid) Lookup --}}
        <section id="rid-inspector" class="mt-8 rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-8">
            <div class="max-w-2xl">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Challenge Inspector</span>
                <h2 class="mt-1 font-display text-2xl font-bold tracking-tight">Request ID (rid) Lookup</h2>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                    Received a challenge page? Paste the Request ID (<code class="font-mono text-neutral-800 dark:text-neutral-200">rid</code>) to inspect token validity, format specifications, and expiration rules.
                </p>
            </div>

            <div class="mt-6">
                <div class="flex flex-col gap-2 sm:flex-row">
                    <input
                        type="text"
                        id="lookup-rid-input"
                        placeholder="e.g. rid_01J9H5W6EZTRF4D9KXM2QG8VYB"
                        class="flex-1 rounded-xl border border-neutral-300 bg-neutral-50 px-3.5 py-2.5 font-mono text-xs text-neutral-900 placeholder:text-neutral-400 focus:border-neutral-900 focus:bg-white focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white dark:focus:border-white"
                    >
                    <button
                        type="button"
                        id="run-rid-lookup-btn"
                        class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-neutral-100 px-5 py-2.5 text-xs font-semibold text-neutral-800 hover:bg-neutral-200 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700 transition-all"
                    >
                        Verify Request ID
                    </button>
                </div>

                <div id="rid-lookup-result" class="mt-4 hidden rounded-2xl border border-neutral-200 bg-neutral-50 p-4 text-xs font-mono dark:border-neutral-800 dark:bg-neutral-950">
                    <p id="rid-lookup-message" class="font-semibold"></p>
                    <p id="rid-lookup-details" class="mt-1 text-neutral-500"></p>
                </div>
            </div>
        </section>

        {{-- Architecture & Defense Rings --}}
        <section class="mt-16">
            <div class="text-center">
                <span class="text-xs font-bold uppercase tracking-wider text-neutral-400">Zero-Trust Architecture</span>
                <h2 class="mt-1 font-display text-3xl font-bold tracking-tight">The Four Rings of Edge Mitigation</h2>
                <p class="mx-auto mt-3 max-w-2xl text-sm text-neutral-500 dark:text-neutral-400">
                    Traffic flowing through ternis.link is evaluated across layered checkpoints designed to stop abuse without impacting clean browser redirects.
                </p>
            </div>

            <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-neutral-100 font-mono text-xs font-bold text-neutral-900 dark:bg-neutral-800 dark:text-white">01</span>
                    <h3 class="mt-3 font-display text-base font-bold">Bot Protection</h3>
                    <p class="mt-2 text-xs leading-relaxed text-neutral-500 dark:text-neutral-400">
                        Links with bot protection enabled verify visitor authenticity before dispatching the redirect, shielding destination servers from web scrapers and rogue crawlers.
                    </p>
                </div>

                <div class="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-neutral-100 font-mono text-xs font-bold text-neutral-900 dark:bg-neutral-800 dark:text-white">02</span>
                    <h3 class="mt-3 font-display text-base font-bold">Suspicious UA Screening</h3>
                    <p class="mt-2 text-xs leading-relaxed text-neutral-500 dark:text-neutral-400">
                        Automated client signatures, curl commands, and headless scripts are directed to a transparent challenge gate with unique Request IDs (`rid`) and Turnstile verification.
                    </p>
                </div>

                <div class="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-neutral-100 font-mono text-xs font-bold text-neutral-900 dark:bg-neutral-800 dark:text-white">03</span>
                    <h3 class="mt-3 font-display text-base font-bold">Smart Memory</h3>
                    <p class="mt-2 text-xs leading-relaxed text-neutral-500 dark:text-neutral-400">
                        Verified human visitors receive an encrypted session token valid for 2 hours, eliminating repetitive challenges while preserving zero third-party tracking.
                    </p>
                </div>

                <div class="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-neutral-100 font-mono text-xs font-bold text-neutral-900 dark:bg-neutral-800 dark:text-white">04</span>
                    <h3 class="mt-3 font-display text-base font-bold">AI Threat Shield</h3>
                    <p class="mt-2 text-xs leading-relaxed text-neutral-500 dark:text-neutral-400">
                        Continuously analyzes destination patterns and request anomalies, quarantining phishing campaigns and malware before harm occurs.
                    </p>
                </div>
            </div>
        </section>

        {{-- Technical Specifications Grid --}}
        <section class="mt-14 rounded-3xl border border-neutral-200 bg-neutral-100/50 p-6 dark:border-neutral-800 dark:bg-neutral-900/50 sm:p-8">
            <h3 class="font-display text-lg font-bold">Gateway Specifications &amp; Parameters</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3 text-xs">
                <div class="rounded-xl border border-neutral-200 bg-white p-3.5 dark:border-neutral-800 dark:bg-neutral-900">
                    <span class="font-semibold text-neutral-600 dark:text-neutral-400">HMAC-SHA256 Signatures:</span>
                    <p class="mt-1 text-neutral-800 dark:text-neutral-200">Challenge URLs are bound to <code>link_id</code>, <code>rid</code>, and a 15-minute expiration timestamp.</p>
                </div>
                <div class="rounded-xl border border-neutral-200 bg-white p-3.5 dark:border-neutral-800 dark:bg-neutral-900">
                    <span class="font-semibold text-neutral-600 dark:text-neutral-400">Verification Rate Limit:</span>
                    <p class="mt-1 text-neutral-800 dark:text-neutral-200">Strict limit of <strong>{{ $stats['rate_limit_max'] }} attempts per minute</strong> per visitor IP to prevent brute-force attacks.</p>
                </div>
                <div class="rounded-xl border border-neutral-200 bg-white p-3.5 dark:border-neutral-800 dark:bg-neutral-900">
                    <span class="font-semibold text-neutral-600 dark:text-neutral-400">Fail-Open Resilience:</span>
                    <p class="mt-1 text-neutral-800 dark:text-neutral-200">If third-party validation providers experience downtime, legitimate user redirects remain unimpeded.</p>
                </div>
            </div>
        </section>

    </main>

    {{-- Footer without Brand Icon --}}
    <footer class="border-t border-neutral-200 py-8 dark:border-neutral-800">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 sm:flex-row sm:px-6">
            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                protected.ternis.link &mdash; official security gateway by <a href="https://ternis.link" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">ternis.link</a> &middot;
                <a href="https://ternis.link/pages/legal/privacy" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">privacy</a> &middot;
                <a href="https://ternis.link/pages/legal/terms" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">terms</a>
            </p>
            <div class="flex items-center gap-4 text-xs text-neutral-500">
                <a href="https://docs.ternis.link" class="hover:text-neutral-900 dark:hover:text-white">Documentation</a>
                <a href="https://href.nz" class="hover:text-neutral-900 dark:hover:text-white">href.nz</a>
                <a href="https://ternis.dev" class="hover:text-neutral-900 dark:hover:text-white">ternis.dev</a>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            // Preset UA button handlers
            document.querySelectorAll('.preset-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const ua = btn.getAttribute('data-ua');
                    if (ua) {
                        document.getElementById('test-ua-input').value = ua;
                        classifyUserAgent(ua);
                    }
                });
            });

            // Run classify button handler
            const classifyBtn = document.getElementById('run-classify-btn');
            classifyBtn?.addEventListener('click', () => {
                const ua = document.getElementById('test-ua-input').value;
                classifyUserAgent(ua);
            });

            function classifyUserAgent(ua) {
                const resultBox = document.getElementById('classify-result');
                const verdictBadge = document.getElementById('result-verdict-badge');
                const modeBadge = document.getElementById('result-mode-badge');
                const decision = document.getElementById('result-decision');
                const scoreBar = document.getElementById('result-score-bar');
                const scoreText = document.getElementById('result-score-text');
                const reasonsList = document.getElementById('result-reasons-list');

                classifyBtn.disabled = true;
                classifyBtn.textContent = 'Classifying...';

                fetch('{{ route("protected.classify") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ user_agent: ua })
                })
                .then(r => r.json())
                .then(data => {
                    resultBox.classList.remove('hidden');

                    // Verdict badge
                    verdictBadge.textContent = (data.verdict || 'unknown').replace('_', ' ');
                    verdictBadge.className = 'rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wide ';
                    if (data.verdict === 'legitimate_browser' || data.verdict === 'verified_crawler') {
                        verdictBadge.className += 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300';
                    } else if (data.verdict === 'benign_tool') {
                        verdictBadge.className += 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300';
                    } else {
                        verdictBadge.className += 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300';
                    }

                    modeBadge.textContent = data.mode === 'ai_live' ? '⚡ AI Model' : '🛡️ Heuristic Rule';

                    // Decision text
                    const action = data.recommended_action || 'ALLOW';
                    if (action === 'ALLOW') {
                        decision.innerHTML = '<span class="text-emerald-600 dark:text-emerald-400">PASSED (Direct 302)</span>';
                    } else if (action === 'CHALLENGE_TURNSTILE') {
                        decision.innerHTML = '<span class="text-amber-600 dark:text-amber-400">CHALLENGE (Turnstile)</span>';
                    } else {
                        decision.innerHTML = '<span class="text-rose-600 dark:text-rose-400">ISOLATE (Client Filter)</span>';
                    }

                    // Score bar
                    const score = data.score || 0;
                    scoreBar.style.width = score + '%';
                    scoreText.textContent = score + '%';
                    if (score < 40) {
                        scoreBar.className = 'h-full bg-emerald-500';
                    } else if (score < 75) {
                        scoreBar.className = 'h-full bg-amber-500';
                    } else {
                        scoreBar.className = 'h-full bg-rose-500';
                    }

                    // Reasons
                    reasonsList.innerHTML = '';
                    const reasons = data.reasons || [];
                    if (reasons.length === 0) {
                        reasons.push('Standard interactive browser header verified');
                    }
                    reasons.forEach(r => {
                        const li = document.createElement('li');
                        li.textContent = r;
                        reasonsList.appendChild(li);
                    });
                })
                .catch(err => {
                    alert('Error classifying signature: ' + err);
                })
                .finally(() => {
                    classifyBtn.disabled = false;
                    classifyBtn.textContent = 'Analyze Signature';
                });
            }

            // RID lookup handler
            const lookupBtn = document.getElementById('run-rid-lookup-btn');
            lookupBtn?.addEventListener('click', () => {
                const rid = document.getElementById('lookup-rid-input').value;
                const resultBox = document.getElementById('rid-lookup-result');
                const msgEl = document.getElementById('rid-lookup-message');
                const detailsEl = document.getElementById('rid-lookup-details');

                if (!rid) return;

                fetch('{{ route("protected.lookup-rid") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ rid: rid })
                })
                .then(r => r.json())
                .then(data => {
                    resultBox.classList.remove('hidden');
                    if (data.valid_format) {
                        msgEl.className = 'font-semibold text-emerald-700 dark:text-emerald-400';
                        msgEl.textContent = '✓ ' + data.message;
                        detailsEl.textContent = 'Generator: ' + data.generated_type + ' | Expiration Window: ' + data.time_window_seconds + ' seconds';
                    } else {
                        msgEl.className = 'font-semibold text-rose-700 dark:text-rose-400';
                        msgEl.textContent = '✕ ' + data.message;
                        detailsEl.textContent = 'Valid Request IDs start with "rid_" and contain a valid ULID.';
                    }
                })
                .catch(err => {
                    alert('Lookup error: ' + err);
                });
            });
        });
    </script>
</body>
</html>
