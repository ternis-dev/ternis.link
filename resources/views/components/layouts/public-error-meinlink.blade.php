@props(['code' => '500', 'title' => 'Etwas ist schiefgelaufen'])

@php
[$kicker, $hint] = match ((string) $code) {
    '404' => ['Aktenzeichen unbekannt', 'Vorgänge unterscheiden Groß- und Kleinschreibung — prüf die Schreibweise, oder der Vorgang wurde aussortiert bzw. ist abgelaufen.'],
    '403' => ['Keine Berechtigung', 'Dieser Schalter braucht ein angemeldetes Mitglied — log dich ein und versuch es erneut.'],
    '419' => ['Sitzung abgelaufen', 'Deine Sitzung ist abgelaufen, während das Formular offen war — lad neu und versuch es erneut.'],
    '429' => ['Bitte hinten anstellen', 'Zu viele Anträge auf einmal — wart einen Moment und versuch es erneut. Gäste haben 10 pro Minute.'],
    '500' => ['Hausgemacht', 'Bei uns ist etwas kaputtgegangen und wir haben es zu Protokoll genommen — versuch es gleich nochmal.'],
    '503' => ['Wartungsarbeiten', 'Wartung oder Neustart — Anträge sind gleich wieder möglich.'],
    default => ['Störungsmeldung', null],
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
<body class="ml-board">
    <a class="ml-skip" href="#error-content">Zur Störungsmeldung springen</a>

    <div class="ml-wrap ml-narrow">
        <header class="ml-top">
            <a href="/" class="ml-brand" aria-label="meinlink.at Startseite">meinlink.at<small>Amt für kurze Links</small></a>
            <div class="ml-top-right">
                <nav aria-label="Zurück">
                    <a href="/" class="ml-login">← zurück zum Formular</a>
                </nav>
            </div>
        </header>

        <main id="error-content" class="ml-error" tabindex="-1">
            <span class="ml-kicker">{{ $kicker }}</span>
            <p class="ml-error-code">{{ $code }}<span class="dot">.</span></p>
            <h1 class="ml-error-title">{{ $title }}</h1>
            <p class="ml-sub ml-error-msg" style="margin-top: 0.6rem;">{{ $slot }}</p>
            @if ($hint)
                <p class="ml-sub ml-error-msg" style="margin-top: 0.4rem;">{{ $hint }}</p>
            @endif

            @if (trim($actions ?? '') !== '')
                <div class="ml-error-actions" aria-label="Aktionen">{{ $actions }}</div>
            @endif

            <p class="ml-new-back">oder <a href="/new">einfach einen neuen Antrag stellen</a></p>
        </main>

        <footer class="ml-foot">
            Amt für kurze Links · Dienststelle meinlink.at · <a href="https://ternis.link/pages/legal/privacy">Datenschutz</a> ·
            <a href="https://ternis.link/pages/legal/terms">AGB</a>
        </footer>
    </div>
</body>
</html>
