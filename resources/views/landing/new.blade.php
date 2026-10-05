<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>make a short link — href.nz</title>
    <meta name="description" content="Create a short href.nz link. Guests are welcome; members get custom slugs and click stats.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://href.nz/new">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/landing-public.css'])
    @livewireStyles
    @if (config('services.turnstile.key'))
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
    @endif
</head>
<body class="sk-root sk-new-page">
    <a class="sk-skip" href="#new-link">Skip to the link form</a>

    <div class="sk-wrap sk-new-wrap">
        <header class="sk-head sk-new-head">
            <a href="/" class="sk-brand" aria-label="href.nz home">href<span>.nz</span></a>
            <nav aria-label="Account">
                @auth
                    <a href="{{ \App\Support\DomainUrls::publicDashboard('/') }}" class="sk-login">open dashboard <span aria-hidden="true">→</span></a>
                @else
                    <a href="{{ url('/login') }}" class="sk-login">members log in <span aria-hidden="true">→</span></a>
                @endauth
            </nav>
        </header>

        <main class="sk-new-main">
            <section class="sk-new-intro" aria-labelledby="new-title">
                <h1 class="sk-title sk-new-title" id="new-title">make something
                    <span class="sk-u">short.</span>
                </h1>
                <p class="sk-sub">Drop in a destination and we’ll make a tidy href.nz link.</p>
            </section>

            @guest
                <p class="sk-new-login" role="note">
                    Member? <a href="{{ url('/login') }}">Log in</a> for custom slugs, shorter links &amp; click stats —
                    or continue as a guest below.
                </p>
            @else
                <p class="sk-new-login" role="note">
                    Signed in — links you make here land in your <a href="{{ \App\Support\DomainUrls::publicDashboard('/') }}">dashboard</a> with stats.
                </p>
            @endguest

            <section class="sk-form-zone sk-new-form-zone" id="new-link" aria-label="Create a short link">
                <livewire:public.shorten-form :compact="true" :minimal="true" />
            </section>

            <p class="sk-new-back"><a href="/">← back to the full shortener</a></p>
        </main>

        <footer class="sk-foot">
            href.nz · <a href="https://ternis.link/pages/legal/privacy">privacy</a> ·
            <a href="https://ternis.link/pages/legal/terms">terms</a>
        </footer>
    </div>

    @livewireScripts
</body>
</html>
