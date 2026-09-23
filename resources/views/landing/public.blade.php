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
                    <a href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}" class="mm-login">open dashboard →</a>
                @else
                    <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="mm-login">members log in →</a>
                @endauth
            </div>

            <p class="mm-memo-line"><strong>memo</strong> № 001 — for everyone with an ugly-long link</p>
            <p class="mm-memo-line"><strong>re</strong> making links pocket-sized, no account needed</p>

            <h1 class="mm-title">long links go in.<br><span class="mm-stamp">short links come out.</span></h1>
            <p class="mm-sub">Drop yours in the box below and walk away with an 8-character href.nz link.</p>

            <livewire:public.shorten-form />

            <p class="mm-signoff">— by the makers of <strong>href.re</strong> &amp; <strong>static.re</strong></p>
        </section>

        <div class="mm-div" aria-hidden="true">⁂</div>

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
