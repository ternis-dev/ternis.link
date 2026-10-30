<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#b42333">
    <title>@yield('title', 'Meine Links') · meinlink.at</title>
    <link rel="icon" href="{{ asset('favicon-meinlink-dashboard.svg') }}">
    @vite(['resources/css/meinlink-dashboard.css', 'resources/js/meinlink-dashboard.js'])
</head>
<body>
    <a class="skip" href="#inhalt">Zum Inhalt</a>
    <header class="header"><div class="shell header-inner">
        <a class="brand" href="{{ route('meinlink-dashboard.index') }}">meinlink<span>.at</span><small>MEIN BEREICH</small></a>
        <nav aria-label="Hauptnavigation">
            <a href="{{ route('meinlink-dashboard.index') }}" @if(request()->routeIs('meinlink-dashboard.index', 'meinlink-dashboard.links', 'meinlink-dashboard.show', 'meinlink-dashboard.edit')) aria-current="page" @endif>Meine Links</a>
            @auth<a href="{{ route('meinlink-dashboard.statistics') }}" @if(request()->routeIs('meinlink-dashboard.statistics')) aria-current="page" @endif>Statistiken</a>@endauth
            <a href="{{ route('meinlink-dashboard.help') }}" @if(request()->routeIs('meinlink-dashboard.help')) aria-current="page" @endif>Hilfe</a>
            @auth<form method="post" action="/logout">@csrf<button class="text-button" type="submit">Abmelden</button></form>@endauth
        </nav>
    </div></header>
    <main id="inhalt" class="shell main">
        @if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="notice error" role="alert">Die Anmeldung konnte nicht abgeschlossen werden. Bitte versuche es erneut.</div>@endif
        @if($errors->any())<div class="notice error" role="alert"><strong>Bitte prüfe deine Eingaben.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
    <footer class="shell footer"><span>Kurze Links. Alles im Blick.</span><nav aria-label="Rechtliches">
        <a href="https://meinlink.at">meinlink.at</a>
        <a href="https://ternis.link/pages/legal/privacy">Datenschutz (EN)</a>
        <a href="https://ternis.link/pages/legal/terms">Nutzungsbedingungen (EN)</a>
        <a href="{{ \App\Support\DomainUrls::impressum(config('domains.meinlink_dashboard_host'), 'de') }}">Impressum</a>
    </nav></footer>
</body>
</html>
