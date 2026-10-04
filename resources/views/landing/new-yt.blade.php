<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>make a short link — href.yt</title>
    <meta name="description" content="Create a short href.yt link. Guests are welcome; members get custom slugs and click stats.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://href.yt/new">
    <meta name="theme-color" content="#0f0f0f">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/landing-public.css', 'resources/css/yt.css', 'resources/js/yt.js'])
    @livewireStyles
    @if (config('services.turnstile.key'))
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
    @endif
</head>
<body class="yt-root yt-new-root">

    <a class="yt-skip" href="#new-link">Skip to the link form</a>

    <div class="yt-new-wrap">
        <header class="yt-new-head">
            <a href="/" class="yt-brand" aria-label="href.yt home">
                <span class="yt-brand-play" aria-hidden="true">
                    <svg viewBox="0 0 16 16" aria-hidden="true"><polygon points="4,2 14,8 4,14"/></svg>
                </span>
                href<span>.yt</span>
            </a>
            <nav aria-label="Account">
                @auth
                    <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="yt-login">
                        <span>open dashboard</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                @else
                    <a href="{{ url('/login') }}" class="yt-login">
                        <span>members log in</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                @endauth
            </nav>
        </header>

        <main class="yt-new-main">
            <div class="yt-new-intro">
                <h1 class="yt-new-title">
                    Make something <span style="color:var(--yt-red)">short.</span>
                </h1>
                <p class="yt-new-sub">Drop in a destination URL and get a clean href.yt link back.</p>
            </div>

            @guest
                <p class="yt-new-notice">
                    Member? <a href="{{ url('/login') }}">Log in</a> for custom slugs, shorter links &amp; click stats —
                    or continue as a guest below.
                </p>
            @else
                <p class="yt-new-notice">
                    Signed in — links you make here land in your <a href="{{ \App\Support\DomainUrls::dashboard('/') }}">dashboard</a> with stats.
                </p>
            @endguest

            <section id="new-link" aria-label="Create a short link">
                <livewire:public.shorten-form :compact="true" :minimal="true" />
            </section>

            <p class="yt-new-back">
                <a href="/">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>back to href.yt</span>
                </a>
            </p>
        </main>

        <footer class="yt-foot" style="padding-top:1.5rem;">
            <p class="yt-foot-links">
                href.yt ·
                <a href="https://ternis.link/pages/legal/privacy">privacy</a> ·
                <a href="https://ternis.link/pages/legal/terms">terms</a>
            </p>
            <p class="yt-foot-disclaimer">
                href.yt is an independent link shortening service and is not affiliated with, endorsed by, authorized by, or in any way officially connected with YouTube, Google LLC, Alphabet Inc., or any of their subsidiaries or affiliates. "YouTube" is a registered trademark of Google LLC.
            </p>
        </footer>
    </div>

    @livewireScripts
</body>
</html>
