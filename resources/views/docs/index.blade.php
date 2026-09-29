<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Docs · ternis.link</title>
    <meta name="description" content="Guides for the ternis.link network: shorten links, use the API, custom domains, accounts, and how it all works.">
    <link rel="canonical" href="https://docs.ternis.link/">
    <meta property="og:title" content="Docs · ternis.link">
    <meta property="og:description" content="Guides for the ternis.link network: shorten links, use the API, custom domains, accounts, and how it all works.">
    <meta property="og:url" content="https://docs.ternis.link/">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="ternis.link docs">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Docs · ternis.link">
    <meta name="twitter:description" content="Guides for the ternis.link network: shorten links, use the API, custom domains, accounts, and how it all works.">
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
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col bg-neutral-50 dark:bg-neutral-950">
    <header class="border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="/" class="font-display text-xl font-bold tracking-tight">ternis<span class="text-neutral-400 dark:text-neutral-500">.link</span> <span class="text-sm font-medium text-neutral-500 dark:text-neutral-400">docs</span></a>
            <nav class="flex items-center gap-1 text-sm" aria-label="Network">
                <a href="https://href.nz" class="rounded-lg px-2 py-1 text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">href.nz</a>
                <a href="https://ternis.link/pages/stats" class="rounded-lg px-2 py-1 text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">stats</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6">
        <p class="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-500">guides</p>
        <h1 class="font-display mt-1 text-3xl font-bold tracking-tight">Shorten links, use the API, understand your stats</h1>

        <ul class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($pages as $page)
                <li>
                    <a href="{{ url('/'.$page['slug']) }}" class="block h-full rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm transition-colors hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-600">
                        <span class="font-display text-lg font-bold tracking-tight">{{ $page['title'] }}</span>
                        <span class="mt-1 block text-sm text-neutral-500 dark:text-neutral-400">{{ $page['description'] }}</span>
                    </a>
                </li>
            @endforeach
            <li>
                <a href="{{ url('/api-v1-openapi.yaml') }}" class="block h-full rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm transition-colors hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-600">
                    <span class="font-display text-lg font-bold tracking-tight">OpenAPI contract</span>
                    <span class="mt-1 block text-sm text-neutral-500 dark:text-neutral-400">The v1 API as YAML, for code generators and agents.</span>
                </a>
            </li>
        </ul>
    </main>

    <footer class="mx-auto w-full max-w-6xl px-4 pb-6 sm:px-6">
        <p class="text-center text-xs text-neutral-500 dark:text-neutral-500">ternis.link docs · <a href="https://ternis.link/pages/legal/privacy" class="underline underline-offset-2 hover:text-neutral-700 dark:hover:text-neutral-300">privacy</a> · <a href="https://ternis.link/pages/legal/terms" class="underline underline-offset-2 hover:text-neutral-700 dark:hover:text-neutral-300">terms</a></p>
    </footer>
</body>
</html>
