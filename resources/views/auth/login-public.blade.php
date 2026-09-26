<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>members log in — href.nz</title>
    <meta name="description" content="href.nz member login — one button, no password. Sign in with Ternis Auth SSO for custom slugs, shorter links and click stats.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://href.nz/login">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/landing-public.css'])
</head>
<body class="sk-root">
    <a class="sk-skip" href="#login">Skip to the login button</a>

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

        <section class="sk-hero">
            <svg class="dk" style="top: 6px; left: 8px; transform: rotate(-12deg);" width="40" height="40" viewBox="0 0 32 32" fill="none" aria-hidden="true"><circle cx="16" cy="16" r="12" stroke="currentColor" stroke-width="2"/><path d="m10.5 16 3.8 3.8 7.2-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <svg class="dk" style="top: 0; right: 30px; transform: rotate(12deg);" width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2.5c.7 4.8 2.1 6.9 7.5 7.5-5.4.6-6.8 2.7-7.5 7.5-.7-4.8-2.1-6.9-7.5-7.5 5.4-.6 6.8-2.7 7.5-7.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>

            <span class="sk-kicker">members only</span>

            <h1 class="sk-title" id="page-title">members
                <span class="sk-u">log in
                    <svg viewBox="0 0 150 14" fill="none" preserveAspectRatio="none" aria-hidden="true"><path d="M4 9.5 C 35 5.5, 60 11.5, 90 8 S 130 8, 146 6.5" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/></svg>
                </span> here.
            </h1>

            <p class="sk-sub">One button, no password — sign-in runs through Ternis Auth SSO.
                Members pick custom slugs, shorter links &amp; click stats.</p>

            <ol class="sk-steps" aria-label="How it works">
                <li><span class="n" aria-hidden="true">1</span> Hit the button</li>
                <svg class="sk-step-arrow" width="26" height="16" viewBox="0 0 26 16" fill="none" aria-hidden="true"><path d="M2 10 C 9 8.5, 15 8.5, 21 9.5 M21 9.5 L15.5 6 M21 9.5 L15.5 13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <li><span class="n" aria-hidden="true">2</span> Sign in at Ternis Auth</li>
                <svg class="sk-step-arrow" width="26" height="16" viewBox="0 0 26 16" fill="none" aria-hidden="true"><path d="M2 10 C 9 8.5, 15 8.5, 21 9.5 M21 9.5 L15.5 6 M21 9.5 L15.5 13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <li><span class="n" aria-hidden="true">3</span> Land in your dashboard
                    <svg width="15" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4.5 12.5 10 18 19.5 6.5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </li>
            </ol>
        </section>

        <section class="sk-form-zone" id="login" aria-label="Member login">
            <div class="sk-card tilt-l">
                <span class="sk-tape" aria-hidden="true"></span>
                <h2 class="sk-form-title">one button, no password</h2>
                <p class="sk-form-sub">The actual sign-in happens on the dashboard host — your login
                    session lives there, browsers won't share cookies across domains.</p>

                @if (session('error'))
                    <div class="sk-oops" role="alert">
                        <p class="sk-oops-msg">{{ session('error') }}</p>
                    </div>
                @endif

                <p style="margin: 1.1rem 0 0;">
                    <a class="sk-btn" style="text-decoration: none; display: inline-block;" href="{{ \App\Support\DomainUrls::dashboard('/login') }}">
                        Log in with Ternis Auth
                    </a>
                </p>
                <p class="sk-hint">Takes you to {{ config('domains.dashboard_host', 'dash.ternis.link') }} — you'll be back before your coffee cools.</p>
            </div>
        </section>

        <aside class="sk-notes" aria-label="Good to know" style="margin-top: 2rem;">
            <div class="sk-note">
                <span class="sk-pin" aria-hidden="true"></span>
                <svg class="dk" style="top: 10px; right: 12px;" width="22" height="28" viewBox="0 0 24 30" fill="none" aria-hidden="true"><path d="M6.5 13.5 h11 v11 h-11 Z M9 13.5 V10 a3 3 0 0 1 6 0 v3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="18.5" r="1.2" fill="currentColor"/></svg>
                <h2>why the hop?</h2>
                <p>Short links work everywhere, but login sessions don't travel between domains. That's why the button hands you over to the dashboard — same account, safer session.</p>
            </div>
            <div class="sk-note">
                <span class="sk-pin" aria-hidden="true"></span>
                <svg class="dk" style="top: 10px; right: 12px;" width="24" height="30" viewBox="0 0 24 30" fill="none" aria-hidden="true"><path d="M12 3 C 8 3, 5 6.2, 5 10 c0 2.5 1.3 4.2 2.7 5.3 .7.6 1 1.1 1 2.2 h6.6 c0-1.1.3-1.6 1-2.2 C 17.7 14.2, 19 12.5, 19 10 C 19 6.2, 16 3, 12 3 Z M9.5 21.5 h5 M10.5 25 h3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <h2>no account yet?</h2>
                <p>Accounts come from Ternis Auth — if you're family, a partner, or a customer, you already have what you need. Guests can just <a href="/">shorten links</a>, no account required.</p>
            </div>
        </aside>

        <footer class="sk-foot">
            sketched by ternis.link from <a href="https://ternis.dev">ternis.dev</a> · hosted on <a href="https://ternis.net">ternis.net</a>
            · <a href="https://ternis.link/pages/legal/privacy">privacy</a> · <a href="https://ternis.link/pages/legal/terms">terms</a> · <a href="https://ternis.dev/en/legal/imprint">imprint</a>
        </footer>
    </div>
</body>
</html>
