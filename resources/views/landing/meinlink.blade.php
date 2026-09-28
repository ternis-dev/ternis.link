<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>meinlink.at — Amt für kurze Links</title>
    <meta name="description" content="meinlink.at — Antrag auf Linkkürzung. Lange URL einreichen, 8-Zeichen-Kurzlink erhalten. Gratis, sofort, kein Konto nötig.">
    <meta name="theme-color" content="#fafaf7">
    <link rel="canonical" href="https://meinlink.at/">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="meinlink.at">
    <meta property="og:title" content="meinlink.at — Amt für kurze Links">
    <meta property="og:description" content="Antrag einreichen, 8-Zeichen-Kurzlink erhalten. Kein Konto nötig.">
    <meta property="og:url" content="https://meinlink.at/">
    <meta name="twitter:card" content="summary">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/landing-meinlink.css'])
    @livewireStyles
    @if (config('services.turnstile.key'))
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
    @endif
</head>
<body class="ml-board">
    <a class="ml-skip" href="#shorten">Zum Formular springen</a>

    <div class="ml-wrap">
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

        <section class="ml-hero">
            <span class="ml-kicker">Formular LK-8 <span class="red">·</span> Antrag auf Linkkürzung</span>
            <h1 class="ml-title" id="page-title">Kurz. Kürzer. <span class="amber">Gekürzt.</span></h1>
            <p class="ml-sub">Bitte vollständig ausfüllen: lange URL einreichen, <code>8-Zeichen-Kurzlink</code> erhalten. Bearbeitung sofort, Gebühren: keine, Konto: nicht nötig.</p>

            <ol class="ml-steps" aria-label="Ablauf">
                <li><span class="n" aria-hidden="true">01</span>Formular ausfüllen</li>
                <li><span class="n" aria-hidden="true">02</span>Antrag einreichen</li>
                <li><span class="n" aria-hidden="true">03</span>Vorgangsnummer erhalten</li>
            </ol>
        </section>

        <section class="ml-board-zone" id="shorten" aria-label="Link kürzen">
            <livewire:public.shorten-form locale="de" theme="board" />
        </section>

        <div class="ml-info">
            <div class="ml-info-card">
                <h2><span class="num" aria-hidden="true">01</span>OHNE ANTRAGSTELLER</h2>
                <p>Gäste brauchen kein Konto: automatische 8-Zeichen-Codes, bis zu 50 Vorgänge am Tag.</p>
                <p>Eigene Kürzel, kürzere Links &amp; Statistiken gibt's für Mitglieder — <a href="{{ url('/login') }}">einloggen</a>.</p>
            </div>
            <div class="ml-info-card">
                <h2><span class="num" aria-hidden="true">02</span>DATENSCHUTZ</h2>
                <p>Zu jedem Vorgang wird nur ein gehashter Zähler gespeichert, sonst nichts.</p>
                <p>Vorgänge bleiben bestehen, solange sie in Ordnung sind — Missbrauch wird aussortiert.</p>
            </div>
            <div class="ml-info-card">
                <h2><span class="num" aria-hidden="true">03</span>DIENSTLEISTUNG</h2>
                <p>Links, denen man den Absender ansieht? Geprüfte Links mit Statistik gibt's auf <a href="https://href.re">href.re</a> — nur für Unternehmen.</p>
            </div>
        </div>

        <div class="ml-ticker" aria-label="Auf einen Blick">
            <span><strong>8</strong> ZEICHEN · <strong>50</strong> VORGÄNGE PRO TAG · <strong>0</strong> € GEBÜHR</span>
            <a href="https://ternis.link/pages/stats">Netz-Statistik →</a>
        </div>

        <footer class="ml-foot">
            Amt für kurze Links · Dienststelle meinlink.at · <a href="https://ternis.link/pages/legal/privacy">Datenschutz</a> ·
            <a href="https://ternis.link/pages/legal/terms">AGB</a> · <a href="https://ternis.dev/de/legal/imprint">Impressum</a>
        </footer>
    </div>

    @livewireScripts
</body>
</html>
