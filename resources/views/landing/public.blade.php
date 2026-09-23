<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>href.nz — tiny links, zero fuss</title>
    <meta name="description" content="href.nz — shorten links instantly, no account needed. Guests get auto-generated 8-character links.">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/landing-public.css') }}">
    @livewireStyles
</head>
<body class="nz-root">
    <header class="nz-nav">
        <a href="/" class="nz-brand">href<span>.nz</span></a>
        <div>
            @auth
                <a href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}" class="btn btn-secondary btn-sm">Dashboard</a>
            @else
                <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="btn btn-secondary btn-sm">Log in</a>
            @endauth
        </div>
    </header>

    <main>
        <section class="nz-hero">
            <h1>tiny links, <span>zero fuss!</span></h1>
            <p>Paste a URL, grab your short <strong>href.nz</strong> link. No account, no fuss — just an 8-character link, ready to share.</p>
        </section>

        <livewire:public.shorten-form />

        <section class="nz-features">
            <div class="nz-feature">
                <h3>✄ snip snip!</h3>
                <p>One field, one click. Your long URL becomes a tiny link.</p>
            </div>
            <div class="nz-feature">
                <h3>☁ no snooping</h3>
                <p>Guest links carry no account — just a hashed IP for abuse limits.</p>
            </div>
            <div class="nz-feature">
                <h3>✎ want more?</h3>
                <p><a href="{{ \App\Support\DomainUrls::dashboard('/login') }}">Log in</a> for custom slugs, shorter links &amp; click stats.</p>
            </div>
        </section>

        <footer class="nz-footer">
            made with ♥ by ternis.link from <a href="https://ternis.dev">ternis.dev</a> · hosted on <a href="https://ternis.net">ternis.net</a> · <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}">dashboard</a>
        </footer>
    </main>

    @livewireScripts
</body>
</html>
