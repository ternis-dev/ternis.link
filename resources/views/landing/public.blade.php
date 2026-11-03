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
                <a href="#features">features</a>
                <a href="{{ url('/scan') }}">link scanner</a>
                <a href="#questions">questions?</a>
                @auth
                    <a href="{{ \App\Support\DomainUrls::publicDashboard('/') }}" class="home-login">my dashboard <span aria-hidden="true">↗</span></a>
                @else
                    <a href="{{ url('/login') }}" class="home-login">log in <span aria-hidden="true">↗</span></a>
                @endauth
            </nav>
        </header>

        <main id="main-content">
            {{-- Hero Section --}}
            <div class="home-workspace">
                <section class="home-intro" aria-labelledby="page-title">
                    <p class="home-eyebrow">
                        {{-- Doodle 1: Sparkle / Star --}}
                        <svg class="sk-doodle-sparkle" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round" aria-hidden="true" style="display:inline-block; vertical-align:middle; margin-right:4px;">
                            <path d="M12 2.5c.7 4.8 2.1 6.9 7.5 7.5-5.4.6-6.8 2.7-7.5 7.5-.7-4.8-2.1-6.9-7.5-7.5 5.4-.6 6.8-2.7 7.5-7.5Z"/>
                        </svg>
                        small links. big possibilities.
                    </p>

                    <h1 id="page-title">Long link?<br>Make it <span class="home-underline">a little <br>shorter.</span></h1>

                    <p class="home-lede">For the good stuff you want to share.<br>Turn a sprawling URL into a neat little link — free, fast, with no account needed.</p>

                    <div class="home-example" aria-label="Example of a shortened link">
                        <span class="home-example-label">less of this</span>
                        <span class="home-long-url">example.com/a/very/long/link?and=more</span>

                        {{-- Doodle 2: Flow Arrow --}}
                        <svg width="54" height="40" viewBox="0 0 54 40" fill="none" aria-hidden="true">
                            <path d="M4 4C8 29 23 31 46 24M46 24L35 19M46 24L39 35" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>

                        <span class="home-example-label">more of this</span>
                        <strong>href.nz/aB3k9xQ2</strong>
                    </div>

                    <p class="home-margin-note">
                        a little less link, a lot less fuss.
                        {{-- Doodle 3: Margin Heart --}}
                        <svg class="sk-doodle-heart" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:inline-block; vertical-align:middle; margin-left:3px;">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </p>
                </section>

                <section class="sk-form-zone home-shortener" id="shorten" aria-label="Shorten a link">
                    <p class="home-card-caption">your next short link starts here <span aria-hidden="true">↓</span></p>
                    <livewire:public.shorten-form />
                    <div class="home-under-form">
                        <span class="home-under-pill">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            100% Free
                        </span>
                        <span class="home-under-pill">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            No sign-up
                        </span>
                        <span class="home-under-pill">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            Instant QR
                        </span>
                        <span class="home-under-pill">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            AI-Screened
                        </span>
                    </div>
                </section>
            </div>

            {{-- How It Works --}}
            <section class="home-how" id="how-it-works" aria-labelledby="how-title">
                <div class="home-section-heading">
                    <p class="home-eyebrow">nothing to figure out</p>
                    <h2 id="how-title">Three steps. One tiny link.</h2>
                    <p>Paste, trim, and share in seconds — zero sign-up hoops.</p>
                </div>

                <ol class="home-process">
                    <li class="home-process-card">
                        <div class="home-process-head">
                            <span class="home-number">01</span>
                            {{-- Doodle 4: Link / URL Snippet --}}
                            <svg class="home-step-icon" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="6" y="10" width="36" height="28" rx="4" stroke-dasharray="2 3"/>
                                <path d="M14 20h20M14 28h12"/>
                                <circle cx="34" cy="28" r="2" fill="currentColor"/>
                            </svg>
                        </div>
                        <div>
                            <h3>Drop in your URL</h3>
                            <p>A favourite article, portfolio piece, YouTube video, or that impossibly long product link.</p>
                        </div>
                    </li>

                    <li class="home-process-card">
                        <div class="home-process-head">
                            <span class="home-number">02</span>
                            {{-- Doodle 5: Scissors Trimming Link --}}
                            <svg class="home-step-icon" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="14" cy="16" r="6"/>
                                <circle cx="14" cy="32" r="6"/>
                                <path d="M19 19L36 34M19 29L36 14M38 24h6" stroke-dasharray="3 3"/>
                            </svg>
                        </div>
                        <div>
                            <h3>Give it a trim</h3>
                            <p>Hit Shorten. We instantly trim away tracking bloat and issue a neat 8-character code.</p>
                        </div>
                    </li>

                    <li class="home-process-card">
                        <div class="home-process-head">
                            <span class="home-number">03</span>
                            {{-- Doodle 6: Paper Plane & Share --}}
                            <svg class="home-step-icon" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M6 24L42 8L28 42L22 28L6 24Z"/>
                                <path d="M22 28L42 8" stroke-dasharray="2 2"/>
                            </svg>
                        </div>
                        <div>
                            <h3>Send it somewhere</h3>
                            <p>Copy it into a message, add it to a post, or grab its crisp vector QR code ready for cameras.</p>
                        </div>
                    </li>
                </ol>
            </section>

            {{-- Features Grid --}}
            <section class="home-good" id="features" aria-labelledby="good-title">
                <div class="home-section-heading">
                    <p class="home-eyebrow">a few things worth knowing</p>
                    <h2 id="good-title">Small link. Thoughtfully made.</h2>
                </div>

                <div class="home-note-grid">
                    <article class="home-note">
                        {{-- Doodle 7: Unlocked / Free Tag --}}
                        <svg class="home-note-doodle" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M8 18h24v16H8z"/>
                            <path d="M14 18v-6a6 6 0 0 1 12 0"/>
                            <circle cx="20" cy="26" r="2" fill="currentColor"/>
                        </svg>
                        <div>
                            <h3>No account. Really.</h3>
                            <p>Just paste and shorten. Guests can create up to 50 links a day with automatic 8-character codes.</p>
                        </div>
                        <a href="#shorten">make your first link ↗</a>
                    </article>

                    <article class="home-note">
                        {{-- Doodle 8: Vector QR Code --}}
                        <svg class="home-note-doodle" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="6" y="6" width="10" height="10" rx="2"/>
                            <rect x="24" y="6" width="10" height="10" rx="2"/>
                            <rect x="6" y="24" width="10" height="10" rx="2"/>
                            <path d="M24 24h4v4h-4zM32 24v8M24 32h4"/>
                        </svg>
                        <div>
                            <h3>Crisp Vector QR.</h3>
                            <p>Every link includes instant SVG and PNG QR codes. Scalable for presentations, stickers, and print flyers.</p>
                        </div>
                        <a href="https://qr.href.nz">explore the QR studio ↗</a>
                    </article>

                    <article class="home-note">
                        {{-- Doodle 9: Security Shield --}}
                        <svg class="home-note-doodle" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 4s12 3 12 12c0 10-12 18-12 20C20 34 8 26 8 16c0-9 12-12 12-12Z"/>
                            <path d="M15 19l4 4 7-7"/>
                        </svg>
                        <div>
                            <h3>A little peace of mind.</h3>
                            <p>AI screens destinations for phishing, spam, and malicious content. IPs are stored only as hashes.</p>
                        </div>
                        <a href="https://ternis.link/pages/legal/privacy">the privacy details ↗</a>
                    </article>

                    <article class="home-note">
                        {{-- Doodle 10: Member Perks & Insights --}}
                        <svg class="home-note-doodle" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="20" cy="20" r="14"/>
                            <path d="M12 25l5-6 5 3 6-8"/>
                            <path d="M25 14h3v3"/>
                        </svg>
                        <div>
                            <h3>Make it your own.</h3>
                            <p>Sign in for custom slugs, short memorable paths, bio pages, and cookie-free click statistics.</p>
                        </div>
                        <a href="{{ url('/login') }}">meet member perks ↗</a>
                    </article>
                </div>
            </section>

            {{-- URL Threat Inspector Callout Banner --}}
            <section class="home-inspector-banner" aria-labelledby="inspector-heading">
                <div class="home-inspector-left">
                    {{-- Doodle 11: Magnifying Glass Inspector --}}
                    <svg class="home-inspector-icon" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="20" cy="20" r="13"/>
                        <path d="M30 30L42 42M16 20h8M20 16v8"/>
                    </svg>
                    <div class="home-inspector-text">
                        <h3 id="inspector-heading">Free Link Threat Inspector</h3>
                        <p>Unsure about a shortened link someone sent you? Paste any web address into our free URL Inspector to check for phishing traps, suspicious redirects, and malware reputation without opening the site.</p>
                    </div>
                </div>
                <a href="{{ url('/scan') }}" class="home-inspector-btn">Inspect a Link →</a>
            </section>

            {{-- FAQ Section --}}
            <section class="home-faq" id="questions" aria-labelledby="faq-title">
                <div class="home-section-heading">
                    <p class="home-eyebrow">the small print, minus the squint</p>
                    <h2 id="faq-title">A few good questions.</h2>
                    <p>Still curious? Here’s the useful stuff.</p>
                </div>
                <div class="home-questions">
                    <details>
                        <summary>Is it really free?</summary>
                        <p>Yes. Guests can shorten up to 50 links per day without an account. Guest links use an automatically generated 8-character code.</p>
                    </details>
                    <details>
                        <summary>Can I choose my own short link?</summary>
                        <p>Custom slugs and branded paths are available when you <a href="{{ url('/login') }}">sign in</a>. Guests get automatically generated codes. Shorter codes depend on your account plan.</p>
                    </details>
                    <details>
                        <summary>Can I share a link as a QR code?</summary>
                        <p>Yes. After shortening, choose the QR option beside your new link. You can download a high-res PNG or vector SVG to use online or in print. For advanced QR styles, check out <a href="https://qr.href.nz">qr.href.nz</a>.</p>
                    </details>
                    <details>
                        <summary>How does href.nz protect against malicious links?</summary>
                        <p>We screen destination URLs with automated threat detection and AI scoring to stop phishing, malware, and deceptive sites before they can harm visitors. If a link triggers security rules, it is quarantined immediately.</p>
                    </details>
                    <details>
                        <summary>What if I discover an abusive or harmful link?</summary>
                        <p>You can report abusive short links anytime through our <a href="https://ternis.link/pages/abuse">Abuse Report Form</a> or by emailing <a href="mailto:abuse@ternis.dev">abuse@ternis.dev</a>. Our Trust & Safety team investigates all reports promptly.</p>
                    </details>
                    <details>
                        <summary>Where can I find links I’ve made?</summary>
                        <p>Recent links appear in the shortener tray on this device. This history stays in your browser, so clearing browser data removes it. Sign in to sync your links and access click statistics from any device.</p>
                    </details>
                </div>
            </section>

            {{-- Business Next Door --}}
            <aside class="home-business">
                <div>
                    <span class="home-eyebrow">something a little more official?</span>
                    <h2>Business links live next door.</h2>
                    <p>Discover verified business links, team management, and custom domains on href.re.</p>
                </div>
                <a href="https://href.re">take a look <span aria-hidden="true">↗</span></a>
            </aside>
        </main>

        <footer class="home-footer">
            <div>
                <a href="/" class="sk-brand">href<span>.nz</span></a>
                <p>A little link goes a long way.</p>
                <p>© {{ date('Y') }} · made by <a href="https://ternis.dev">ternis.dev</a> · hosted on <a href="https://ternis.net">ternis.net</a></p>
            </div>
            <nav aria-label="Legal & Safety">
                <a href="https://ternis.link/pages/legal/privacy">privacy</a>
                <a href="https://ternis.link/pages/legal/terms">terms</a>
                <a href="https://ternis.link/pages/legal/cookies">cookies</a>
                <a href="https://ternis.link/pages/abuse">report abuse</a>
                <a href="{{ url('/scan') }}">inspect link</a>
                <a href="{{ \App\Support\DomainUrls::impressum('href.nz', 'en') }}">imprint ↗</a>
            </nav>
        </footer>
    </div>
    @livewireScripts
</body>
</html>
