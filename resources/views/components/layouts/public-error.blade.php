@props(['code' => '500', 'title' => 'Something went wrong'])

@php
// Per-status personality: kicker badge + one-line follow-up hint.
// Unknown codes fall back to the generic detour copy.
[$kicker, $hint] = match ((string) $code) {
    '404' => ['lost link', 'Links are case-sensitive — double-check the spelling, or the link was deactivated or expired.'],
    '403' => ['members only', 'This corner needs a signed-in member — log in and try again.'],
    '419' => ['stale page', 'Your session timed out while the page sat open — reload and try again.'],
    '429' => ['too fast', 'Too many tries in a row — wait a moment, then retry. Guests get 10 a minute.'],
    '500' => ['our fault', 'Something broke on our side and we’ve logged it — try again in a moment.'],
    '503' => ['napping', 'Maintenance or startup — redirects will be back shortly.'],
    default => ['a small detour', null],
};
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ $title }} · href.nz</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://href.nz{{ request()->getPathInfo() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/landing-public.css'])
</head>
<body class="sk-root">
    <a class="sk-skip" href="#error-content">Skip to the error message</a>

    <div class="sk-wrap">
        <header class="sk-head">
            <a href="/" class="sk-brand" aria-label="href.nz home">href<span>.nz</span></a>
            <nav aria-label="Back">
                <a href="/" class="sk-login">
                    back to shortener
                    <svg width="26" height="12" viewBox="0 0 28 14" fill="none" aria-hidden="true"><path d="M26 10 C 18 8, 10 7.5, 4 8.5 M4 8.5 L9.5 5 M4 8.5 L9.5 12" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            </nav>
        </header>

        <main id="error-content" class="sk-error" tabindex="-1">
            <div class="sk-hero">
                @switch((string) $code)
                    @case('404')
                        {{-- magnifier over a broken link --}}
                        <svg class="dk" style="top: 6px; left: 8px; transform: rotate(-10deg);" width="46" height="46" viewBox="0 0 48 48" fill="none" aria-hidden="true"><circle cx="21" cy="21" r="12" stroke="currentColor" stroke-width="2.4"/><path d="M30 30l9 9" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><path d="M17 21h8M21 17v8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                        @break
                    @case('403')
                        {{-- lock --}}
                        <svg class="dk" style="top: 6px; left: 8px; transform: rotate(-8deg);" width="42" height="48" viewBox="0 0 32 40" fill="none" aria-hidden="true"><path d="M8 18h16v14H8z M11 18v-4a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="16" cy="25" r="1.6" fill="currentColor"/></svg>
                        @break
                    @case('419')
                        {{-- refresh --}}
                        <svg class="dk" style="top: 6px; left: 8px; transform: rotate(10deg);" width="46" height="46" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M10 24a14 14 0 1 1 4.1 9.9" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><path d="M10 35v-8h8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @break
                    @case('429')
                        {{-- hourglass --}}
                        <svg class="dk" style="top: 6px; left: 8px; transform: rotate(6deg);" width="40" height="48" viewBox="0 0 32 40" fill="none" aria-hidden="true"><path d="M8 5h16 M8 35h16 M10 5c0 8 6 9 6 15s-6 7-6 15 M22 5c0 8-6 9-6 15s6 7 6 15" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @break
                    @case('500')
                        {{-- cracked heart --}}
                        <svg class="dk" style="top: 6px; left: 8px; transform: rotate(-8deg);" width="46" height="44" viewBox="0 0 48 44" fill="none" aria-hidden="true"><path d="M24 39 C 15 30, 7 24, 8.5 16.5 C 9.7 10.5, 16 10.5, 20 16 L24 22 L22 14 L26 18 L24 8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M24 39 C 33 30, 41 24, 39.5 16.5 C 38.3 10.5, 32 10.5, 28 16" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                        @break
                    @case('503')
                        {{-- moon --}}
                        <svg class="dk" style="top: 6px; left: 8px; transform: rotate(-10deg);" width="44" height="44" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M32 28 C 24 28, 17 21, 17 13 C 17 10 18 8 19 6 C 11 8.5, 6 15, 6 23 C 6 33, 14 40, 24 40 C 29 40, 33 38, 36 35 C 34 32, 33 30, 32 28 Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/><path d="M34 8l.8 2.2L37 11l-2.2.8L34 14l-.8-2.2L31 11l2.2-.8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                        @break
                    @default
                        {{-- sparkle --}}
                        <svg class="dk" style="top: 6px; left: 8px; transform: rotate(12deg);" width="36" height="36" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2.5c.7 4.8 2.1 6.9 7.5 7.5-5.4.6-6.8 2.7-7.5 7.5-.7-4.8-2.1-6.9-7.5-7.5 5.4-.6 6.8-2.7 7.5-7.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                @endswitch
                <svg class="dk" style="top: 0; right: 30px; transform: rotate(12deg);" width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2.5c.7 4.8 2.1 6.9 7.5 7.5-5.4.6-6.8 2.7-7.5 7.5-.7-4.8-2.1-6.9-7.5-7.5 5.4-.6 6.8-2.7 7.5-7.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>

                <span class="sk-kicker">{{ $kicker }}</span>
                <h1 class="sk-title"><span class="sk-u">{{ $code }}</span> — {{ $title }}</h1>
                <p class="sk-sub">{{ $slot }}</p>
                @if ($hint)
                    <p class="sk-sub sk-error-hint">{{ $hint }}</p>
                @endif
            </div>

            @if (trim($actions ?? '') !== '')
                <div class="sk-error-actions" aria-label="Error actions">{{ $actions }}</div>
            @endif

            <p class="sk-new-back">or <a href="/new">shorten a new link</a> instead</p>
        </main>

        <footer class="sk-foot">
            href.nz · <a href="https://ternis.link/pages/legal/privacy">privacy</a> ·
            <a href="https://ternis.link/pages/legal/terms">terms</a>
        </footer>
    </div>
</body>
</html>
