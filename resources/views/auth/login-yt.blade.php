<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>members log in — href.yt</title>
    <meta name="description" content="href.yt member login — one button, no password. Sign in with Ternis Auth SSO for custom slugs, shorter links and click stats.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://href.yt/login">
    <meta name="theme-color" content="#0f0f0f">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/landing-public.css', 'resources/css/yt.css'])
</head>
<body class="yt-root">
    <a class="yt-skip" href="#login">Skip to the login button</a>

    <header class="yt-head">
        <a href="/" class="yt-brand" aria-label="href.yt home">
            <span class="yt-brand-play" aria-hidden="true">
                <svg viewBox="0 0 16 16" aria-hidden="true"><polygon points="4,2 14,8 4,14"/></svg>
            </span>
            href<span>.yt</span>
        </a>
        <nav class="yt-nav" aria-label="Back">
            <a href="/" class="yt-login yt-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>back to shortener</span>
            </a>
        </nav>
    </header>

    <div class="yt-wrap">
        <section class="yt-hero" style="padding-bottom: 2rem;">
            <div class="yt-badge" aria-hidden="true">
                <svg width="10" height="10" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><polygon points="3,2 14,8 3,14"/></svg>
                Members only
            </div>
            <h1 class="yt-title" id="page-title">
                Members<br>
                <span class="yt-title-accent">log in</span> here.
            </h1>
            <p class="yt-sub">
                One button, no password — authentication runs safely through Ternis Auth SSO.
                Signed-in creators get custom slugs, shorter links, and click analytics.
            </p>
        </section>

        <section class="yt-form-zone" id="login" aria-label="Member login" style="max-width: 540px; margin: 0 auto 3rem;">
            <div class="yt-login-card">
                <div class="yt-login-card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
                <h2 class="yt-login-card-title">One button, no password</h2>
                <p class="yt-login-card-sub">
                    Sign-in takes place on your central dashboard host. Your authenticated session lives securely there without cross-domain cookie leakage.
                </p>

                @if (session('error'))
                    <div class="yt-oops" role="alert">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                <div style="margin: 1.5rem 0 1rem; text-align: center;">
                    <a class="yt-play-cta" href="{{ \App\Support\DomainUrls::dashboard('/login') }}" style="width: 100%; justify-content: center;">
                        <span>Log in with Ternis Auth</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </div>
                <p class="yt-login-hint">
                    Hands you over to <code>{{ config('domains.dashboard_host', 'dash.ternis.link') }}</code> — verified in seconds.
                </p>
            </div>
        </section>

        <section class="yt-notes" aria-label="Good to know" style="margin-bottom: 3.5rem;">
            <div class="yt-note">
                <h2 class="yt-note-title">Why the redirect?</h2>
                <p>Short links resolve everywhere, but session cookies are strictly scoped for security. The button transfers you to the dashboard host to keep your session authenticated safely.</p>
            </div>
            <div class="yt-note">
                <h2 class="yt-note-title">No account yet?</h2>
                <p>Ternis Auth accounts are provided to partners, clients, and family members. If you just need a quick link, you can always <a href="/">shorten as a guest</a> with zero sign-up.</p>
            </div>
        </section>

        <footer class="yt-foot">
            <p class="yt-foot-links">
                href.yt &copy; {{ date('Y') }} sketched by <a href="https://ternis.dev">ternis.dev</a>
                <span class="yt-foot-sep" aria-hidden="true">·</span>
                <a href="https://ternis.link/pages/legal/privacy">privacy</a>
                <span class="yt-foot-sep" aria-hidden="true">·</span>
                <a href="https://ternis.link/pages/legal/terms">terms</a>
                <span class="yt-foot-sep" aria-hidden="true">·</span>
                <a href="{{ \App\Support\DomainUrls::impressum('href.yt', 'en') }}">imprint</a>
            </p>
            <p class="yt-foot-disclaimer">
                href.yt is an independent link shortening service and is not affiliated with, endorsed by, authorized by, or in any way officially connected with YouTube, Google LLC, Alphabet Inc., or any of their subsidiaries or affiliates. "YouTube" is a registered trademark of Google LLC.
            </p>
        </footer>
    </div>
</body>
</html>
