@props(['code' => '500', 'title' => 'Something went wrong'])

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
                <span class="sk-kicker">a small detour</span>
                <h1 class="sk-title"><span class="sk-u">{{ $code }}</span> — {{ $title }}</h1>
                <p class="sk-sub">{{ $slot }}</p>
            </div>

            @if (trim($actions ?? '') !== '')
                <div class="sk-error-actions" aria-label="Error actions">{{ $actions }}</div>
            @endif
        </main>

        <footer class="sk-foot">
            href.nz · <a href="https://ternis.link/pages/legal/privacy">privacy</a> ·
            <a href="https://ternis.link/pages/legal/terms">terms</a>
        </footer>
    </div>
</body>
</html>
