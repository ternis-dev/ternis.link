<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>meinlink.at — Wohin darf's gehen?</title>
    <meta name="description" content="meinlink.at — der Kurzlink-Automat. Ziel eingeben, 8-Zeichen-Kurzlink erhalten. Gratis, sofort, kein Konto nötig.">
    <meta name="theme-color" content="#101014">
    <link rel="canonical" href="https://meinlink.at/">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="meinlink.at">
    <meta property="og:title" content="meinlink.at — Wohin darf's gehen?">
    <meta property="og:description" content="Ziel eingeben, 8-Zeichen-Kurzlink erhalten. Kein Konto nötig.">
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
    <a class="ml-skip" href="#shorten">Zum Automaten springen</a>

    <div class="ml-wrap">
        <header class="ml-top">
            <a href="/" class="ml-brand" aria-label="meinlink.at Startseite">MEINLINK<b>.AT</b> ▮</a>
            <div class="ml-top-right">
                <span class="ml-clock" id="ml-clock" aria-label="Aktuelle Uhrzeit">--:--:--</span>
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
            <span class="ml-kicker">Abfahrtsanzeige · Gleis 8</span>
            <h1 class="ml-title" id="page-title">Wohin darf's <span class="amber">gehen?</span></h1>
            <p class="ml-sub">Ziel unten eingeben, Kurzlink erhalten — <code>8 Zeichen</code>, gratis, ohne Konto. Pünktlich wie ein Uhrwerk.</p>

            <ol class="ml-steps" aria-label="So geht's">
                <li><span class="n" aria-hidden="true">1</span>Ziel eingeben</li>
                <li><span class="n" aria-hidden="true">2</span>Abfahrt drücken</li>
                <li><span class="n" aria-hidden="true">3</span>Kopieren &amp; teilen</li>
            </ol>
        </section>

        <section class="ml-board-zone" id="shorten" aria-label="Link kürzen">
            <livewire:public.shorten-form locale="de" theme="board" />
        </section>

        <div class="ml-info">
            <div class="ml-info-card">
                <h2><span class="amber">◈</span> OHNE FAHRSCHEIN</h2>
                <p>Gäste fahren ohne Konto: automatische 8-Zeichen-Codes, bis zu 50 am Tag.</p>
                <p>Eigene Kürzel, kürzere Links &amp; Statistiken gibt's für Mitglieder — <a href="{{ url('/login') }}">einloggen</a>.</p>
            </div>
            <div class="ml-info-card">
                <h2><span class="amber">◈</span> PÜNKTLICH &amp; PRIVAT</h2>
                <p>Fair Use: 50 Links pro Tag und Gast. Gespeichert wird nur ein gehashter Zähler, sonst nichts.</p>
                <p>Links bleiben eingestellt, solange sie in Ordnung sind — Missbrauch fliegt raus.</p>
            </div>
            <div class="ml-info-card">
                <h2><span class="amber">◈</span> BORDPERSONAL</h2>
                <p>Links, denen man den Absender ansieht? Offizielle Links mit Statistik gibt's auf <a href="https://href.re">href.re</a> — nur für Unternehmen.</p>
            </div>
        </div>

        <div class="ml-ticker" aria-label="Auf einen Blick">
            <span><strong>8</strong> ZEICHEN · <strong>50</strong> FREIFAHRTEN PRO TAG · <strong>0</strong> KONTO NÖTIG</span>
            <a href="https://ternis.link/pages/stats">Netz-Statistik →</a>
        </div>

        <footer class="ml-foot">
            meinlink.at · <a href="https://ternis.link/pages/legal/privacy">Datenschutz</a> ·
            <a href="https://ternis.link/pages/legal/terms">AGB</a> · <a href="https://ternis.dev/en/legal/imprint">Impressum</a>
        </footer>
    </div>

    @livewireScripts

    <script>
    /* Bahnhofsuhr: läuft in Europe/Berlin, deutsche Schreibweise. */
    (function () {
        var clock = document.getElementById('ml-clock');
        if (!clock) return;

        function tick() {
            try {
                clock.textContent = new Date().toLocaleTimeString('de-DE', {
                    hour: '2-digit', minute: '2-digit', second: '2-digit',
                    timeZone: 'Europe/Berlin',
                });
            } catch (e) {
                clock.textContent = '';
            }
        }

        tick();
        setInterval(tick, 1000);
    })();
    </script>
</body>
</html>
