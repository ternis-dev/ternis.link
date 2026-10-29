<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>href.nz — long links go in, short links come out</title>
    <meta name="description" content="href.nz — the no-account link shortener. Paste a long link, get an 8-character short link back. Free, fast, no sign-up.">
    <meta name="theme-color" content="#f6f3ec">
    <link rel="canonical" href="https://href.nz/">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="href.nz">
    <meta property="og:title" content="href.nz — long links go in, short links come out">
    <meta property="og:description" content="Paste a long link, get an 8-character href.nz link back. No account needed.">
    <meta property="og:url" content="https://href.nz/">
    <meta name="twitter:card" content="summary">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/landing-public.css'])
    @livewireStyles
    @if (config('services.turnstile.key'))
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
    @endif
</head>
<body class="sk-root sk-home">
    <a class="sk-skip" href="#shorten">Skip to the shortener</a>
    <div class="sk-wrap">
        <header class="sk-head">
            <a href="/" class="sk-brand" aria-label="href.nz home">href<span>.nz</span></a>
            <nav class="home-nav" aria-label="Main navigation">
                <a href="#how-it-works">how it works</a>
                <a href="#questions">questions?</a>
                @auth
                    <a href="{{ \App\Support\DomainUrls::publicDashboard('/') }}" class="home-login">my dashboard <span aria-hidden="true">↗</span></a>
                @else
                    <a href="{{ url('/login') }}" class="home-login">log in <span aria-hidden="true">↗</span></a>
                @endauth
            </nav>
        </header>
        <main id="main-content">
            <div class="home-workspace">
                <section class="home-intro" aria-labelledby="page-title">
                    <p class="home-eyebrow"><span aria-hidden="true">✳</span> small links. big possibilities.</p>
                    <h1 id="page-title">Long link?<br>Make it <span class="home-underline">a little <br>shorter.</span></h1>
                    <p class="home-lede">For the good stuff you want to share.<br>Turn a sprawling URL into a neat little link — free, with no account needed.</p>
                    <div class="home-example" aria-label="Example of a shortened link">
                        <span class="home-example-label">less of this</span>
                        <span class="home-long-url">example.com/a/very/long/link?and=more</span>
                        <svg width="54" height="40" viewBox="0 0 54 40" fill="none" aria-hidden="true"><path d="M4 4C8 29 23 31 46 24M46 24L35 19M46 24L39 35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="home-example-label">more of this</span>
                        <strong>href.nz/aB3k9xQ2</strong>
                    </div>
                    <p class="home-margin-note">a little less link, a lot less fuss. <span aria-hidden="true">♡</span></p>
                </section>
                <section class="sk-form-zone home-shortener" id="shorten" aria-label="Shorten a link">
                    <p class="home-card-caption">your next short link starts here <span aria-hidden="true">↓</span></p>
                    <livewire:public.shorten-form />
                    <p class="home-under-form"><span aria-hidden="true">✓</span> Free to use <span aria-hidden="true">·</span> No sign-up <span aria-hidden="true">·</span> Ready to share</p>
                </section>
            </div>

            <section class="home-how" id="how-it-works" aria-labelledby="how-title">
                <div class="home-section-heading"><p class="home-eyebrow">nothing to figure out</p><h2 id="how-title">Three steps. One tiny link.</h2></div>
                <ol class="home-process">
                    <li><span class="home-number">01</span><div><h3>Drop in your URL</h3><p>A favourite article, your next project, that impossibly long product link.</p></div></li>
                    <li><span class="home-number">02</span><div><h3>Give it a trim</h3><p>Hit Shorten. We’ll make an auto-generated, 8-character link for you.</p></div></li>
                    <li><span class="home-number">03</span><div><h3>Send it somewhere</h3><p>Copy it into a message, add it to a post, or grab its QR code.</p></div></li>
                </ol>
            </section>

            <section class="home-good" aria-labelledby="good-title">
                <div class="home-section-heading"><p class="home-eyebrow">a few things worth knowing</p><h2 id="good-title">Small link. Thoughtfully made.</h2></div>
                <div class="home-note-grid">
                    <article class="home-note"><span class="home-note-icon" aria-hidden="true">♡</span><h3>No account. Really.</h3><p>Just paste and shorten. Guests can create up to 50 links a day, with automatic codes.</p><a href="#shorten">make your first link ↗</a></article>
                    <article class="home-note"><span class="home-note-icon" aria-hidden="true">⌁</span><h3>A little peace of mind.</h3><p>TypeSafe AI screens destinations for phishing, spam and malicious content. IP addresses are stored as hashes.</p><a href="https://ternis.link/pages/legal/privacy">the privacy details ↗</a></article>
                    <article class="home-note"><span class="home-note-icon" aria-hidden="true">✳</span><h3>Make it your own.</h3><p>Sign in for custom slugs, shorter links and click stats. Your links, with a little more possibility.</p><a href="{{ url('/login') }}">meet the member perks ↗</a></article>
                </div>
            </section>

            <section class="home-faq" id="questions" aria-labelledby="faq-title">
                <div class="home-section-heading"><p class="home-eyebrow">the small print, minus the squint</p><h2 id="faq-title">A few good questions.</h2><p>Still curious? Here’s the useful stuff.</p></div>
                <div class="home-questions">
                    <details><summary>Is it really free?</summary><p>Yes. Guests can shorten up to 50 links per day without an account. Guest links use an automatically generated 8-character code.</p></details>
                    <details><summary>Can I choose my own short link?</summary><p>Custom slugs are available when you <a href="{{ url('/login') }}">sign in</a>. Guests get automatically generated codes. Shorter codes depend on your account’s plan.</p></details>
                    <details><summary>Where can I find links I’ve made?</summary><p>Recent links appear in the shortener on this device. This history stays in your browser, so clearing browser data removes it. Sign in to manage links from your dashboard.</p></details>
                    <details><summary>Can I share a link as a QR code?</summary><p>Yes. After shortening, choose the QR option beside your new link. You can download a PNG or SVG to use online or in print.</p></details>
                </div>
            </section>
            <aside class="home-business"><div><span class="home-eyebrow">something a little more official?</span><h2>Business links live next door.</h2><p>Discover verified business links on href.re.</p></div><a href="https://href.re">take a look <span aria-hidden="true">↗</span></a></aside>
        </main>
        <footer class="home-footer"><div><a href="/" class="sk-brand">href<span>.nz</span></a><p>A little link goes a long way.</p><p>© {{ date('Y') }} · made by <a href="https://ternis.dev">ternis.dev</a> · hosted on <a href="https://ternis.net">ternis.net</a></p></div><nav aria-label="Legal"><a href="https://ternis.link/pages/legal/privacy">privacy</a><a href="https://ternis.link/pages/legal/terms">terms</a><a href="{{ \App\Support\DomainUrls::impressum('href.nz', 'en') }}">imprint ↗</a></nav></footer>
    </div>
    @livewireScripts
</body>
</html>
