<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>meinlink.at — Amt für kurze Links</title>
    <meta name="description" content="meinlink.at">
    <meta name="theme-color" content="#fafaf7">
    <link rel="canonical" href="https://meinlink.at/">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="meinlink.at">
    <meta property="og:title" content="meinlink.at">
    <meta property="og:description" content="Kein Konto nötig.">
    <meta property="og:url" content="https://meinlink.at/">
    <meta name="twitter:card" content="summary">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/meinlink-at.css'])
    @livewireStyles
    @if (config('services.turnstile.key'))
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
    @endif
</head>
<body class="ml-board">
    <a class="ml-skip" href="#shorten">Zum Formular springen</a>

    <div class="ml-wrap">
        <header class="top">
            <a href="/" class="brand" aria-label="meinlink.at Startseite">meinlink.at</a>
            <div class="top-right">
                <nav aria-label="Konto">
                    @auth
                        <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="ml-login">Dashboard öffnen →</a>
                    @else
                        <a href="{{ url('/login') }}" class="login">Mitglieder-Login <svg class="arrow-right parent-hover-move"></svg></a>
                    @endauth
                </nav>
            </div>
        </header>

        <section class="hero">
            
        </section>

        <section class="" id="shorten" aria-label="Link kürzen">
            <!--livewire:public.shorten-form locale="de" theme="board" /-->
        </section>

        <footer class="foot">
            Amt für kurze Links · Dienststelle meinlink.at · <a href="https://ternis.link/pages/legal/privacy">Datenschutz</a> ·
            <a href="https://ternis.link/pages/legal/terms">AGB</a> · <a href="https://ternis.dev/de/legal/imprint">Impressum</a>
        </footer>
    </div>

    @livewireScripts
</body>
</html>
