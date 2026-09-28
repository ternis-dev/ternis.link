<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mitglieder-Login — meinlink.at</title>
    <meta name="description" content="meinlink.at Mitglieder-Login — Schnelle und sichere Anmeldung über Ternis Auth SSO für eigene Kürzel und Klick-Statistiken.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#dc2626">
    <link rel="canonical" href="https://meinlink.at/login">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/meinlink.css'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased selection:bg-red-500 selection:text-white dark:bg-zinc-950 dark:text-zinc-100">
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 h-[450px] w-[700px] rounded-full bg-gradient-to-b from-red-500/10 via-rose-500/5 to-transparent blur-3xl"></div>
    </div>

    <div class="mx-auto flex min-h-screen max-w-xl flex-col justify-between px-4 py-8 sm:px-6">
        <header class="flex items-center justify-between">
            <a href="/" class="text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100" aria-label="meinlink.at Startseite">
                meinlink<span class="text-red-600">.at</span>
            </a>

            <nav aria-label="Navigation">
                <a href="/" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                    ← Zurück zum Kürzer
                </a>
            </nav>
        </header>

        <main class="my-auto py-10">
            <div class="text-center">
                <h1 class="font-display text-3xl font-extrabold tracking-tight text-zinc-900 sm:text-4xl dark:text-zinc-50">
                    Mitglieder-Login
                </h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Melde dich an, um eigene Wunschkürzel zu vergeben und Klick-Statistiken einzusehen.
                </p>
            </div>

            <div class="mt-8 overflow-hidden rounded-2xl border border-zinc-200 bg-white/90 p-6 shadow-xl backdrop-blur-xl sm:p-8 dark:border-zinc-800 dark:bg-zinc-900/90">
                @if (session('error'))
                    <div class="mb-5 rounded-xl border border-red-500/20 bg-red-500/10 p-3.5 text-xs font-medium text-red-700 dark:text-red-300" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                <p class="text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                    Die Anmeldung läuft sicher und zentral über <strong>Ternis Auth SSO</strong>. Du benötigst kein separates Passwort für meinlink.at.
                </p>

                <div class="mt-6">
                    <a
                        href="{{ \App\Support\DomainUrls::dashboard('/login') }}"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-3 font-semibold text-white shadow-md transition hover:bg-red-500 active:scale-[0.98]"
                    >
                        <span>Mit Ternis Auth anmelden</span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                </div>

                <div class="mt-6 border-t border-zinc-100 pt-5 dark:border-zinc-800">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Vorteile für angemeldete Nutzer</h3>
                    <ul class="mt-3 space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Eigene Wunsch-Kürzel (Custom Slugs) wählen</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Detaillierte Klickzahlen &amp; Statistiken in Echtzeit</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Höhere Tageslimits für deine Links</span>
                        </li>
                    </ul>
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-zinc-500 dark:text-zinc-400">
                Noch kein Konto? <a href="/" class="font-medium text-zinc-700 underline underline-offset-4 transition hover:text-red-600 dark:text-zinc-300 dark:hover:text-red-400">Gäste können Links sofort ohne Konto kürzen</a>
            </p>
        </main>

        <footer class="border-t border-zinc-200/80 pt-6 text-center text-xs text-zinc-500 dark:border-zinc-800/80 dark:text-zinc-400">
            <div class="flex flex-wrap items-center justify-center gap-4">
                <span>meinlink.at</span>
                <span>·</span>
                <a href="https://ternis.link/pages/legal/privacy" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">Datenschutz</a>
                <span>·</span>
                <a href="https://ternis.link/pages/legal/terms" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">AGB</a>
                <span>·</span>
                <a href="https://ternis.dev/de/legal/imprint" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">Impressum</a>
            </div>
        </footer>
    </div>
</body>
</html>
