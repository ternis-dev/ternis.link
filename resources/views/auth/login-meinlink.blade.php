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
<body class="sk-root ml-root">
    <a class="sk-skip" href="#login">Zum Login-Knopf springen</a>

    <div class="sk-wrap">
        <header class="sk-head">
            <a href="/" class="sk-brand" aria-label="meinlink.at Startseite">meinlink<span>.at</span></a>
            <nav aria-label="Zurück">
                <a href="/" class="sk-login">
                    zurück zum Kürzer
                    <svg width="26" height="12" viewBox="0 0 28 14" fill="none" aria-hidden="true"><path d="M26 10 C 18 8, 10 7.5, 4 8.5 M4 8.5 L9.5 5 M4 8.5 L9.5 12" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            </nav>
        </header>

        <section class="sk-hero">
            <svg class="dk" style="top: 6px; left: 8px; transform: rotate(-6deg);" width="46" height="34" viewBox="0 0 64 44" fill="none" aria-hidden="true"><path d="M4 38 L22 10 L30 24 L37 14 L60 38" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <svg class="dk ml-dk-red" style="top: 0; right: 30px; transform: rotate(12deg);" width="36" height="36" viewBox="0 0 36 36" fill="none" aria-hidden="true"><circle cx="18" cy="18" r="3" stroke="currentColor" stroke-width="2"/><ellipse cx="18" cy="9" rx="3" ry="5" stroke="currentColor" stroke-width="1.8"/><ellipse cx="18" cy="27" rx="3" ry="5" stroke="currentColor" stroke-width="1.8"/><ellipse cx="9" cy="18" rx="5" ry="3" stroke="currentColor" stroke-width="1.8"/><ellipse cx="27" cy="18" rx="5" ry="3" stroke="currentColor" stroke-width="1.8"/></svg>

            <span class="sk-kicker">nur für Mitglieder</span>

            <h1 class="sk-title" id="page-title">Mitglieder
                <span class="sk-u">loggen sich
                    <svg viewBox="0 0 150 14" fill="none" preserveAspectRatio="none" aria-hidden="true"><path d="M4 9.5 C 35 5.5, 60 11.5, 90 8 S 130 8, 146 6.5" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/></svg>
                </span> hier ein.
            </h1>

            <p class="sk-sub">Ein Knopf, kein Passwort — die Anmeldung läuft über Ternis Auth SSO.
                Mitglieder wählen eigene Kürzel, kürzere Links &amp; Klick-Statistiken.</p>

            <ol class="sk-steps" aria-label="So geht's">
                <li><span class="n" aria-hidden="true">1</span> Knopf drücken</li>
                <svg class="sk-step-arrow" width="26" height="16" viewBox="0 0 26 16" fill="none" aria-hidden="true"><path d="M2 10 C 9 8.5, 15 8.5, 21 9.5 M21 9.5 L15.5 6 M21 9.5 L15.5 13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <li><span class="n" aria-hidden="true">2</span> Bei Ternis Auth anmelden</li>
                <svg class="sk-step-arrow" width="26" height="16" viewBox="0 0 26 16" fill="none" aria-hidden="true"><path d="M2 10 C 9 8.5, 15 8.5, 21 9.5 M21 9.5 L15.5 6 M21 9.5 L15.5 13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <li><span class="n" aria-hidden="true">3</span> Im Dashboard landen
                    <svg width="15" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4.5 12.5 10 18 19.5 6.5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </li>
            </ol>
        </section>

        <section class="sk-form-zone" id="login" aria-label="Mitglieder-Login">
            <div class="sk-card tilt-l">
                <span class="sk-tape" aria-hidden="true"></span>
                <h2 class="sk-form-title">ein Knopf, kein Passwort</h2>
                <p class="sk-form-sub">Die eigentliche Anmeldung passiert auf dem Dashboard-Host — deine
                    Login-Session lebt dort, Browser teilen keine Cookies zwischen Domains.</p>

                @if (session('error'))
                    <div class="sk-oops" role="alert">
                        <p class="sk-oops-msg">{{ session('error') }}</p>
                    </div>
                @endif

                <p style="margin: 1.1rem 0 0;">
                    <a class="sk-btn" style="text-decoration: none; display: inline-block;" href="{{ \App\Support\DomainUrls::dashboard('/login') }}">
                        Mit Ternis Auth einloggen
                    </a>
                </p>
                <p class="sk-hint">Bringt dich zu {{ config('domains.dashboard_host', 'dash.ternis.link') }} — du bist zurück, bevor der Kaffee kalt ist.</p>
            </div>
        </section>

        <aside class="sk-notes" aria-label="Gut zu wissen" style="margin-top: 2rem;">
            <div class="sk-note">
                <span class="sk-pin" aria-hidden="true"></span>
                <svg class="dk" style="top: 10px; right: 12px;" width="22" height="28" viewBox="0 0 24 30" fill="none" aria-hidden="true"><path d="M6.5 13.5 h11 v11 h-11 Z M9 13.5 V10 a3 3 0 0 1 6 0 v3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="18.5" r="1.2" fill="currentColor"/></svg>
                <h2>warum der Umweg?</h2>
                <p>Kurzlinks funktionieren überall, aber Login-Sessions reisen nicht zwischen Domains. Darum reicht dich der Knopf ans Dashboard weiter — gleiches Konto, sicherere Session.</p>
            </div>
            <div class="sk-note">
                <span class="sk-pin" aria-hidden="true"></span>
                <svg class="dk" style="top: 10px; right: 12px;" width="24" height="30" viewBox="0 0 24 30" fill="none" aria-hidden="true"><path d="M12 3 C 8 3, 5 6.2, 5 10 c0 2.5 1.3 4.2 2.7 5.3 .7.6 1 1.1 1 2.2 h6.6 c0-1.1.3-1.6 1-2.2 C 17.7 14.2, 19 12.5, 19 10 C 19 6.2, 16 3, 12 3 Z M9.5 21.5 h5 M10.5 25 h3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <h2>noch kein Konto?</h2>
                <p>Konten kommen von Ternis Auth — als Familie, Partner oder Kunde hast du schon alles, was du brauchst. Gäste können einfach <a href="/">Links kürzen</a>, ganz ohne Konto.</p>
            </div>
        </aside>

        <footer class="sk-foot">
            skizziert von ternis.link von <a href="https://ternis.dev">ternis.dev</a> · gehostet auf <a href="https://ternis.net">ternis.net</a>
            · <a href="https://ternis.link/pages/legal/privacy">Datenschutz</a> · <a href="https://ternis.link/pages/legal/terms">AGB</a> · <a href="https://ternis.dev/en/legal/imprint">Impressum</a>
        </footer>
    </div>
</body>
</html>
