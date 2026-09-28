<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Link kürzen — meinlink.at</title>
    <meta name="description" content="Kurzen meinlink.at-Link erstellen. Gäste sind willkommen; Mitglieder bekommen eigene Kürzel und Klick-Statistiken.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://meinlink.at/new">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/landing-meinlink.css'])
    @livewireStyles
    @if (config('services.turnstile.key'))
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
    @endif
</head>
<body class="ml-board">
    <a class="ml-skip" href="#new-link">Zum Formular springen</a>

    <div class="ml-wrap ml-narrow">
        <header class="ml-top">
            <a href="/" class="ml-brand" aria-label="meinlink.at Startseite">meinlink.at<small>Amt für kurze Links</small></a>
            <div class="ml-top-right">
                <nav aria-label="Konto">
                    @auth
                        <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="ml-login">Dashboard öffnen →</a>
                    @else
                        <a href="{{ url('/login') }}" class="ml-login">Mitglieder-Login →</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            <section class="ml-hero" style="padding-top: 2.25rem;">
                <span class="ml-kicker">Formular LK-8 <span class="red">·</span> neuer Vorgang</span>
                <h1 class="ml-title" id="new-title">Neuer <span class="amber">Vorgang.</span></h1>
                <p class="ml-sub">Ziel eingeben, Kurzlink erhalten.</p>
            </section>

            @guest
                <div class="ml-notice" role="note" style="margin-top: 1.25rem;">
                    <p>Mitglied? <a href="{{ url('/login') }}">Log dich ein</a> für eigene Kürzel, kürzere Links &amp; Statistiken — oder stell den Antrag als Gast.</p>
                </div>
            @else
                <div class="ml-notice" role="note" style="margin-top: 1.25rem;">
                    <p>Angemeldet — deine Vorgänge landen mit Statistik im <a href="{{ \App\Support\DomainUrls::dashboard('/') }}">Dashboard</a>.</p>
                </div>
            @endguest

            <section class="ml-board-zone" id="new-link" aria-label="Kurzlink erstellen">
                <livewire:public.shorten-form :compact="true" :minimal="true" locale="de" theme="board" />
            </section>

            <p class="ml-new-back"><a href="/">← zurück zur Übersicht</a></p>
        </main>

        <footer class="ml-foot">
            Amt für kurze Links · Dienststelle meinlink.at · <a href="https://ternis.link/pages/legal/privacy">Datenschutz</a> ·
            <a href="https://ternis.link/pages/legal/terms">AGB</a>
        </footer>
    </div>

    @livewireScripts
</body>
</html>
