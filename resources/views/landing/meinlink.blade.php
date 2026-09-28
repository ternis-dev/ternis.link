<!DOCTYPE html>
<html lang="de" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>meinlink.at — Schneller &amp; sicherer URL-Kürzer aus Deutschland</title>
    <meta name="description" content="Kostenloser URL-Shortener ohne Registrierung. Lange Links sofort kürzen, QR-Codes erstellen, Ablaufdatum festlegen und datenschutzkonform teilen.">
    <meta name="keywords" content="URL Shortener, Link kürzen, Kurzlink erstellen, Link verkürzen, QR Code erstellen, Kurzlink Deutschland, meinlink, meinlink.at, URL verkürzen, Link Shortener kostenlos">
    <meta name="author" content="Ternis">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="theme-color" content="#dc2626">

    {{-- Canonical & Hreflang --}}
    <link rel="canonical" href="https://meinlink.at/">
    <link rel="alternate" hreflang="de" href="https://meinlink.at/">
    <link rel="alternate" hreflang="de-DE" href="https://meinlink.at/">
    <link rel="alternate" hreflang="de-AT" href="https://meinlink.at/">
    <link rel="alternate" hreflang="de-CH" href="https://meinlink.at/">
    <link rel="alternate" hreflang="x-default" href="https://meinlink.at/">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:locale" content="de_DE">
    <meta property="og:site_name" content="meinlink.at">
    <meta property="og:title" content="meinlink.at — Schneller &amp; sicherer URL-Kürzer aus Deutschland">
    <meta property="og:description" content="Lange Links blitzschnell kürzen. Kostenlos, ohne Registrierung, mit QR-Code und frei wählbarer Link-Länge. 100% datenschutzkonform.">
    <meta property="og:url" content="https://meinlink.at/">

    {{-- Twitter Cards --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="meinlink.at — Schneller &amp; sicherer URL-Kürzer aus Deutschland">
    <meta name="twitter:description" content="Lange Links blitzschnell kürzen. Kostenlos, ohne Registrierung, mit QR-Code und frei wählbarer Link-Länge.">

    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Structured Data (JSON-LD) --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebSite',
                '@id' => 'https://meinlink.at/#website',
                'url' => 'https://meinlink.at/',
                'name' => 'meinlink.at',
                'description' => 'Kostenloser URL-Shortener und Kurzlink-Dienst aus Deutschland.',
                'inLanguage' => 'de-DE',
            ],
            [
                '@type' => 'WebApplication',
                '@id' => 'https://meinlink.at/#webapp',
                'name' => 'meinlink.at URL Shortener',
                'applicationCategory' => 'UtilitiesApplication',
                'operatingSystem' => 'All',
                'url' => 'https://meinlink.at/',
                'description' => 'Moderner URL-Kürzer zum schnellen Erstellen kompakter Links und QR-Codes ohne Registrierung.',
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'EUR',
                ],
                'featureList' => [
                    'URL-Kürzung ohne Registrierung',
                    'Automatische QR-Code-Erstellung',
                    'Individuelle Link-Länge (5 bis 9 Zeichen)',
                    'Wählbare Domain (meinlink.at oder href.nz)',
                    'Frei einstellbares Ablaufdatum',
                    'DSGVO-konform ohne Tracking-Cookies',
                ],
            ],
            [
                '@type' => 'FAQPage',
                '@id' => 'https://meinlink.at/#faq',
                'mainEntity' => [
                    [
                        '@type' => 'Question',
                        'name' => 'Wie kann ich einen Link auf meinlink.at kostenlos kürzen?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => 'Füge einfach deine lange Ziel-URL in das Eingabefeld ein, wähle bei Bedarf deine bevorzugte Domain (meinlink.at oder href.nz), die gewünschte Zeichenlänge (5 bis 9 Zeichen) sowie ein optionales Ablaufdatum und klicke auf "Kürzen".',
                        ],
                    ],
                    [
                        '@type' => 'Question',
                        'name' => 'Ist meinlink.at kostenlos und ohne Registrierung nutzbar?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => 'Ja, als Gast kannst du bis zu 50 Links pro Tag völlig kostenlos und ohne Angabe von E-Mail-Adresse oder Passwort kürzen.',
                        ],
                    ],
                    [
                        '@type' => 'Question',
                        'name' => 'Wird automatisch ein QR-Code für den Link generiert?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => 'Ja! Zu jedem erstellten Kurzlink erhältst du direkt einen hochauflösenden QR-Code zum Scannen oder Herunterladen.',
                        ],
                    ],
                    [
                        '@type' => 'Question',
                        'name' => 'Wie schützt meinlink.at den Datenschutz und die Privatsphäre?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => 'meinlink.at arbeitet nach strengen DSGVO-Richtlinien. Es werden keine Werbetracker oder Tracking-Cookies gesetzt, und IP-Adressen werden zur Missbrauchsprävention nur als Einweg-Hash verarbeitet.',
                        ],
                    ],
                    [
                        '@type' => 'Question',
                        'name' => 'Welche Vorteile bietet ein kostenloses Mitgliedskonto?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => 'Registrierte Mitglieder können eigene Wunschkürzel vergeben, detaillierte Klick-Statistiken in Echtzeit einsehen und höhere Tageslimits nutzen.',
                        ],
                    ],
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

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
            <a href="/" class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100" aria-label="meinlink.at Startseite">
                meinlink<span class="text-red-600">.at</span>
            </a>

            <nav class="flex items-center gap-3 sm:gap-6" aria-label="Hauptnavigation">
                <a href="#features" class="hidden text-sm font-medium text-zinc-600 transition hover:text-zinc-900 sm:inline-block dark:text-zinc-400 dark:hover:text-zinc-100">
                    Vorteile
                </a>
                <a href="#faq" class="hidden text-sm font-medium text-zinc-600 transition hover:text-zinc-900 sm:inline-block dark:text-zinc-400 dark:hover:text-zinc-100">
                    FAQ
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
                {{-- Germany Flag Pill --}}
                <div class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-3.5 py-1 text-xs font-semibold text-zinc-700 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                    <span class="inline-block h-2.5 w-3.5 overflow-hidden rounded-xs border border-zinc-300 dark:border-zinc-700 shadow-xs">
                        <span class="block h-1/3 bg-black"></span>
                        <span class="block h-1/3 bg-red-600"></span>
                        <span class="block h-1/3 bg-amber-400"></span>
                    </span>
                    <span>Moderne &amp; sichere Kurzlinks aus Deutschland</span>
                </div>

                <h1 class="mt-6 font-display text-4xl font-extrabold tracking-tight text-zinc-900 sm:text-5xl sm:leading-tight lg:text-6xl dark:text-zinc-50" id="hero-title">
                    Lange URLs einfach <br class="hidden sm:inline" />
                    <span class="bg-gradient-to-r from-red-600 via-rose-600 to-red-500 bg-clip-text text-transparent">kurz gemacht.</span>
                </h1>

                <p class="mx-auto mt-4 max-w-xl text-base text-zinc-600 sm:text-lg dark:text-zinc-400">
                    Füge deine lange Webadresse ein und erhalte sofort einen kompakten Kurzlink — ohne Registrierung, gebührenfrei und datenschutzfreundlich.
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
                        <div class="flex items-center gap-3">
                            <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">Sofort einsatzbereit</h3>
                        </div>
                        <p class="mt-3 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Kein Passwort, kein Konto nötig. Link einfügen und in Sekunden teilen.
                        </p>
                    </div>

                    {{-- Card 2 --}}
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center gap-3">
                            <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">Datenschutz nach DSGVO</h3>
                        </div>
                        <p class="mt-3 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Keine Weitergabe von Daten, keine Tracking-Cookies und anonymisierte IP-Hashes.
                        </p>
                    </div>

                    {{-- Card 3 --}}
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center gap-3">
                            <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-600 dark:bg-sky-950 dark:text-sky-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">QR-Codes inklusive</h3>
                        </div>
                        <p class="mt-3 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Zu jedem Kurzlink gibt es automatisch einen hochauflösenden QR-Code zum Herunterladen.
                        </p>
                    </div>

                    {{-- Card 4 --}}
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center gap-3">
                            <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-950 dark:text-purple-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">Auswertungen &amp; Slugs</h3>
                        </div>
                        <p class="mt-3 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Angemeldete Mitglieder können eigene Wunschkürzel wählen und Klick-Statistiken einsehen.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- FAQ Section (SEO Boost) --}}
        <section id="faq" class="py-16 sm:py-24">
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <div class="text-center">
                    <span class="text-xs font-semibold uppercase tracking-wider text-red-600 dark:text-red-400">Wissenswertes</span>
                    <h2 class="mt-2 font-display text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-100">
                        Häufig gestellte Fragen (FAQ)
                    </h2>
                    <p class="mx-auto mt-2 max-w-lg text-sm text-zinc-600 dark:text-zinc-400">
                        Alles Wichtige über den kostenlosen Kurzlink-Dienst auf meinlink.at.
                    </p>
                </div>

                <div class="mt-12 space-y-4">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">
                            Wie kann ich einen Link auf meinlink.at kostenlos kürzen?
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Füge einfach deine lange Ziel-URL in das Eingabefeld ein, wähle bei Bedarf deine bevorzugte Domain (<code>meinlink.at</code> oder <code>href.nz</code>), die gewünschte Zeichenlänge (5 bis 9 Zeichen) sowie ein optionales Ablaufdatum und klicke auf „Kürzen“. Der Kurzlink wird sofort erstellt.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">
                            Ist meinlink.at kostenlos und ohne Registrierung nutzbar?
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Ja, als Gast kannst du bis zu 50 Links pro Tag völlig kostenlos und ohne Angabe von persönlichen Daten, E-Mail-Adresse oder Passwort kürzen.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">
                            Wird automatisch ein QR-Code für den Link generiert?
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Ja! Zu jedem erstellten Kurzlink erhältst du direkt einen hochauflösenden QR-Code zum Scannen oder Herunterladen. Perfekt für Flyer, Präsentationen und Visitenkarten.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">
                            Wie schützt meinlink.at meine Privatsphäre?
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                            meinlink.at arbeitet nach strengen DSGVO-Richtlinien. Es werden keine Werbetracker oder Tracking-Cookies gesetzt, und IP-Adressen werden zur Missbrauchsprävention nur als Einweg-Hash verarbeitet.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">
                            Welche Vorteile bietet ein kostenloses Mitgliedskonto?
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Registrierte Mitglieder können eigene Wunschkürzel vergeben, detaillierte Klick-Statistiken in Echtzeit abrufen und höhere Tageslimits nutzen. Die Anmeldung erfolgt schnell und sicher über Ternis Auth SSO.
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
