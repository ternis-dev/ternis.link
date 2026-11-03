<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>protected.ternis.link — Zero-Trust Link & Bot Protection</title>
    <meta name="description" content="Centralized verification gateway for the ternis.link network: zero-trust bot protection and privacy-first human challenges.">
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

    {{-- Minimal Header --}}
    <header class="border-b border-neutral-200/80 bg-white/80 backdrop-blur-md dark:border-neutral-800/80 dark:bg-neutral-900/80">
        <div class="mx-auto flex h-16 max-w-4xl items-center justify-between gap-4 px-4 sm:px-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('protected.index') }}" class="font-display text-base font-bold tracking-tight text-neutral-900 dark:text-white sm:text-lg">
                    protected<span class="text-neutral-400 dark:text-neutral-500">.ternis.link</span>
                </a>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:text-emerald-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Gateway Active
                </span>
            </div>

            <div class="flex items-center gap-3">
                @if ($isAdmin)
                    <a
                        href="{{ route('protected.debug') }}"
                        class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:text-amber-300 hover:bg-amber-500/20 transition-colors"
                    >
                        ⚡ Admin Debug
                    </a>
                @endif

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

    <main class="mx-auto flex w-full max-w-4xl flex-1 flex-col justify-center px-4 py-12 sm:px-6 sm:py-16">

        {{-- Hero --}}
        <section class="text-center">
            <x-ui.badge tone="solid" class="mb-4">Zero-Trust Link &amp; Bot Protection</x-ui.badge>
            <h1 class="font-display text-4xl font-bold tracking-tight sm:text-5xl">
                Zero-Trust <span class="text-neutral-400 dark:text-neutral-500">Gateway Protection.</span>
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-base text-neutral-600 dark:text-neutral-400 leading-relaxed">
                <strong class="text-neutral-900 dark:text-white">protected.ternis.link</strong> is the centralized verification gateway for the Ternis short link network. It isolates bot traffic, filters suspicious automated scripts, and enforces Cloudflare Turnstile human verification.
            </p>
        </section>

        {{-- Simplified Signature Checker --}}
        <section class="mt-10 rounded-2xl border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-neutral-100 pb-3 dark:border-neutral-800">
                <span class="text-xs font-bold uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                    Client Signature &amp; Bot Classifier
                </span>
                <span class="text-[11px] text-neutral-400 font-mono">
                    IP: {{ $stats['visitor_ip'] }}
                </span>
            </div>

            <div class="mt-4 space-y-3">
                <div class="flex flex-col gap-2 sm:flex-row">
                    <input
                        type="text"
                        id="test-ua-input"
                        value="{{ $stats['visitor_ua'] }}"
                        class="flex-1 rounded-xl border border-neutral-300 bg-neutral-50 px-3.5 py-2 text-xs font-mono text-neutral-900 focus:border-neutral-900 focus:bg-white focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white dark:focus:border-white"
                        placeholder="Enter User-Agent string"
                    >
                    <button
                        type="button"
                        id="run-classify-btn"
                        class="cursor-pointer rounded-xl bg-neutral-900 px-5 py-2 text-xs font-semibold text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100 transition-colors"
                    >
                        Test Signature
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-1.5 text-xs text-neutral-500">
                    <span class="text-[11px]">Presets:</span>
                    <button type="button" class="preset-btn rounded border border-neutral-200 px-2 py-0.5 text-[11px] hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800" data-ua="{{ $stats['visitor_ua'] }}">Current Browser</button>
                    <button type="button" class="preset-btn rounded border border-neutral-200 px-2 py-0.5 text-[11px] hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800" data-ua="curl/8.4.0">cURL</button>
                    <button type="button" class="preset-btn rounded border border-neutral-200 px-2 py-0.5 text-[11px] hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800" data-ua="python-requests/2.31.0">Python</button>
                    <button type="button" class="preset-btn rounded border border-neutral-200 px-2 py-0.5 text-[11px] hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-800" data-ua="Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)">Googlebot</button>
                </div>

                {{-- Result --}}
                <div id="classify-result" class="hidden mt-3 rounded-xl border border-neutral-200 bg-neutral-50 p-4 text-xs dark:border-neutral-800 dark:bg-neutral-950">
                    <div class="flex items-center justify-between">
                        <span id="result-verdict-badge" class="rounded px-2 py-0.5 font-mono text-[11px] font-bold uppercase"></span>
                        <span id="result-decision" class="font-semibold"></span>
                    </div>
                    <p id="result-detail" class="mt-2 text-neutral-500 text-[11px]"></p>
                </div>
            </div>
        </section>

        {{-- 3 Clean Architectural Pillars --}}
        <section class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.card>
                <h2 class="font-display text-sm font-bold">Bot Protection</h2>
                <p class="mt-1.5 text-xs text-neutral-500 dark:text-neutral-400 leading-relaxed">
                    Protects link destinations from automated crawlers, scrapers, and bot syndicates with Cloudflare Turnstile human verification.
                </p>
            </x-ui.card>

            <x-ui.card>
                <h2 class="font-display text-sm font-bold">Suspicious UA Screening</h2>
                <p class="mt-1.5 text-xs text-neutral-500 dark:text-neutral-400 leading-relaxed">
                    Automated scripts, curl, and headless browsers are screened transparently before reaching origin servers.
                </p>
            </x-ui.card>

            <x-ui.card>
                <h2 class="font-display text-sm font-bold">Smart Memory</h2>
                <p class="mt-1.5 text-xs text-neutral-500 dark:text-neutral-400 leading-relaxed">
                    Verified human visitors receive an encrypted 2-hour session cookie, avoiding repetitive challenges with zero tracking.
                </p>
            </x-ui.card>
        </section>

        {{-- Optional RID check dropdown --}}
        <details class="mt-6 rounded-xl border border-neutral-200 bg-white p-4 text-xs dark:border-neutral-800 dark:bg-neutral-900">
            <summary class="cursor-pointer font-semibold text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-white">
                Request ID (rid) Lookup &middot; Have a challenge token?
            </summary>
            <div class="mt-3 flex gap-2">
                <input
                    type="text"
                    id="lookup-rid-input"
                    placeholder="e.g. rid_01J9H5W6EZTRF4D9KXM2QG8VYB"
                    class="flex-1 rounded-lg border border-neutral-300 bg-neutral-50 px-3 py-1.5 font-mono text-xs dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                >
                <button
                    type="button"
                    id="run-rid-lookup-btn"
                    class="cursor-pointer rounded-lg border border-neutral-300 bg-neutral-100 px-3 py-1.5 text-xs font-semibold hover:bg-neutral-200 dark:border-neutral-700 dark:bg-neutral-800 dark:hover:bg-neutral-700"
                >
                    Verify
                </button>
            </div>
            <p id="rid-lookup-status" class="mt-2 hidden text-[11px] font-mono"></p>
        </details>

    </main>

    {{-- Minimal Footer --}}
    <footer class="border-t border-neutral-200 py-6 dark:border-neutral-800">
        <div class="mx-auto flex max-w-4xl flex-col items-center justify-between gap-3 px-4 sm:flex-row sm:px-6 text-xs text-neutral-500">
            <p>
                protected.ternis.link &mdash; official security gateway by <a href="https://ternis.link" class="underline hover:text-neutral-900 dark:hover:text-white">ternis.link</a>
            </p>
            <div class="flex items-center gap-3">
                <a href="https://docs.ternis.link" class="hover:text-neutral-900 dark:hover:text-white">Docs</a>
                <a href="https://ternis.link/pages/legal/privacy" class="hover:text-neutral-900 dark:hover:text-white">Privacy</a>
                <a href="https://ternis.link/pages/legal/terms" class="hover:text-neutral-900 dark:hover:text-white">Terms</a>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            // Presets
            document.querySelectorAll('.preset-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const ua = btn.getAttribute('data-ua');
                    if (ua) {
                        document.getElementById('test-ua-input').value = ua;
                        classifyUserAgent(ua);
                    }
                });
            });

            // Classifier
            const classifyBtn = document.getElementById('run-classify-btn');
            classifyBtn?.addEventListener('click', () => {
                classifyUserAgent(document.getElementById('test-ua-input').value);
            });

            function classifyUserAgent(ua) {
                const resultBox = document.getElementById('classify-result');
                const badge = document.getElementById('result-verdict-badge');
                const decision = document.getElementById('result-decision');
                const detail = document.getElementById('result-detail');

                classifyBtn.disabled = true;

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
                    badge.textContent = (data.verdict || 'unknown').replace('_', ' ');

                    if (data.recommended_action === 'ALLOW') {
                        badge.className = 'rounded px-2 py-0.5 font-mono text-[11px] font-bold uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
                        decision.innerHTML = '<span class="text-emerald-600 dark:text-emerald-400">PASSED (Direct 302 Redirect)</span>';
                        detail.textContent = 'Standard browser signature verified. Redirect executes with zero latency.';
                    } else if (data.recommended_action === 'CHALLENGE_TURNSTILE') {
                        badge.className = 'rounded px-2 py-0.5 font-mono text-[11px] font-bold uppercase bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
                        decision.innerHTML = '<span class="text-amber-600 dark:text-amber-400">CHALLENGE (Turnstile Required)</span>';
                        detail.textContent = 'Non-interactive or developer client signature. Visitor will be challenged before continuing.';
                    } else {
                        badge.className = 'rounded px-2 py-0.5 font-mono text-[11px] font-bold uppercase bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300';
                        decision.innerHTML = '<span class="text-rose-600 dark:text-rose-400">FILTERED (Suspicious Client Gate)</span>';
                        detail.textContent = 'Automated scraper / crawler pattern matched. Visitor redirected to client challenge.';
                    }
                })
                .finally(() => {
                    classifyBtn.disabled = false;
                });
            }

            // RID lookup
            const lookupBtn = document.getElementById('run-rid-lookup-btn');
            lookupBtn?.addEventListener('click', () => {
                const rid = document.getElementById('lookup-rid-input').value;
                const status = document.getElementById('rid-lookup-status');

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
                    status.classList.remove('hidden');
                    if (data.valid_format) {
                        status.className = 'mt-2 text-[11px] font-mono text-emerald-600 dark:text-emerald-400';
                        status.textContent = '✓ ' + data.message + ' (15-min window)';
                    } else {
                        status.className = 'mt-2 text-[11px] font-mono text-rose-600 dark:text-rose-400';
                        status.textContent = '✕ ' + data.message;
                    }
                });
            });
        });
    </script>
</body>
</html>
