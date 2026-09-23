<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>href.nz — long links go in, short links come out</title>
    <meta name="description" content="href.nz — the no-account link shortener. Paste a long link, get an 8-character short link back.">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/landing-public.css') }}">
    @livewireStyles
</head>
<body class="nz-root">
    <main>
        <section class="mm-sheet">
            <div class="mm-letterhead">
                <a href="/" class="mm-brand nz-brand">href<span>.nz</span></a>
                @auth
                    <a href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}" class="mm-login">open dashboard <svg width="36" height="14" viewBox="0 0 36 14" fill="none" aria-hidden="true"><path d="M2 9 C 12 7, 22 6, 29 7 M 29 7 l -8 -4 M 29 7 l -8 5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                @else
                    <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="mm-login">members log in <svg width="36" height="14" viewBox="0 0 36 14" fill="none" aria-hidden="true"><path d="M2 9 C 12 7, 22 6, 29 7 M 29 7 l -8 -4 M 29 7 l -8 5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                @endauth
            </div>

            <p class="mm-memo-line"><strong>memo</strong> № 001 — for everyone with an ugly-long link</p>
            <p class="mm-memo-line"><strong>re</strong> making links pocket-sized, no account needed</p>

            <h1 class="mm-title">long links go in.<br><span class="mm-stamp">short links come out.</span></h1>
            <p class="mm-sub">Drop yours in the box below and walk away with an 8-character href.nz link.</p>
            <p class="mm-nudge" aria-hidden="true">
                <svg width="72" height="56" viewBox="0 0 72 56" fill="none">
                    <path d="M6 6 C 28 10, 52 18, 56 44 M 56 44 l -11 -4 M 56 44 l 2 -11" stroke="#717171" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>psst — right here!</span>
            </p>

            <livewire:public.shorten-form />

            <p class="mm-signoff">— by the makers of <strong>href.re</strong> &amp; <strong>static.re</strong></p>
        </section>

        <div class="mm-div" aria-hidden="true">
            <svg width="132" height="12" viewBox="0 0 132 12" fill="none">
                <path d="M2 8 Q 13 2, 24 8 T 46 8 T 68 8 T 90 8 T 112 8 T 134 8" stroke="#cfcfcf" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>

        <section class="mm-notes">
            <div class="mm-note">
                <h2>margin note ①</h2>
                <p>Guests get auto-made codes — picking your own is a members' perk.</p>
                <p><a href="{{ \App\Support\DomainUrls::dashboard('/login') }}">Log in</a> for custom slugs, shorter links &amp; click stats.</p>
            </div>
            <div class="mm-note">
                <h2>margin note ②</h2>
                <p>Fair use: 50 links a day per guest. Nothing of yours is kept but a hashed IP for counting.</p>
                <p>Official business? That's next door on href.re.</p>
            </div>
        </section>

        <footer class="mm-foot">
            made with ♥ by ternis.link from <a href="https://ternis.dev">ternis.dev</a> · hosted on <a href="https://ternis.net">ternis.net</a>
        </footer>
    </main>

    @livewireScripts
</body>
</html>
