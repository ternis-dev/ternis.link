<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Docs · ternis.link</title>
    <meta name="description" content="{{ $title }} — ternis.link guides.">
    <link rel="canonical" href="https://docs.ternis.link/{{ $slug }}">
    <meta property="og:title" content="{{ $title }} · Docs · ternis.link">
    <meta property="og:description" content="{{ $title }} — ternis.link guides.">
    <meta property="og:url" content="https://docs.ternis.link/{{ $slug }}">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="ternis.link docs">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $title }} · Docs · ternis.link">
    <meta name="twitter:description" content="{{ $title }} — ternis.link guides.">
    <link rel="alternate" type="text/markdown" title="{{ $title }} (Markdown)" href="{{ url('/'.$slug.'.md') }}">
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
        <div class="grid gap-10 lg:grid-cols-[220px_minmax(0,1fr)]">
            <nav class="hidden lg:block" aria-label="Docs pages">
                <p class="mb-2 px-3 text-xs font-medium tracking-wide text-neutral-500 uppercase dark:text-neutral-500"><a href="/" class="hover:text-neutral-900 dark:hover:text-white">← All guides</a></p>
                <ul class="sticky top-6 space-y-1 text-sm">
                    @foreach ($pages as $page)
                        <li>
                            <a href="{{ url('/'.$page['slug']) }}" @class([
                                'block rounded-lg px-3 py-1.5',
                                'bg-neutral-200 font-semibold dark:bg-neutral-800' => $page['slug'] === $slug,
                                'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white' => $page['slug'] !== $slug,
                            ])>{{ $page['title'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <article class="legal-prose min-w-0 max-w-3xl">
                {!! $html !!}
            </article>
        </div>
    </main>

    <footer class="mx-auto w-full max-w-6xl px-4 pb-6 sm:px-6">
        <p class="text-center text-xs text-neutral-500 dark:text-neutral-500">ternis.link docs · <a href="https://ternis.link/pages/legal/privacy" class="underline underline-offset-2 hover:text-neutral-700 dark:hover:text-neutral-300">privacy</a> · <a href="https://ternis.link/pages/legal/terms" class="underline underline-offset-2 hover:text-neutral-700 dark:hover:text-neutral-300">terms</a></p>
    </footer>
</body>
</html>
