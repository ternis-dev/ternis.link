<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>href.re — Official business links</title>
    <meta name="description" content="href.re — reserved for official ternis business links. Verified, trusted, analytics-backed.">
    <link rel="canonical" href="https://href.re/">
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
<body class="flex min-h-screen flex-col">
    <header class="border-b border-neutral-200 dark:border-neutral-800">
        <div class="mx-auto flex h-16 max-w-4xl items-center justify-between gap-3 px-4 max-sm:h-auto max-sm:flex-wrap max-sm:py-3 sm:px-6">
            <a href="/" class="font-display text-2xl font-bold tracking-tight">href<span>.re</span></a>
            <div class="flex flex-wrap items-center justify-end gap-3">
                <button
                    type="button"
                    data-theme-toggle
                    class="theme-toggle inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-neutral-300 text-neutral-600 transition-colors hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-900"
                    aria-label="Toggle color theme"
                >
                    <svg class="icon-moon h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                    <svg class="icon-sun h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>
                </button>
                <a href="https://href.nz" class="text-xs text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white transition-colors" rel="noopener">Public Shortener (href.nz) &rarr;</a>
                <x-ui.badge tone="solid">Official · Business only</x-ui.badge>
            </div>
        </div>
    </header>

    <main class="mx-auto flex w-full max-w-4xl flex-1 flex-col justify-center px-4 py-12 sm:px-6 sm:py-16">
        <section class="text-center">
            <x-ui.badge tone="solid" class="mb-4">Official Infrastructure</x-ui.badge>
            <h1 class="font-display text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">Official links, <span class="text-neutral-400 dark:text-neutral-500">recognizable.</span></h1>
            <p class="mx-auto mt-4 max-w-xl text-neutral-500 dark:text-neutral-400">
                <strong class="text-neutral-900 dark:text-white">href.re</strong> is reserved for official ternis business links. No public guest shortening here &mdash; every redirect is provisioned and audited by the ternis team.
            </p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                @auth
                    <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/') }}" variant="primary" size="lg">Go to Dashboard</x-ui.button>
                @else
                    <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}" variant="primary" size="lg">Sign in with Ternis Auth</x-ui.button>
                @endauth
                <x-ui.button href="https://href.nz" variant="secondary" size="lg" rel="noopener">Open href.nz</x-ui.button>
            </div>
            <ul class="mt-8 flex flex-wrap justify-center gap-2 text-xs text-neutral-500 dark:text-neutral-400">
                <li class="rounded-full bg-neutral-100 px-3 py-1 dark:bg-neutral-900">&check; Verified sender</li>
                <li class="rounded-full bg-neutral-100 px-3 py-1 dark:bg-neutral-900">&check; AI threat &amp; spam shield</li>
                <li class="rounded-full bg-neutral-100 px-3 py-1 dark:bg-neutral-900">&check; Click analytics</li>
                <li class="rounded-full bg-neutral-100 px-3 py-1 dark:bg-neutral-900">&check; Abuse-monitored</li>
                <li class="rounded-full bg-neutral-100 px-3 py-1 dark:bg-neutral-900">&check; Zero ad trackers</li>
            </ul>
        </section>

        <section class="mt-12 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.card>
                <div class="flex items-center gap-2.5">
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <div class="font-display text-base font-bold">Business only</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Public guest shortening is disabled on this domain. Need a quick public short link? Use <a href="https://href.nz" class="font-medium text-neutral-900 underline underline-offset-2 hover:text-black dark:text-white dark:hover:text-neutral-200" rel="noopener">href.nz</a>.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center gap-2.5">
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                    <div class="font-display text-base font-bold">Trusted by default</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Recipients can trust href.re redirects &mdash; issued internally, protected by TypeSafe AI threat scanning, and free of tracker walls or ads.</p>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center gap-2.5">
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h16"/><path d="M7 20v-6M12 20V6M17 20v-9"/></svg>
                    <div class="font-display text-base font-bold">Measured</div>
                </div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Every redirect records analytics asynchronously with non-reversible IP hashing for aggregate insights.</p>
            </x-ui.card>
        </section>
    </main>

    <footer class="border-t border-neutral-200 py-6 dark:border-neutral-800">
        <p class="text-center text-xs text-neutral-500 dark:text-neutral-400">
            href.re &mdash; official business shortener by <a href="https://ternis.link" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white" rel="noopener">ternis.link</a> &middot; 
            public links: <a href="https://href.nz" class="font-semibold underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white" rel="noopener">href.nz</a> &middot; 
            <a href="https://ternis.link/pages/legal/privacy" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">privacy</a> &middot; 
            <a href="https://ternis.link/pages/legal/terms" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">terms</a> &middot; 
            <a href="{{ \App\Support\DomainUrls::impressum('href.re', 'en') }}" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">imprint</a>
        </p>
    </footer>
</body>
</html>
