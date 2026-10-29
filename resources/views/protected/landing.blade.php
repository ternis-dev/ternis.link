<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>protected.ternis.link — Zero-Trust Link & Bot Protection</title>
    <meta name="description" content="Dedicated verification gateway for the Ternis link network: automated bot protection, edge rate-limiting, and Turnstile security checks.">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
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
<body class="flex min-h-screen flex-col bg-neutral-50 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">
    <header class="border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="mx-auto flex h-16 max-w-4xl items-center justify-between gap-3 px-4 sm:px-6">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-neutral-900 text-white dark:bg-white dark:text-neutral-900 font-mono font-bold text-sm">
                    🔒
                </span>
                <span class="font-display text-xl font-bold tracking-tight">protected<span>.ternis.link</span></span>
            </div>
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    data-theme-toggle
                    class="theme-toggle inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-neutral-300 text-neutral-600 transition-colors hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800"
                    aria-label="Toggle color theme"
                >
                    <svg class="icon-moon h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                    <svg class="icon-sun h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>
                </button>
                <x-ui.badge tone="solid">Gateway Service</x-ui.badge>
            </div>
        </div>
    </header>

    <main class="mx-auto flex w-full max-w-4xl flex-1 flex-col justify-center px-4 py-14 sm:px-6 sm:py-20">
        <section class="text-center">
            <x-ui.badge tone="solid" class="mb-4">Link Security &amp; Bot Mitigation</x-ui.badge>
            <h1 class="font-display text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                Zero-Trust <span class="text-neutral-400 dark:text-neutral-500">Gateway Protection.</span>
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-base text-neutral-600 dark:text-neutral-400 leading-relaxed">
                <strong class="text-neutral-900 dark:text-white">protected.ternis.link</strong> is the centralized verification gateway for the Ternis short link network. It isolates bot traffic, filters suspicious automated scripts, and enforces Cloudflare Turnstile human verification.
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <x-ui.button href="https://ternis.link" variant="primary" size="lg">ternis.link Home</x-ui.button>
                <x-ui.button href="https://href.nz" variant="secondary" size="lg">href.nz Shortener</x-ui.button>
                <x-ui.button href="https://docs.ternis.link" variant="secondary" size="lg">Documentation</x-ui.button>
            </div>
        </section>

        <section class="mt-14 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.card>
                <div class="flex items-center gap-2.5">
                    <svg class="h-5 w-5 shrink-0 text-neutral-800 dark:text-neutral-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <h2 class="font-display text-base font-bold">Bot Protection</h2>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                    Links with bot protection enabled verify visitor authenticity before dispatching the redirect, shielding destination servers from web scrapers and rogue crawlers.
                </p>
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-center gap-2.5">
                    <svg class="h-5 w-5 shrink-0 text-neutral-800 dark:text-neutral-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <h2 class="font-display text-base font-bold">Suspicious UA Screening</h2>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                    Automated client signatures, curl commands, and headless scripts are directed to a transparent challenge gate with unique Request IDs (`rid`) and Turnstile verification.
                </p>
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-center gap-2.5">
                    <svg class="h-5 w-5 shrink-0 text-neutral-800 dark:text-neutral-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <h2 class="font-display text-base font-bold">Smart Memory</h2>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                    Verified human visitors receive an encrypted session token valid for 2 hours, eliminating repetitive challenges while preserving zero third-party tracking.
                </p>
            </x-ui.card>
        </section>
    </main>

    <footer class="border-t border-neutral-200 py-6 dark:border-neutral-800">
        <p class="text-center text-xs text-neutral-500 dark:text-neutral-400">
            protected.ternis.link &mdash; official security gateway by <a href="https://ternis.link" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">ternis.link</a> &middot;
            <a href="https://ternis.link/pages/legal/privacy" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">privacy</a> &middot;
            <a href="https://ternis.link/pages/legal/terms" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">terms</a>
        </p>
    </footer>
</body>
</html>
