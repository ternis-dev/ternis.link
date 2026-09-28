<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Neuen Link erstellen — meinlink.at</title>
    <meta name="description" content="Erstelle einen neuen Kurzlink auf meinlink.at. Schnell, kostenlos und ohne Registrierung.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#dc2626">
    <link rel="canonical" href="https://meinlink.at/new">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/meinlink.css'])
    @livewireStyles

    @if (config('services.turnstile.key'))
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
    @endif
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased selection:bg-red-500 selection:text-white dark:bg-zinc-950 dark:text-zinc-100">
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 h-[450px] w-[700px] rounded-full bg-gradient-to-b from-red-500/10 via-rose-500/5 to-transparent blur-3xl"></div>
    </div>

    <div class="mx-auto flex min-h-screen max-w-2xl flex-col justify-between px-4 py-8 sm:px-6">
        <header class="flex items-center justify-between">
            <a href="/" class="text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100" aria-label="meinlink.at Startseite">
                meinlink<span class="text-red-600">.at</span>
            </a>

            <nav aria-label="Navigation">
                @auth
                    <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                        Dashboard →
                    </a>
                @else
                    <a href="{{ url('/login') }}" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                        Mitglieder-Login →
                    </a>
                @endauth
            </nav>
        </header>

        <main class="my-auto py-10">
            <div class="text-center">
                <h1 class="font-display text-3xl font-extrabold tracking-tight text-zinc-900 sm:text-4xl dark:text-zinc-50">
                    Neuen Link erstellen
                </h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Gib deine Ziel-URL ein, um einen schnellen Kurzlink zu erzeugen.
                </p>
            </div>

            <div class="mt-8">
                <livewire:meinlink.shorten-form :compact="true" :minimal="true" />
            </div>

            <p class="mt-6 text-center text-xs text-zinc-500 dark:text-zinc-400">
                <a href="/" class="font-medium text-zinc-700 underline underline-offset-4 transition hover:text-red-600 dark:text-zinc-300 dark:hover:text-red-400">
                    ← Zurück zur Startseite
                </a>
            </p>
        </main>

        <footer class="border-t border-zinc-200/80 pt-6 text-center text-xs text-zinc-500 dark:border-zinc-800/80 dark:text-zinc-400">
            <div class="flex flex-wrap items-center justify-center gap-4">
                <span>meinlink.at</span>
                <span>·</span>
                <a href="https://ternis.link/pages/legal/privacy" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">Datenschutz</a>
                <span>·</span>
                <a href="https://ternis.link/pages/legal/terms" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">AGB</a>
            </div>
        </footer>
    </div>

    @livewireScripts
</body>
</html>
