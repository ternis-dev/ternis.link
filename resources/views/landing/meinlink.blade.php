<!DOCTYPE html>
<html lang="de" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>meinlink.at — Schnelle, sichere Kurzlinks</title>
    <meta name="description" content="meinlink.at — Der moderne Kurzlink-Dienst aus Österreich. Links sofort kürzen ohne Anmeldung, mit QR-Code und garantiert datenschutzfreundlich.">
    <meta name="theme-color" content="#dc2626">
    <link rel="canonical" href="https://meinlink.at/">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="meinlink.at">
    <meta property="og:title" content="meinlink.at — Schnelle, sichere Kurzlinks">
    <meta property="og:description" content="Lange Links blitzschnell kürzen. Kostenlos, ohne Registrierung und datenschutzkonform.">
    <meta property="og:url" content="https://meinlink.at/">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/meinlink.css'])
    @livewireStyles

    @if (config('services.turnstile.key'))
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
    @endif
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased selection:bg-red-500 selection:text-white dark:bg-zinc-950 dark:text-zinc-100">
    <a href="#shorten" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-red-600 focus:px-4 focus:py-2 focus:text-white">
        Direkt zum Formular springen
    </a>

    {{-- Subtle background decoration --}}
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 h-[500px] w-[800px] rounded-full bg-gradient-to-b from-red-500/10 via-rose-500/5 to-transparent blur-3xl"></div>
        <div class="absolute top-[600px] -left-40 h-[400px] w-[400px] rounded-full bg-zinc-200/50 blur-3xl dark:bg-zinc-800/20"></div>
    </div>

    {{-- Navigation Header --}}
    <header class="sticky top-0 z-40 w-full border-b border-zinc-200/80 bg-white/75 backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/75">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
            <a href="/" class="flex items-center gap-2 text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100" aria-label="meinlink.at Startseite">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-600 font-display text-base font-black text-white shadow-sm">
                    m
                </span>
                <span>meinlink<span class="text-red-600">.at</span></span>
            </a>

            <nav class="flex items-center gap-3 sm:gap-6" aria-label="Hauptnavigation">
                <a href="#features" class="hidden text-sm font-medium text-zinc-600 transition hover:text-zinc-900 sm:inline-block dark:text-zinc-400 dark:hover:text-zinc-100">
                    Vorteile
                </a>
                <a href="https://ternis.link/pages/legal/privacy" class="hidden text-sm font-medium text-zinc-600 transition hover:text-zinc-900 sm:inline-block dark:text-zinc-400 dark:hover:text-zinc-100">
                    Datenschutz
                </a>

                @auth
                    <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-zinc-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
                        <span>Dashboard</span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                @else
                    <a href="{{ url('/login') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-3.5 py-2 text-sm font-semibold text-zinc-800 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-700 dark:hover:bg-zinc-800">
                        <span>Mitglieder-Login</span>
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        {{-- Hero Section --}}
        <section class="relative px-4 pt-12 pb-16 sm:px-6 sm:pt-20 sm:pb-24">
            <div class="mx-auto max-w-3xl text-center">
                {{-- Austrian Flag Pill --}}
                <div class="inline-flex items-center gap-2 rounded-full border border-red-500/20 bg-red-500/10 px-3.5 py-1 text-xs font-semibold text-red-700 dark:border-red-500/30 dark:bg-red-950/40 dark:text-red-300">
                    <span class="inline-block h-2.5 w-3.5 overflow-hidden rounded-xs border border-red-600/30 shadow-xs">
                        <span class="block h-1/3 bg-red-600"></span>
                        <span class="block h-1/3 bg-white"></span>
                        <span class="block h-1/3 bg-red-600"></span>
                    </span>
                    <span>Moderne &amp; sichere Kurzlinks aus Österreich</span>
                </div>

                <h1 class="mt-6 font-display text-4xl font-extrabold tracking-tight text-zinc-900 sm:text-5xl sm:leading-tight lg:text-6xl dark:text-zinc-50" id="hero-title">
                    Lange URLs einfach <br class="hidden sm:inline" />
                    <span class="bg-gradient-to-r from-red-600 via-rose-600 to-red-500 bg-clip-text text-transparent">kurz gemacht.</span>
                </h1>

                <p class="mx-auto mt-4 max-w-xl text-base text-zinc-600 sm:text-lg dark:text-zinc-400">
                    Füge deine lange Webadresse ein und erhalte sofort einen kompakten <strong>meinlink.at</strong>-Kurzlink — ohne Registrierung, gebührenfrei und datenschutzfreundlich.
                </p>

                {{-- Dedicated Link Creation Form --}}
                <div class="mt-10 sm:mt-12" id="shorten" aria-label="Link kürzen">
                    <livewire:meinlink.shorten-form />
                </div>
            </div>
        </section>

        {{-- Feature Highlights --}}
        <section id="features" class="border-t border-zinc-200/80 bg-zinc-100/50 py-16 sm:py-24 dark:border-zinc-800/80 dark:bg-zinc-900/40">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <div class="text-center">
                    <h2 class="font-display text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                        Warum meinlink.at?
                    </h2>
                    <p class="mx-auto mt-2 max-w-lg text-sm text-zinc-600 dark:text-zinc-400">
                        Gebaut für Geschwindigkeit, Sicherheit und Benutzerfreundlichkeit.
                    </p>
                </div>

                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Card 1 --}}
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 font-semibold text-zinc-900 dark:text-zinc-100">Sofort einsatzbereit</h3>
                        <p class="mt-1.5 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Kein Passwort, kein Konto nötig. Link einfügen und in Sekunden teilen.
                        </p>
                    </div>

                    {{-- Card 2 --}}
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 font-semibold text-zinc-900 dark:text-zinc-100">Datenschutz nach DSGVO</h3>
                        <p class="mt-1.5 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Keine Weitergabe von Daten, keine Tracking-Cookies und anonymisierte IP-Hashes.
                        </p>
                    </div>

                    {{-- Card 3 --}}
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-sky-100 text-sky-600 dark:bg-sky-950 dark:text-sky-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 font-semibold text-zinc-900 dark:text-zinc-100">QR-Codes inklusive</h3>
                        <p class="mt-1.5 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Zu jedem Kurzlink gibt es automatisch einen hochauflösenden QR-Code zum Herunterladen.
                        </p>
                    </div>

                    {{-- Card 4 --}}
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-950 dark:text-purple-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 font-semibold text-zinc-900 dark:text-zinc-100">Auswertungen &amp; Slugs</h3>
                        <p class="mt-1.5 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Angemeldete Mitglieder können eigene Wunschkürzel wählen und Klick-Statistiken einsehen.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Call To Action for Members --}}
        <section class="px-4 py-16 sm:px-6">
            <div class="mx-auto max-w-4xl overflow-hidden rounded-3xl border border-zinc-200 bg-gradient-to-br from-zinc-900 via-zinc-900 to-zinc-950 p-8 text-center text-white shadow-xl sm:p-12 dark:border-zinc-800">
                <h2 class="font-display text-2xl font-bold tracking-tight sm:text-3xl">
                    Möchtest du mehr Kontrolle über deine Links?
                </h2>
                <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-zinc-400 sm:text-base">
                    Melde dich kostenlos an und erhalte eigene Kurzlink-Namen, erweiterte Klick-Statistiken, API-Zugriff und ein übersichtliches Dashboard.
                </p>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                    <a href="{{ url('/login') }}" class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-6 py-3 font-semibold text-white shadow-md transition hover:bg-red-500 active:scale-[0.98]">
                        <span>Jetzt anmelden</span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                    <a href="/new" class="inline-flex items-center rounded-xl border border-zinc-700 bg-zinc-800/80 px-6 py-3 font-semibold text-zinc-200 transition hover:bg-zinc-800 hover:text-white">
                        Weiter als Gast
                    </a>
                </div>
            </div>
        </section>
    </main>

    {{-- Modern Footer --}}
    <footer class="border-t border-zinc-200 bg-white py-12 text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400">
        <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-6 px-4 text-center sm:flex-row sm:px-6 sm:text-left">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-red-600 text-xs font-bold text-white">m</span>
                <span class="font-semibold text-zinc-800 dark:text-zinc-200">meinlink.at</span>
                <span class="text-zinc-400 dark:text-zinc-600">·</span>
                <span>Ein Dienst der Ternis-Plattform</span>
            </div>

            <nav class="flex flex-wrap items-center justify-center gap-6 text-xs" aria-label="Rechtliche Links">
                <a href="https://ternis.link/pages/legal/privacy" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">Datenschutz</a>
                <a href="https://ternis.link/pages/legal/terms" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">AGB</a>
                <a href="https://ternis.dev/de/legal/imprint" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">Impressum</a>
                <a href="https://ternis.link/pages/stats" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">Netzwerk-Statistik</a>
            </nav>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
