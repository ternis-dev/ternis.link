<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>href.re — Official business links</title>
    <meta name="description" content="href.re — reserved for official ternis business links. Verified, trusted, analytics-backed.">
    <meta name="robots" content="noindex, nofollow">
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
        <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="/" class="font-display text-2xl font-bold tracking-tight">href<span>.re</span></a>
            <x-ui.badge tone="solid">Official · Business only</x-ui.badge>
        </div>
    </header>

    <main class="mx-auto w-full max-w-3xl flex-1 px-4 sm:px-6">
        <section class="py-14 text-center sm:py-20">
            <h1 class="font-display text-5xl font-bold tracking-tight sm:text-6xl">Official links, <span class="text-neutral-400 dark:text-neutral-500">recognizable.</span></h1>
            <p class="mx-auto mt-4 max-w-xl text-neutral-500 dark:text-neutral-400"><strong class="text-neutral-900 dark:text-white">href.re</strong> is reserved for official ternis business links. No public shortening here — every redirect is provisioned and audited by the ternis team.</p>
            <div class="mt-8">
                @auth
                    <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}" variant="primary" size="lg">Go to Dashboard</x-ui.button>
                @else
                    <x-ui.button href="{{ \App\Support\DomainUrls::dashboard('/login') }}" variant="primary" size="lg">Sign in with Ternis Auth</x-ui.button>
                @endauth
            </div>
            <ul class="mt-8 flex flex-wrap justify-center gap-2 text-xs text-neutral-500 dark:text-neutral-400">
                <li class="rounded-full bg-neutral-100 px-3 py-1 dark:bg-neutral-900">✓ Verified sender</li>
                <li class="rounded-full bg-neutral-100 px-3 py-1 dark:bg-neutral-900">✓ Click analytics</li>
                <li class="rounded-full bg-neutral-100 px-3 py-1 dark:bg-neutral-900">✓ Abuse-monitored</li>
            </ul>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.card>
                <h2 class="text-sm font-semibold">Business only</h2>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Public guest shortening is disabled on this domain. Need a public link? Use <strong>href.nz</strong>.</p>
            </x-ui.card>
            <x-ui.card>
                <h2 class="text-sm font-semibold">Trusted by default</h2>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Recipients can trust href.re redirects — they are issued internally and access-controlled.</p>
            </x-ui.card>
            <x-ui.card>
                <h2 class="text-sm font-semibold">Measured</h2>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Every business redirect logs referrer, client and timestamp asynchronously for insights.</p>
            </x-ui.card>
        </section>
    </main>

    <footer class="mt-14 border-t border-neutral-200 py-6 dark:border-neutral-800">
        <p class="text-center text-xs text-neutral-500 dark:text-neutral-500">href.re — official business shortener by ternis.link · public links: <strong>href.nz</strong> · <a href="https://ternis.link/legal/privacy" class="underline underline-offset-2">privacy</a> · <a href="https://ternis.link/legal/terms" class="underline underline-offset-2">terms</a> · <a href="https://ternis.dev/en/legal/imprint" class="underline underline-offset-2">imprint</a></p>
    </footer>
</body>
</html>
