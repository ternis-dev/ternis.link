<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Docs · ternis.link</title>
    <meta name="description" content="Developer documentation for the ternis.link network: architecture, authentication, domains and routing, links.">
    <link rel="canonical" href="https://docs.ternis.link/">
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
        <div class="mx-auto flex max-w-4xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="/" class="font-display text-xl font-bold tracking-tight">ternis<span class="text-neutral-400 dark:text-neutral-500">.link</span> <span class="text-sm font-medium text-neutral-500 dark:text-neutral-400">docs</span></a>
            <nav class="flex items-center gap-1 text-sm" aria-label="Network">
                <a href="https://href.nz" class="rounded-lg px-2 py-1 text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">href.nz</a>
                <a href="https://ternis.link/pages/stats" class="rounded-lg px-2 py-1 text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">stats</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-4xl flex-1 px-4 py-10 sm:px-6">
        <p class="text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-500">developer documentation</p>
        <h1 class="font-display mt-1 text-3xl font-bold tracking-tight">How this network fits together</h1>
        <p class="mt-2 max-w-2xl text-sm text-neutral-500 dark:text-neutral-400">Rendered from the repo <code>docs/</code> files, so code and documentation cannot drift. Every page has a Markdown twin for crawlers and agents.</p>

        <ul class="mt-8 grid gap-3 sm:grid-cols-2">
            @foreach ($pages as $page)
                <li>
                    <a href="{{ url('/'.$page['slug']) }}" class="block rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition-colors hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-600">
                        <span class="font-display text-lg font-bold tracking-tight">{{ $page['title'] }}</span>
                        <span class="mt-1 block text-xs text-neutral-500 dark:text-neutral-500">/{{ $page['slug'] }} · <span class="underline underline-offset-2">/{{ $page['slug'] }}.md</span></span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-8 rounded-2xl border border-neutral-200 bg-white p-5 text-sm dark:border-neutral-800 dark:bg-neutral-900">
            <p class="font-semibold">Machine-readable API</p>
            <p class="mt-1 text-neutral-500 dark:text-neutral-400">OpenAPI 3.1 for the public v1 API, served raw: <a href="{{ url('/api-v1-openapi.yaml') }}" class="underline underline-offset-2 hover:text-neutral-900 dark:hover:text-white">/api-v1-openapi.yaml</a>.</p>
        </div>
    </main>

    <footer class="mx-auto w-full max-w-4xl px-4 pb-8 sm:px-6">
        <p class="text-center text-xs text-neutral-500 dark:text-neutral-500">ternis.link docs · <a href="https://ternis.link/pages/legal/privacy" class="underline underline-offset-2 hover:text-neutral-700 dark:hover:text-neutral-300">privacy</a> · <a href="https://ternis.link/pages/legal/terms" class="underline underline-offset-2 hover:text-neutral-700 dark:hover:text-neutral-300">terms</a></p>
    </footer>
</body>
</html>
