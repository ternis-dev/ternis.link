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
<body class="sk-root ml-root sk-new-page">
    <a class="sk-skip" href="#new-link">Zum Link-Formular springen</a>

    <div class="sk-wrap sk-new-wrap">
        <header class="sk-head sk-new-head">
            <a href="/" class="sk-brand" aria-label="meinlink.at Startseite">meinlink<span>.at</span></a>
            <nav aria-label="Konto">
                @auth
                    <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="sk-login">Dashboard öffnen <span aria-hidden="true">→</span></a>
                @else
                    <a href="{{ url('/login') }}" class="sk-login">Mitglieder-Login <span aria-hidden="true">→</span></a>
                @endauth
            </nav>
        </header>

        <main class="sk-new-main">
            <section class="sk-new-intro" aria-labelledby="new-title">
                <h1 class="sk-title sk-new-title" id="new-title">mach etwas
                    <span class="sk-u">kurz.</span>
                </h1>
                <p class="sk-sub">Füg ein Ziel ein und wir machen einen ordentlichen meinlink.at-Link daraus.</p>
            </section>

            @guest
                <p class="sk-new-login" role="note">
                    Mitglied? <a href="{{ url('/login') }}">Log dich ein</a> für eigene Kürzel, kürzere Links &amp; Klick-Statistiken —
                    oder mach als Gast weiter.
                </p>
            @else
                <p class="sk-new-login" role="note">
                    Angemeldet — deine Links landen mit Statistik im <a href="{{ \App\Support\DomainUrls::dashboard('/') }}">Dashboard</a>.
                </p>
            @endguest

            <section class="sk-form-zone sk-new-form-zone" id="new-link" aria-label="Kurzlink erstellen">
                <livewire:public.shorten-form :compact="true" :minimal="true" locale="de" />
            </section>

            <p class="sk-new-back"><a href="/">← zurück zum vollen Kürzer</a></p>
        </main>

        <footer class="sk-foot">
            meinlink.at · <a href="https://ternis.link/pages/legal/privacy">Datenschutz</a> ·
            <a href="https://ternis.link/pages/legal/terms">AGB</a>
        </footer>
    </div>

    @livewireScripts
</body>
</html>
