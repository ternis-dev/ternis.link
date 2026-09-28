@props(['code' => '500', 'title' => 'Etwas ist schiefgelaufen'])

@php
[$kicker, $hint] = match ((string) $code) {
    '404' => ['verlorener Link', 'Links unterscheiden Groß- und Kleinschreibung — prüf die Schreibweise, oder der Link wurde deaktiviert bzw. ist abgelaufen.'],
    '403' => ['nur für Mitglieder', 'Diese Ecke braucht ein angemeldetes Mitglied — log dich ein und versuch es erneut.'],
    '419' => ['alte Seite', 'Deine Session ist abgelaufen, während die Seite offen war — lad neu und versuch es erneut.'],
    '429' => ['zu schnell', 'Zu viele Versuche hintereinander — wart einen Moment und versuch es erneut. Gäste haben 10 pro Minute.'],
    '500' => ['unser Fehler', 'Bei uns ist etwas kaputtgegangen und wir haben es protokolliert — versuch es gleich nochmal.'],
    '503' => ['macht Nickerchen', 'Wartung oder Neustart — Weiterleitungen sind gleich zurück.'],
    default => ['kleiner Umweg', null],
};
@endphp

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ $title }} · meinlink.at</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://meinlink.at{{ request()->getPathInfo() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/landing-meinlink.css'])
</head>
<body class="sk-root ml-root">
    <a class="sk-skip" href="#error-content">Zur Fehlermeldung springen</a>

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

        <main id="error-content" class="sk-error" tabindex="-1">
            <div class="sk-hero">
                @switch((string) $code)
                    @case('404')
                        <svg class="dk" style="top: 6px; left: 8px; transform: rotate(-10deg);" width="46" height="46" viewBox="0 0 48 48" fill="none" aria-hidden="true"><circle cx="21" cy="21" r="12" stroke="currentColor" stroke-width="2.4"/><path d="M30 30l9 9" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><path d="M17 21h8M21 17v8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                        @break
                    @case('403')
                        <svg class="dk" style="top: 6px; left: 8px; transform: rotate(-8deg);" width="42" height="48" viewBox="0 0 32 40" fill="none" aria-hidden="true"><path d="M8 18h16v14H8z M11 18v-4a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="16" cy="25" r="1.6" fill="currentColor"/></svg>
                        @break
                    @case('419')
                        <svg class="dk" style="top: 6px; left: 8px; transform: rotate(10deg);" width="46" height="46" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M10 24a14 14 0 1 1 4.1 9.9" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><path d="M10 35v-8h8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @break
                    @case('429')
                        <svg class="dk" style="top: 6px; left: 8px; transform: rotate(6deg);" width="40" height="48" viewBox="0 0 32 40" fill="none" aria-hidden="true"><path d="M8 5h16 M8 35h16 M10 5c0 8 6 9 6 15s-6 7-6 15 M22 5c0 8-6 9-6 15s6 7 6 15" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @break
                    @case('500')
                        <svg class="dk" style="top: 6px; left: 8px; transform: rotate(-8deg);" width="46" height="44" viewBox="0 0 48 44" fill="none" aria-hidden="true"><path d="M24 39 C 15 30, 7 24, 8.5 16.5 C 9.7 10.5, 16 10.5, 20 16 L24 22 L22 14 L26 18 L24 8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M24 39 C 33 30, 41 24, 39.5 16.5 C 38.3 10.5, 32 10.5, 28 16" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                        @break
                    @case('503')
                        <svg class="dk" style="top: 6px; left: 8px; transform: rotate(-10deg);" width="44" height="44" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M32 28 C 24 28, 17 21, 17 13 C 17 10 18 8 19 6 C 11 8.5, 6 15, 6 23 C 6 33, 14 40, 24 40 C 29 40, 33 38, 36 35 C 34 32, 33 30, 32 28 Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/><path d="M34 8l.8 2.2L37 11l-2.2.8L34 14l-.8-2.2L31 11l2.2-.8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                        @break
                    @default
                        <svg class="dk ml-dk-red" style="top: 6px; left: 8px; transform: rotate(12deg);" width="36" height="36" viewBox="0 0 36 36" fill="none" aria-hidden="true"><circle cx="18" cy="18" r="3" stroke="currentColor" stroke-width="2"/><ellipse cx="18" cy="9" rx="3" ry="5" stroke="currentColor" stroke-width="1.8"/><ellipse cx="18" cy="27" rx="3" ry="5" stroke="currentColor" stroke-width="1.8"/><ellipse cx="9" cy="18" rx="5" ry="3" stroke="currentColor" stroke-width="1.8"/><ellipse cx="27" cy="18" rx="5" ry="3" stroke="currentColor" stroke-width="1.8"/></svg>
                @endswitch
                <svg class="dk ml-dk-red" style="top: 0; right: 30px; transform: rotate(12deg);" width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2.5c.7 4.8 2.1 6.9 7.5 7.5-5.4.6-6.8 2.7-7.5 7.5-.7-4.8-2.1-6.9-7.5-7.5 5.4-.6 6.8-2.7 7.5-7.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>

                <span class="sk-kicker">{{ $kicker }}</span>
                <h1 class="sk-title"><span class="sk-u">{{ $code }}</span> — {{ $title }}</h1>
                <p class="sk-sub">{{ $slot }}</p>
                @if ($hint)
                    <p class="sk-sub sk-error-hint">{{ $hint }}</p>
                @endif
            </div>

            @if (trim($actions ?? '') !== '')
                <div class="sk-error-actions" aria-label="Aktionen">{{ $actions }}</div>
            @endif

            <p class="sk-new-back">oder <a href="/new">kürz stattdessen einen neuen Link</a></p>
        </main>

        <footer class="sk-foot">
            meinlink.at · <a href="https://ternis.link/pages/legal/privacy">Datenschutz</a> ·
            <a href="https://ternis.link/pages/legal/terms">AGB</a>
        </footer>
    </div>
</body>
</html>
