<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mitglieder-Login — meinlink.at</title>
    <meta name="description" content="meinlink.at Mitglieder-Login — ein Knopf, kein Passwort. Anmeldung über Ternis Auth SSO für eigene Kürzel, kürzere Links und Klick-Statistiken.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://meinlink.at/login">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/landing-meinlink.css'])
</head>
<body class="ml-board">
    <a class="ml-skip" href="#login">Zum Login-Knopf springen</a>

    <div class="ml-wrap ml-narrow">
        <header class="ml-top">
            <a href="/" class="ml-brand" aria-label="meinlink.at Startseite">meinlink.at<small>Amt für kurze Links</small></a>
            <div class="ml-top-right">
                <nav aria-label="Zurück">
                    <a href="/" class="ml-login">← zurück zum Formular</a>
                </nav>
            </div>
        </header>

        <section class="ml-hero" style="padding-top: 2.25rem;">
            <span class="ml-kicker">Dienstausweis <span class="red">·</span> nur für Mitglieder</span>
            <h1 class="ml-title" id="page-title">Bitte <span class="amber">ausweisen.</span></h1>
            <p class="ml-sub">Ein Knopf, kein Passwort — die Anmeldung läuft über Ternis Auth SSO. Mitglieder wählen eigene Kürzel, kürzere Links &amp; Statistiken.</p>

            <ol class="ml-steps" aria-label="Ablauf">
                <li><span class="n" aria-hidden="true">01</span>Knopf drücken</li>
                <li><span class="n" aria-hidden="true">02</span>Bei Ternis Auth anmelden</li>
                <li><span class="n" aria-hidden="true">03</span>Im Dashboard landen</li>
            </ol>
        </section>

        <section class="ml-board-zone" id="login" aria-label="Mitglieder-Login">
            <div class="ml-panel ml-login-card">
                <div class="ml-panel-head">
                    <h2 class="ml-panel-title">ANMELDUNG</h2>
                </div>
                <p class="ml-sub" style="margin-bottom: 1rem; font-size: 0.95rem;">Die eigentliche Anmeldung passiert auf dem Dashboard-Host — deine Session lebt dort, Browser teilen keine Cookies zwischen Domains.</p>

                @if (session('error'))
                    <div class="ml-notice is-error" role="alert">
                        <p class="ml-notice-msg">{{ session('error') }}</p>
                    </div>
                @endif

                <p style="margin: 1rem 0 0;">
                    <a class="ml-btn" style="text-decoration: none; display: inline-block;" href="{{ \App\Support\DomainUrls::dashboard('/login') }}">
                        Mit Ternis Auth anmelden
                    </a>
                </p>
                <p class="ml-meta" style="margin-top: 0.8rem;"><span>Bringt dich zu {{ config('domains.dashboard_host', 'dash.ternis.link') }} — du bist zurück, bevor der Kaffee kalt ist.</span></p>
            </div>
        </section>

        <div class="ml-info" style="margin-top: 1.75rem;">
            <div class="ml-info-card">
                <h2><span class="num" aria-hidden="true">01</span>WARUM DER UMWEG?</h2>
                <p>Kurzlinks funktionieren überall, aber Login-Sessions reisen nicht zwischen Domains. Darum reicht dich der Knopf ans Dashboard weiter.</p>
            </div>
            <div class="ml-info-card">
                <h2><span class="num" aria-hidden="true">02</span>NOCH KEIN KONTO?</h2>
                <p>Konten kommen von Ternis Auth. Gäste können einfach <a href="/">Links kürzen</a> — ganz ohne Konto.</p>
            </div>
        </div>

        <footer class="ml-foot">
            Amt für kurze Links · Dienststelle meinlink.at · <a href="https://ternis.link/pages/legal/privacy">Datenschutz</a> ·
            <a href="https://ternis.link/pages/legal/terms">AGB</a> · <a href="https://ternis.dev/de/legal/imprint">Impressum</a>
        </footer>
    </div>
</body>
</html>
