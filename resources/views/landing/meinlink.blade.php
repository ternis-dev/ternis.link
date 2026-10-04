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

    {{-- Font preloading: load before CSS parsing to eliminate content shift --}}
    <link rel="preload" href="/fonts/inter-var.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/space-grotesk-var.woff2" as="font" type="font/woff2" crossorigin>

    <noscript>
        <style>
            .ml-loader { display: none !important; }
            .ml-enter { animation: none !important; opacity: 1 !important; transform: none !important; }
        </style>
    </noscript>

    @vite(['resources/css/meinlink.css'])
    @livewireStyles

    @if (config('services.turnstile.key'))
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
    @endif
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased selection:bg-red-500 selection:text-white dark:bg-zinc-950 dark:text-zinc-100">

    {{-- =====================================================
         Preloader — Marken-Ladebalken für meinlink.at.
         Verhindert Layout-Verschiebungen (FOUT / Content Shift),
         während Inter & Space Grotesk geladen werden.
         ===================================================== --}}
    <div class="ml-loader" id="ml-loader" aria-hidden="true" role="presentation">
        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -top-40 left-1/2 -translate-x-1/2 h-[500px] w-[800px] rounded-full bg-gradient-to-b from-red-500/10 via-rose-500/5 to-transparent blur-3xl"></div>
        </div>
        <div class="ml-loader-inner">
            <div class="ml-load-brand">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-red-500 via-rose-600 to-red-600 text-white shadow-md shadow-red-500/25">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span>meinlink<span class="text-red-600">.at</span></span>
            </div>
            <p class="ml-load-tagline">Schneller &amp; sicherer URL-Kürzer</p>
            <div class="ml-load-row">
                <div class="ml-load-track">
                    <div class="ml-loader-fill" id="ml-loader-fill"></div>
                </div>
                <span class="ml-load-pct" id="ml-load-pct">0%</span>
            </div>
            <p class="ml-load-status" id="ml-load-status">Kurzlink-Dienst wird initialisiert…</p>
        </div>
    </div>

    <a href="#shorten" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-red-600 focus:px-4 focus:py-2 focus:text-white">
        Direkt zum Formular springen
    </a>

    {{-- Subtle background decoration --}}
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 h-[500px] w-[800px] rounded-full bg-gradient-to-b from-red-500/10 via-rose-500/5 to-transparent blur-3xl"></div>
        <div class="absolute top-[600px] -left-40 h-[400px] w-[400px] rounded-full bg-zinc-200/50 blur-3xl dark:bg-zinc-800/20"></div>
    </div>

    <div class="ml-wrap ml-enter">
    {{-- Navigation Header --}}
    <header class="sticky top-0 z-40 w-full border-b border-zinc-200/80 bg-white/75 backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/75">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
            <a href="/" class="group flex items-center gap-2.5 text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100" aria-label="meinlink.at Startseite">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-red-500 via-rose-600 to-red-600 text-white shadow-md shadow-red-500/25 transition duration-200 group-hover:scale-105 group-hover:shadow-red-500/40">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span>meinlink<span class="text-red-600">.at</span></span>
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
                    <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="group inline-flex items-center gap-1.5 rounded-xl bg-zinc-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
                        <span>Dashboard</span>
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                @else
                    <a href="{{ url('/login') }}" class="group inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-3.5 py-2 text-sm font-semibold text-zinc-800 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-700 dark:hover:bg-zinc-800">
                        <span>Mitglieder-Login</span>
                        <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
                {{-- Germany Flag & Verification Pill --}}
                <div class="inline-flex items-center gap-2 rounded-full border border-zinc-200/90 bg-white/95 px-3.5 py-1 text-xs font-semibold text-zinc-700 shadow-xs backdrop-blur-sm transition hover:border-zinc-300 dark:border-zinc-800/90 dark:bg-zinc-900/95 dark:text-zinc-300 dark:hover:border-zinc-700">
                    <svg class="h-3 w-4 shrink-0 overflow-hidden rounded-[2px] shadow-xs ring-1 ring-black/10" viewBox="0 0 20 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="20" height="5" fill="#18181B"/>
                        <rect y="5" width="20" height="5" fill="#DC2626"/>
                        <rect y="10" width="20" height="5" fill="#F59E0B"/>
                    </svg>
                    <span>Moderne &amp; sichere Kurzlinks aus Deutschland</span>
                    <svg class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 16 16" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm3.354-9.354a.5.5 0 0 0-.708 0L7 9.293 5.354 7.646a.5.5 0 1 0-.708.708l2 2a.5.5 0 0 0 .708 0l4-4a.5.5 0 0 0 0-.708z"/>
                    </svg>
                </div>

                <h1 class="mt-6 font-display text-4xl font-extrabold tracking-tight text-zinc-900 sm:text-5xl sm:leading-tight lg:text-6xl dark:text-zinc-50" id="hero-title">
                    Lange URLs einfach <br class="hidden sm:inline" />
                    <span class="bg-[length:200%_auto] animate-shimmer bg-gradient-to-r from-red-600 via-rose-600 to-red-500 bg-clip-text text-transparent">kurz gemacht.</span>
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
                    <div class="group animate-fade-in-up [animation-delay:100ms] rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
                        <div class="flex items-center gap-3">
                            <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-red-500/10 to-rose-500/20 text-red-600 ring-1 ring-red-500/20 shadow-xs transition duration-200 group-hover:scale-105 group-hover:shadow-md dark:from-red-950/60 dark:to-rose-900/40 dark:text-red-400 dark:ring-red-500/30">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M13 2.5L3.5 13.5H12L11 21.5L20.5 10.5H12L13 2.5Z" fill="currentColor" fill-opacity="0.18" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
                                    <path d="M17 3.5L19 5.5M19.5 2.5L20.5 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">Sofort einsatzbereit</h3>
                        </div>
                        <p class="mt-3 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Kein Passwort, kein Konto nötig. Link einfügen und in Sekunden teilen.
                        </p>
                    </div>

                    {{-- Card 2 --}}
                    <div class="group animate-fade-in-up [animation-delay:200ms] rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
                        <div class="flex items-center gap-3">
                            <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500/10 to-teal-500/20 text-emerald-600 ring-1 ring-emerald-500/20 shadow-xs transition duration-200 group-hover:scale-105 group-hover:shadow-md dark:from-emerald-950/60 dark:to-teal-900/40 dark:text-emerald-400 dark:ring-emerald-500/30">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 3.25L4.5 6.75V11.5C4.5 16.5 7.7 21.1 12 22.25C16.3 21.1 19.5 16.5 19.5 11.5V6.75L12 3.25Z" fill="currentColor" fill-opacity="0.18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9 12.5L11 14.5L15 9.5" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">Datenschutz nach DSGVO</h3>
                        </div>
                        <p class="mt-3 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Keine Weitergabe von Daten, keine Tracking-Cookies und anonymisierte IP-Hashes.
                        </p>
                    </div>

                    {{-- Card 3 --}}
                    <div class="group animate-fade-in-up [animation-delay:300ms] rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
                        <div class="flex items-center gap-3">
                            <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500/10 to-blue-500/20 text-sky-600 ring-1 ring-sky-500/20 shadow-xs transition duration-200 group-hover:scale-105 group-hover:shadow-md dark:from-sky-950/60 dark:to-blue-900/40 dark:text-sky-400 dark:ring-sky-500/30">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/>
                                    <rect x="5.25" y="5.25" width="2.5" height="2.5" rx="0.5" fill="currentColor"/>
                                    <rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/>
                                    <rect x="16.25" y="5.25" width="2.5" height="2.5" rx="0.5" fill="currentColor"/>
                                    <rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="2"/>
                                    <rect x="5.25" y="16.25" width="2.5" height="2.5" rx="0.5" fill="currentColor"/>
                                    <rect x="14" y="14" width="2.5" height="2.5" rx="0.5" fill="currentColor"/>
                                    <rect x="18.5" y="14" width="2.5" height="2.5" rx="0.5" fill="currentColor"/>
                                    <rect x="14" y="18.5" width="2.5" height="2.5" rx="0.5" fill="currentColor"/>
                                    <rect x="18.5" y="18.5" width="2.5" height="2.5" rx="0.5" fill="currentColor"/>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">QR-Codes inklusive</h3>
                        </div>
                        <p class="mt-3 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Zu jedem Kurzlink gibt es automatisch einen hochauflösenden QR-Code zum Herunterladen.
                        </p>
                    </div>

                    {{-- Card 4 --}}
                    <div class="group animate-fade-in-up [animation-delay:400ms] rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
                        <div class="flex items-center gap-3">
                            <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500/10 to-indigo-500/20 text-purple-600 ring-1 ring-purple-500/20 shadow-xs transition duration-200 group-hover:scale-105 group-hover:shadow-md dark:from-purple-950/60 dark:to-indigo-900/40 dark:text-purple-400 dark:ring-purple-500/30">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="3" y="13" width="4" height="8" rx="1.5" fill="currentColor" fill-opacity="0.2" stroke="currentColor" stroke-width="1.8"/>
                                    <rect x="10" y="8" width="4" height="13" rx="1.5" fill="currentColor" fill-opacity="0.3" stroke="currentColor" stroke-width="1.8"/>
                                    <rect x="17" y="4" width="4" height="17" rx="1.5" fill="currentColor" fill-opacity="0.4" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="M4 11L11 6L16 9L21 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="21" cy="3" r="1.5" fill="currentColor"/>
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

                <div class="mt-12 space-y-4" x-data="{ active: null }">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
                        <button @click="active = active === 1 ? null : 1" type="button" class="group flex w-full items-center justify-between text-left">
                            <h3 class="font-semibold text-zinc-900 transition group-hover:text-red-600 dark:text-zinc-100 dark:group-hover:text-red-400">
                                Wie kann ich einen Link auf meinlink.at kostenlos kürzen?
                            </h3>
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 transition-all duration-200 group-hover:bg-red-50 group-hover:text-red-600 dark:bg-zinc-800 dark:text-zinc-400 dark:group-hover:bg-red-950/60 dark:group-hover:text-red-400">
                                <svg :class="active === 1 ? 'rotate-180 text-red-600 dark:text-red-400' : ''" class="h-4 w-4 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                        <div x-show="active === 1" x-transition x-cloak>
                            <p class="mt-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                                Füge einfach deine lange Ziel-URL in das Eingabefeld ein, wähle bei Bedarf deine bevorzugte Domain (<code>meinlink.at</code> oder <code>href.nz</code>), die gewünschte Zeichenlänge (5 bis 9 Zeichen) sowie ein optionales Ablaufdatum und klicke auf „Kürzen“. Der Kurzlink wird sofort erstellt.
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
                        <button @click="active = active === 2 ? null : 2" type="button" class="group flex w-full items-center justify-between text-left">
                            <h3 class="font-semibold text-zinc-900 transition group-hover:text-red-600 dark:text-zinc-100 dark:group-hover:text-red-400">
                                Ist meinlink.at kostenlos und ohne Registrierung nutzbar?
                            </h3>
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 transition-all duration-200 group-hover:bg-red-50 group-hover:text-red-600 dark:bg-zinc-800 dark:text-zinc-400 dark:group-hover:bg-red-950/60 dark:group-hover:text-red-400">
                                <svg :class="active === 2 ? 'rotate-180 text-red-600 dark:text-red-400' : ''" class="h-4 w-4 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                        <div x-show="active === 2" x-transition x-cloak>
                            <p class="mt-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                                Ja, als Gast kannst du bis zu 50 Links pro Tag völlig kostenlos und ohne Angabe von persönlichen Daten, E-Mail-Adresse oder Passwort kürzen.
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
                        <button @click="active = active === 3 ? null : 3" type="button" class="group flex w-full items-center justify-between text-left">
                            <h3 class="font-semibold text-zinc-900 transition group-hover:text-red-600 dark:text-zinc-100 dark:group-hover:text-red-400">
                                Wird automatisch ein QR-Code für den Link generiert?
                            </h3>
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 transition-all duration-200 group-hover:bg-red-50 group-hover:text-red-600 dark:bg-zinc-800 dark:text-zinc-400 dark:group-hover:bg-red-950/60 dark:group-hover:text-red-400">
                                <svg :class="active === 3 ? 'rotate-180 text-red-600 dark:text-red-400' : ''" class="h-4 w-4 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                        <div x-show="active === 3" x-transition x-cloak>
                            <p class="mt-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                                Ja! Zu jedem erstellten Kurzlink erhältst du direkt einen hochauflösenden QR-Code zum Scannen oder Herunterladen. Perfekt für Flyer, Präsentationen und Visitenkarten.
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
                        <button @click="active = active === 4 ? null : 4" type="button" class="group flex w-full items-center justify-between text-left">
                            <h3 class="font-semibold text-zinc-900 transition group-hover:text-red-600 dark:text-zinc-100 dark:group-hover:text-red-400">
                                Wie schützt meinlink.at meine Privatsphäre?
                            </h3>
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 transition-all duration-200 group-hover:bg-red-50 group-hover:text-red-600 dark:bg-zinc-800 dark:text-zinc-400 dark:group-hover:bg-red-950/60 dark:group-hover:text-red-400">
                                <svg :class="active === 4 ? 'rotate-180 text-red-600 dark:text-red-400' : ''" class="h-4 w-4 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                        <div x-show="active === 4" x-transition x-cloak>
                            <p class="mt-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                                meinlink.at arbeitet nach strengen DSGVO-Richtlinien. Es werden keine Werbetracker oder Tracking-Cookies gesetzt, und IP-Adressen werden zur Missbrauchsprävention nur als Einweg-Hash verarbeitet.
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
                        <button @click="active = active === 5 ? null : 5" type="button" class="group flex w-full items-center justify-between text-left">
                            <h3 class="font-semibold text-zinc-900 transition group-hover:text-red-600 dark:text-zinc-100 dark:group-hover:text-red-400">
                                Welche Vorteile bietet ein kostenloses Mitgliedskonto?
                            </h3>
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 transition-all duration-200 group-hover:bg-red-50 group-hover:text-red-600 dark:bg-zinc-800 dark:text-zinc-400 dark:group-hover:bg-red-950/60 dark:group-hover:text-red-400">
                                <svg :class="active === 5 ? 'rotate-180 text-red-600 dark:text-red-400' : ''" class="h-4 w-4 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                        <div x-show="active === 5" x-transition x-cloak>
                            <p class="mt-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                                Registrierte Mitglieder können eigene Wunschkürzel vergeben, detaillierte Klick-Statistiken in Echtzeit abrufen und höhere Tageslimits nutzen. Die Anmeldung erfolgt schnell und sicher über Ternis Auth SSO.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Call To Action for Members --}}
        <section class="px-4 py-16 sm:px-6">
            <div class="relative mx-auto max-w-4xl overflow-hidden rounded-3xl border border-zinc-200 bg-gradient-to-br from-zinc-900 via-zinc-900 to-zinc-950 p-8 text-center text-white shadow-xl sm:p-12 dark:border-zinc-800">
                <div class="relative z-10">
                    <h2 class="font-display text-2xl font-bold tracking-tight sm:text-3xl">
                        Möchtest du mehr Kontrolle über deine Links?
                    </h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-zinc-400 sm:text-base">
                        Melde dich kostenlos an und erhalte eigene Kurzlink-Namen, erweiterte Klick-Statistiken, API-Zugriff und ein übersichtliches Dashboard.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                        <a href="{{ url('/login') }}" class="group inline-flex items-center gap-2 rounded-xl bg-red-600 px-6 py-3 font-semibold text-white shadow-md transition hover:bg-red-500 active:scale-[0.98]">
                            <span>Jetzt anmelden</span>
                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                        <a href="/new" class="inline-flex items-center rounded-xl border border-zinc-700 bg-zinc-800/80 px-6 py-3 font-semibold text-zinc-200 transition hover:bg-zinc-800 hover:text-white">
                            Weiter als Gast
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    {{-- Modern Footer --}}
    <footer class="border-t border-zinc-200 bg-white py-12 text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400">
        <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-6 px-4 text-center sm:flex-row sm:px-6 sm:text-left">
            <div class="flex items-center gap-2">
                <span class="font-semibold text-zinc-800 dark:text-zinc-200">&copy; {{ date('Y') }} Ternis</span>
                <span class="text-zinc-400 dark:text-zinc-600">·</span>
                <span class="inline-flex items-center gap-1.5 font-semibold text-zinc-800 dark:text-zinc-200">
                    <svg class="h-3.5 w-3.5 text-red-600" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    meinlink.at
                </span>
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
    </div>

    @livewireScripts

    <script>
    /* ── Preloader: füllt Ladebalken gleichmäßig und wartet auf
     * Inter & Space Grotesk Schriftarten (inkl. Sicherheits-Timeout).
     * Beseitigt FOUT / Content Shift vollständig. */
    (function () {
        var loader = document.getElementById('ml-loader');
        var fill   = document.getElementById('ml-loader-fill');
        var pct    = document.getElementById('ml-load-pct');
        var status = document.getElementById('ml-load-status');
        if (!loader || !fill) return;

        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var mobile  = window.matchMedia('(max-width: 640px)').matches;
        var start   = null;
        var FILL_MS = reduced ? 0 : (mobile ? 320 : 500);
        var HOLD_MS = reduced ? 0 : (mobile ? 50 : 80);

        function setFill(eased) {
            var percent = Math.min(100, Math.round(eased * 100));
            fill.style.width = percent + '%';
            if (pct) pct.textContent = percent + '%';
        }

        setFill(0);

        function dismiss() {
            loader.classList.add('ml-loader-done');
            var removed = false;
            function cleanUp() {
                if (removed) return;
                removed = true;
                loader.hidden = true;
            }
            loader.addEventListener('transitionend', cleanUp, { once: true });
            setTimeout(cleanUp, 500);
        }

        function finish() {
            if (status) status.textContent = 'Bereit';
            setTimeout(dismiss, HOLD_MS);
        }

        function waitForFonts(callback) {
            if (document.fonts && document.fonts.ready) {
                var timeout = new Promise(function (resolve) { setTimeout(resolve, 800); });
                Promise.race([document.fonts.ready, timeout]).then(callback).catch(callback);
            } else {
                callback();
            }
        }

        function animateFill(ts) {
            if (!start) start = ts;
            var elapsed  = ts - start;
            var progress = Math.min(1, elapsed / Math.max(FILL_MS, 1));
            var eased    = 1 - Math.pow(1 - progress, 3);
            setFill(eased);

            if (progress < 1) {
                requestAnimationFrame(animateFill);
            } else {
                waitForFonts(finish);
            }
        }

        function run() {
            if (reduced) {
                dismiss();
            } else {
                requestAnimationFrame(animateFill);
            }
        }

        window.addEventListener('pageshow', function (e) {
            if (e.persisted && loader) {
                loader.classList.add('ml-loader-done');
                loader.hidden = true;
            }
        });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', run);
        } else {
            run();
        }
    })();
    </script>
</body>
</html>
