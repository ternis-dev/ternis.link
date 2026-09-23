<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>href.nz — Fast public link shortener</title>
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
                <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="btn btn-primary btn-sm nz-btn-guest">Log in</a>
            @endauth
        </div>
    </header>

    <main>
        <section class="nz-hero">
            <h1>Short links, <span>zero friction.</span></h1>
            <p>Paste a URL, get an <strong>href.nz</strong> link instantly. No account needed — guests receive an auto-generated 8-character link.</p>
            <div class="nz-cta-row">
                @auth
                    <a href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">Go to Dashboard</a>
                @else
                    <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="btn btn-primary nz-btn-guest" style="padding: 0.75rem 1.5rem;">Log in for custom slugs</a>
                @endauth
            </div>
        </section>

        <livewire:public.shorten-form />

        <section class="nz-features">
            <div class="nz-feature">
                <h3>⚡ Instant</h3>
                <p>No signup, no waiting. One field, one click, one short link.</p>
            </div>
            <div class="nz-feature">
                <h3>🔒 Private by design</h3>
                <p>Guest links carry no account — only a hashed IP for abuse limits, never raw IPs.</p>
            </div>
            <div class="nz-feature">
                <h3>📊 Insights for members</h3>
                <p>Log in for custom slugs, 6-character links, click analytics and API keys.</p>
            </div>
        </section>

        <footer class="nz-footer">
            href.nz — public shortener by ternis.link · <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}">ternis.link dashboard</a>
        </footer>
    </main>

    @livewireScripts
</body>
</html>
