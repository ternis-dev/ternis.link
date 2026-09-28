@props(['code' => '500', 'title' => 'Etwas ist schiefgelaufen'])

@php
[$kicker, $hint] = match ((string) $code) {
    '404' => ['Link nicht gefunden', 'Der aufgerufene Kurzlink existiert nicht, wurde deaktiviert oder ist abgelaufen. Bitte prüfe die Schreibweise.'],
    '403' => ['Zugriff verweigert', 'Für diese Seite oder Aktion ist eine Anmeldung erforderlich. Bitte melde dich mit deinem Konto an.'],
    '419' => ['Sitzung abgelaufen', 'Deine Sitzung ist abgelaufen. Bitte lade die Seite neu und versuche es erneut.'],
    '429' => ['Zu viele Anfragen', 'Bitte warte einen kurzen Moment, bevor du eine neue Anfrage sendest.'],
    '500' => ['Serverfehler', 'Es ist ein unerwarteter Fehler aufgetreten. Unser Team wurde informiert.'],
    '503' => ['Wartungsarbeiten', 'Wir führen aktuell geplante Wartungsarbeiten durch. In Kürze ist meinlink.at wieder erreichbar.'],
    default => ['Hinweis', null],
};
@endphp

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ $title }} · meinlink.at</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#dc2626">
    <link rel="canonical" href="https://meinlink.at{{ request()->getPathInfo() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/meinlink.css'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased selection:bg-red-500 selection:text-white dark:bg-zinc-950 dark:text-zinc-100">
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 h-[450px] w-[700px] rounded-full bg-gradient-to-b from-red-500/10 via-rose-500/5 to-transparent blur-3xl"></div>
    </div>

    <div class="mx-auto flex min-h-screen max-w-xl flex-col justify-between px-4 py-8 sm:px-6">
        <header class="flex items-center justify-between">
            <a href="/" class="text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100" aria-label="meinlink.at Startseite">
                meinlink<span class="text-red-600">.at</span>
            </a>

            <nav aria-label="Navigation">
                <a href="/" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                    ← Zurück zur Startseite
                </a>
            </nav>
        </header>

        <main id="error-content" class="my-auto py-10 text-center" tabindex="-1">
            <span class="inline-flex items-center rounded-full border border-red-500/20 bg-red-500/10 px-3 py-1 text-xs font-semibold text-red-700 dark:border-red-500/30 dark:bg-red-950/40 dark:text-red-300">
                {{ $kicker }}
            </span>

            <p class="mt-4 font-display text-6xl font-black tracking-tight text-zinc-900 sm:text-7xl dark:text-zinc-50">
                {{ $code }}<span class="text-red-600">.</span>
            </p>

            <h1 class="mt-2 text-xl font-bold tracking-tight text-zinc-800 sm:text-2xl dark:text-zinc-200">
                {{ $title }}
            </h1>

            <div class="mx-auto mt-4 max-w-md text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                {{ $slot }}
            </div>

            @if ($hint)
                <p class="mx-auto mt-2 max-w-md text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $hint }}
                </p>
            @endif

            @if (trim($actions ?? '') !== '')
                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    {{ $actions }}
                </div>
            @else
                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <a href="/" class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500 active:scale-[0.98]">
                        Zur Startseite
                    </a>
                    <a href="/new" class="inline-flex items-center rounded-xl border border-zinc-200 bg-white px-5 py-2.5 text-sm font-semibold text-zinc-700 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
                        Neuen Link erstellen
                    </a>
                </div>
            @endif
        </main>

        <footer class="border-t border-zinc-200/80 pt-6 text-center text-xs text-zinc-500 dark:border-zinc-800/80 dark:text-zinc-400">
            <div class="flex flex-wrap items-center justify-center gap-4">
                <span>meinlink.at</span>
                <span>·</span>
                <a href="https://ternis.link/pages/legal/privacy" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">Datenschutz</a>
                <span>·</span>
                <a href="https://ternis.link/pages/legal/terms" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">AGB</a>
                <span>·</span>
                <a href="https://ternis.dev/de/legal/imprint" class="transition hover:text-zinc-900 dark:hover:text-zinc-100">Impressum</a>
            </div>
        </footer>
    </div>
</body>
</html>
