<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>href.nz — scribble it short</title>
    <meta name="description" content="href.nz — a tiny notebook for long links. Paste a URL, get an 8-character short link. No account needed.">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/landing-public.css') }}">
    @livewireStyles
</head>
<body class="nz-root">
    <header class="nb-top">
        <a href="/" class="nz-brand">href<span>.nz</span></a>
        @auth
            <a href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}" class="nb-login">→ dashboard</a>
        @else
            <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="nb-login">→ log in</a>
        @endauth
    </header>

    <main>
        <section class="nb-sheet">
            <p class="nb-kicker">a tiny notebook for long links — page 1 of 1</p>
            <h1 class="nb-title">got a long link?<br><span class="nb-circled">scribble it short.</span></h1>
            <p class="nb-sub">Paste it below and get a pocket-sized href.nz link. No account, no fuss.</p>
            <p class="nb-arrow">
                <svg width="46" height="40" viewBox="0 0 46 40" fill="none" aria-hidden="true">
                    <path d="M4 4 C 16 12, 30 14, 36 30 M 36 30 l -9 -3 M 36 30 l 1 -9" stroke="#6f6f6f" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
                start scribbling here…
            </p>

            <livewire:public.shorten-form />
        </section>

        <section class="nb-how">
            <h2>how it works</h2>
            <ol class="nb-steps">
                <li>
                    <span class="nb-num">1</span>
                    <p>paste the monster link <small>anything starting with https:// will do</small></p>
                </li>
                <li>
                    <span class="nb-num">2</span>
                    <p>hit shorten <small>we jot down an 8-character code for it</small></p>
                </li>
                <li>
                    <span class="nb-num">3</span>
                    <p>share it everywhere <small>short, sweet, and yours to keep</small></p>
                </li>
            </ol>
        </section>

        <section class="nb-rules">
            <h2>house rules</h2>
            <ul>
                <li>guests get auto-made 8-character links <small>— no picking your own, sorry!</small></li>
                <li>want custom + shorter slugs &amp; stats? <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}">log in</a></li>
                <li>be nice: 50 links a day per guest <small>— plenty for mortal needs</small></li>
            </ul>
        </section>

        <footer class="nb-foot">
            made with ♥ by ternis.link from <a href="https://ternis.dev">ternis.dev</a> · hosted on <a href="https://ternis.net">ternis.net</a>
            <span class="ps">p.s. official business links live next door on href.re →</span>
        </footer>
    </main>

    @livewireScripts
</body>
</html>
