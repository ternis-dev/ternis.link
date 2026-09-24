<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>href.nz — long links go in, short links come out</title>
    <meta name="description" content="href.nz — the no-account link shortener. Paste a long link, get an 8-character short link back. Free, fast, no sign-up.">
    <meta name="theme-color" content="#09090b">
    <link rel="canonical" href="https://href.nz/">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="href.nz">
    <meta property="og:title" content="href.nz — long links go in, short links come out">
    <meta property="og:description" content="Paste a long link, get an 8-character href.nz link back. No account needed.">
    <meta property="og:url" content="https://href.nz/">
    <meta name="twitter:card" content="summary">
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
    @livewireStyles
</head>
<body class="flex min-h-screen flex-col">
    <a href="#shorten" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-neutral-900 focus:px-4 focus:py-2 focus:text-white">Skip to the shortener</a>

    <header class="border-b border-neutral-200 dark:border-neutral-800">
        <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="/" class="font-display text-2xl font-bold tracking-tight" aria-label="href.nz home">href<span>.nz</span></a>
            <nav class="flex items-center gap-2" aria-label="Account">
                <button
                    type="button"
                    data-theme-toggle
                    class="theme-toggle inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg border border-neutral-300 text-neutral-600 transition-colors hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-900"
                    aria-label="Toggle color theme"
                >
                    <svg class="icon-moon h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M17.25 14.2A7.5 7.5 0 0 1 5.8 2.75a.75.75 0 0 0-1-1A9 9 0 1 0 18.25 15.2a.75.75 0 0 0-1-1Z"/></svg>
                    <svg class="icon-sun h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 2a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 2Zm0 13a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm6.28-8.72a.75.75 0 0 1 0 1.06l-1.06 1.06a.75.75 0 1 1-1.06-1.06l1.06-1.06a.75.75 0 0 1 1.06 0ZM3.78 6.28a.75.75 0 0 1 1.06 0l1.06 1.06a.75.75 0 1 1-1.06 1.06L3.78 7.34a.75.75 0 0 1 0-1.06Zm11.44 7.44a.75.75 0 1 1-1.06 1.06l-1.06-1.06a.75.75 0 1 1 1.06-1.06l1.06 1.06ZM4.84 13.22a.75.75 0 0 1 0 1.06l-1.06 1.06a.75.75 0 1 1-1.06-1.06l1.06-1.06a.75.75 0 0 1 1.06 0ZM10 15.25a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0V16a.75.75 0 0 1 .75-.75ZM16.22 3.78a.75.75 0 0 1 0 1.06L15.16 5.9a.75.75 0 1 1-1.06-1.06l1.06-1.06a.75.75 0 0 1 1.06 0ZM5.9 4.84a.75.75 0 0 1-1.06 1.06L3.78 4.84a.75.75 0 1 1 1.06-1.06l1.06 1.06Z" clip-rule="evenodd"/></svg>
                </button>
                @auth
                    <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}" size="sm">Open dashboard →</x-ui.button>
                @else
                    <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}" size="sm" variant="ghost">Members log in →</x-ui.button>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-3xl flex-1 px-4 sm:px-6">
        <section class="py-14 text-center sm:py-20">
            <x-ui.badge tone="solid" class="mb-5">№ 001 — for everyone with an ugly-long link</x-ui.badge>
            <h1 id="page-title" class="font-display text-5xl font-bold tracking-tight sm:text-6xl">long links go in.<br><span class="text-neutral-400 dark:text-neutral-500">short links come out.</span></h1>
            <p class="mx-auto mt-4 max-w-xl text-neutral-500 dark:text-neutral-400">Drop yours in the box below and walk away with an 8-character href.nz link. Free, instant, no sign-up.</p>

            <ol class="mt-8 flex flex-wrap items-center justify-center gap-2 text-sm" aria-label="How it works">
                <li class="flex items-center gap-2 rounded-full border border-neutral-300 px-4 py-1.5 dark:border-neutral-700"><strong class="font-display">1</strong> Paste the long URL</li>
                <li class="flex items-center gap-2 rounded-full border border-neutral-300 px-4 py-1.5 dark:border-neutral-700"><strong class="font-display">2</strong> Hit Shorten</li>
                <li class="flex items-center gap-2 rounded-full border border-neutral-300 px-4 py-1.5 dark:border-neutral-700"><strong class="font-display">3</strong> Copy &amp; share</li>
            </ol>
        </section>

        <section id="shorten" aria-label="Shorten a link" class="scroll-mt-6">
            <livewire:public.shorten-form />
        </section>

        <ul class="mt-6 flex flex-wrap justify-center gap-2 text-xs text-neutral-500 dark:text-neutral-400" aria-label="At a glance">
            <li class="rounded-full bg-neutral-100 px-3 py-1 dark:bg-neutral-900">No account needed</li>
            <li class="rounded-full bg-neutral-100 px-3 py-1 dark:bg-neutral-900">8-character links</li>
            <li class="rounded-full bg-neutral-100 px-3 py-1 dark:bg-neutral-900">50 / day fair use</li>
        </ul>

        <aside class="mt-14 grid grid-cols-1 gap-4 sm:grid-cols-3" aria-label="Good to know">
            <x-ui.card>
                <h2 class="text-sm font-semibold">Members get more</h2>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Guests get auto-made codes — picking your own is a members' perk. <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="underline underline-offset-2">Log in</a> for custom slugs, shorter links &amp; click stats.</p>
            </x-ui.card>
            <x-ui.card>
                <h2 class="text-sm font-semibold">Fair &amp; private</h2>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Fair use: 50 links a day per guest. Nothing of yours is kept but a hashed IP for counting. Links stay active as long as they're legit — abuse gets removed.</p>
            </x-ui.card>
            <x-ui.card>
                <h2 class="text-sm font-semibold">Official business?</h2>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Need a link people can trust is really from you? That's next door on <a href="https://href.re" class="underline underline-offset-2">href.re</a> — verified, analytics-backed, business only.</p>
            </x-ui.card>
        </aside>
    </main>

    <footer class="mt-14 border-t border-neutral-200 py-6 dark:border-neutral-800">
        <p class="text-center text-xs text-neutral-500 dark:text-neutral-500">
            made by ternis.link from <a href="https://ternis.dev" class="underline underline-offset-2">ternis.dev</a> · hosted on <a href="https://ternis.net" class="underline underline-offset-2">ternis.net</a> · official links on <a href="https://href.re" class="underline underline-offset-2">href.re</a>
        </p>
    </footer>

    @livewireScripts
</body>
</html>
