<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>href.re — Official business links</title>
    <meta name="description" content="href.re — reserved for official ternis business links. Verified, trusted, analytics-backed.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/landing-business.css') }}">
</head>
<body class="re-root">
    <header class="re-nav">
        <a href="/" class="re-brand">href<span>.re</span></a>
        <span class="re-badge">Official · Business only</span>
    </header>

    <main>
        <section class="re-hero">
            <h1>Official links, <span>.\re</span>cognizable.</h1>
            <p><strong>href.re</strong> is reserved for official ternis business links. No public shortening here — every redirect is provisioned and audited by the ternis team.</p>
            <div>
                @auth
                    <a href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}" class="btn btn-primary" style="padding: 0.75rem 1.5rem; background: var(--re-gold); border-color: var(--re-gold); color: #1a1206;">Go to Dashboard</a>
                @else
                    <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="btn btn-primary" style="padding: 0.75rem 1.5rem; background: var(--re-gold); border-color: var(--re-gold); color: #1a1206;">Sign in with Ternis Auth</a>
                @endauth
            </div>
            <div class="re-trust">
                <span>✔ Verified sender</span>
                <span>✔ Click analytics</span>
                <span>✔ Abuse-monitored</span>
            </div>
        </section>

        <section class="re-panels">
            <div class="re-panel">
                <h3>🏢 Business only</h3>
                <p>Public guest shortening is disabled on this domain. Need a public link? Use <strong>href.nz</strong>.</p>
            </div>
            <div class="re-panel">
                <h3>🛡 Trusted by default</h3>
                <p>Recipients can trust href.re redirects — they are issued internally and access-controlled.</p>
            </div>
            <div class="re-panel">
                <h3>📈 Measured</h3>
                <p>Every business redirect logs referrer, client and timestamp asynchronously for insights.</p>
            </div>
        </section>

        <footer class="re-footer">
            href.re — official business shortener by ternis.link · public links: <strong>href.nz</strong>
        </footer>
    </main>
</body>
</html>
