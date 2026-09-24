<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>href.nz — long links go in, short links come out</title>
    <meta name="description" content="href.nz — the no-account link shortener. Paste a long link, get an 8-character short link back. Free, fast, no sign-up.">
    <meta name="theme-color" content="#fdfdfb">
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
</head>
<body class="nz-root">
    <a class="mm-skip" href="#shorten">Skip to the shortener</a>

    <main class="mm-wrapper">
        <article class="mm-sheet" aria-labelledby="page-title">
            <span class="tape tape-tl" aria-hidden="true"></span>
            <span class="tape tape-tr" aria-hidden="true"></span>

            {{-- 1 · paperclip holding the memo --}}
            <svg class="dz dz-soft dz-hide-sm" style="top: 54px; right: 26px; transform: rotate(9deg);" width="30" height="62" viewBox="0 0 32 64" fill="none" aria-hidden="true"><path d="M22 56 L22 26 a7.5 7.5 0 0 0-15 0 L7 47 a11.5 11.5 0 0 0 23 0 L30 19" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>

            {{-- 2 · coffee ring, someone was here before you --}}
            <svg class="dz dz-faint dz-hide-sm" style="bottom: 118px; left: -34px; transform: rotate(-12deg);" width="86" height="80" viewBox="0 0 86 80" fill="none" aria-hidden="true"><path d="M43 5 C 22 5, 5 18, 6 39 C 7 60, 25 74, 44 73 C 63 72, 80 59, 79 38 C 78 19, 62 4, 43 5" stroke="currentColor" stroke-width="2.5"/><path d="M43 12 C 27 12, 13 22, 14 39 C 15 55, 29 66, 44 65 C 59 64, 72 54, 71 38 C 70 23, 58 11, 43 12" stroke="currentColor" stroke-width="1.5" opacity="0.6"/></svg>

            <header class="mm-letterhead">
                <a href="/" class="mm-brand nz-brand" aria-label="href.nz home">href<span>.nz</span></a>
                <nav aria-label="Account">
                    @auth
                        <a href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}" class="mm-login">
                            open dashboard
                            {{-- 3 · login arrow --}}
                            <svg width="24" height="10" viewBox="0 0 36 14" fill="none" aria-hidden="true"><path d="M2 9 C 12 7, 22 6, 29 7 M 29 7 l -8 -4 M 29 7 l -8 5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    @else
                        <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="mm-login">
                            members log in
                            {{-- 3 · login arrow --}}
                            <svg width="24" height="10" viewBox="0 0 36 14" fill="none" aria-hidden="true"><path d="M2 9 C 12 7, 22 6, 29 7 M 29 7 l -8 -4 M 29 7 l -8 5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{-- 4 · tiny envelope, members only --}}
                            <svg width="22" height="16" viewBox="0 0 32 24" fill="none" aria-hidden="true"><path d="M3 5 C 10 4, 22 4, 29 5 L29 19 C 22 20, 10 20, 3 19 Z M3 6 L15 15 L29 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    @endauth
                </nav>
            </header>

            <dl class="mm-meta">
                <dt class="lbl">memo</dt>
                <dd>
                    № 001 — for everyone with an
                    <span class="u-strike">ugly-long
                        {{-- 5 · strike-through --}}
                        <svg viewBox="0 0 120 12" fill="none" preserveAspectRatio="none" aria-hidden="true"><path d="M2 8 C 32 4, 72 11, 118 6" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    </span>
                    link
                    {{-- 6 · asterisk --}}
                    <svg style="display:inline-block; vertical-align: super;" width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M8 2 L8 14 M2.5 5 L13.5 11 M13.5 5 L2.5 11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </dd>
                <dt class="lbl">re</dt> <dd>making links pocket-sized, no account needed</dd>
            </dl>

            {{-- 7 · star burst by the headline --}}
            <svg class="dz dz-hide-sm" style="top: 178px; right: 44px; transform: rotate(10deg);" width="34" height="34" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 3 Q 17 13, 27 15 Q 17 17, 16 27 Q 15 17, 5 15 Q 15 13, 16 3" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/></svg>

            <h1 class="mm-title" id="page-title">long links go in.<br>
                <span class="u-scribble">short links come out.
                    {{-- 8 · headline underline --}}
                    <svg viewBox="0 0 150 14" fill="none" preserveAspectRatio="none" aria-hidden="true"><path d="M3 10 C 28 4, 48 13, 74 8 S 122 9, 147 6" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/></svg>
                </span>
            </h1>
            <p class="mm-sub">Drop yours in the box below and walk away with an
                <span class="u-circle">8-character
                    {{-- 9 · hand circle --}}
                    <svg viewBox="0 0 150 54" fill="none" preserveAspectRatio="none" aria-hidden="true"><path d="M75 5 C 42 3, 10 9, 8 25 C 6 42, 44 50, 79 48 C 114 46, 142 40, 141 24 C 140 10, 104 4, 71 6" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                </span>
                href.nz link. Free, instant
                {{-- 10 · lightning for instant --}}
                <svg style="display:inline-block; vertical-align: -2px;" width="13" height="18" viewBox="0 0 24 34" fill="none" aria-hidden="true"><path d="M18 3 L8 19 L15 19 L11 31 L22 13 L15 13 L20 3 Z" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/></svg>,
                no sign-up <span class="stamp">promise!</span>
            </p>

            <ol class="mm-steps" aria-label="How it works">
                <li><strong>1</strong> Paste the long URL</li>
                {{-- 11 · step arrow --}}
                <svg width="26" height="16" viewBox="0 0 26 16" fill="none" aria-hidden="true"><path d="M2 10 C 9 8, 15 8, 21 10 M21 10 l-6 -4 M21 10 l-6 4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <li><strong>2</strong> Hit Shorten</li>
                {{-- 12 · step arrow --}}
                <svg width="26" height="16" viewBox="0 0 26 16" fill="none" aria-hidden="true"><path d="M2 10 C 9 8, 15 8, 21 10 M21 10 l-6 -4 M21 10 l-6 4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <li><strong>3</strong> Copy &amp; share</li>
            </ol>

            <div class="mm-nudge" aria-hidden="true">
                {{-- 13 · nudge arrow --}}
                <svg width="48" height="36" viewBox="0 0 72 56" fill="none">
                    <path d="M6 6 C 28 10, 52 18, 56 44 M 56 44 l -11 -4 M 56 44 l 2 -11" stroke="#717171" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>psst — right here!
                    {{-- 14 · mini heart --}}
                    <svg style="display:inline-block; vertical-align: -3px;" width="15" height="14" viewBox="0 0 32 30" fill="none" aria-hidden="true"><path d="M16 27 C 8 18, 3 12, 6 7 C 9 3, 14 5, 16 10 C 18 5, 23 3, 26 7 C 29 12, 24 18, 16 27 Z" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/></svg>
                </span>
            </div>

            <section class="mm-form-area" id="shorten" aria-label="Shorten a link">
                <livewire:public.shorten-form />
            </section>

            <ul class="mm-trust" aria-label="At a glance">
                <li>
                    {{-- 15 · check --}}
                    <svg width="15" height="14" viewBox="0 0 26 22" fill="none" aria-hidden="true"><path d="M3 12 C 6 14, 8 16, 11 19 C 14 13, 18 8, 24 3" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    No account needed
                </li>
                <li>
                    {{-- 16 · check --}}
                    <svg width="15" height="14" viewBox="0 0 26 22" fill="none" aria-hidden="true"><path d="M3 12 C 6 14, 8 16, 11 19 C 14 13, 18 8, 24 3" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    8-character links
                </li>
                <li>
                    {{-- 17 · check --}}
                    <svg width="15" height="14" viewBox="0 0 26 22" fill="none" aria-hidden="true"><path d="M3 12 C 6 14, 8 16, 11 19 C 14 13, 18 8, 24 3" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    50 / day fair use
                </li>
            </ul>

            <footer class="mm-signoff">
                — by the makers of <strong>href.re</strong> &amp; <strong>static.re</strong>
                {{-- 18 · smiley sign-off --}}
                <svg style="display:inline-block; vertical-align: -4px;" width="20" height="20" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 3 C 8 3, 3 9, 3.5 16 C 4 23, 9 29, 16 29 C 23 29, 28.5 23, 28 16 C 27.5 9, 23 3.5, 16 3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><circle cx="11.5" cy="13.5" r="1.6" fill="currentColor"/><circle cx="20.5" cy="13.5" r="1.6" fill="currentColor"/><path d="M10 20 Q 16 25, 22 19" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
            </footer>
        </article>

        <div class="mm-div" aria-hidden="true">
            {{-- 19 · divider squiggle --}}
            <svg width="132" height="12" viewBox="0 0 132 12" fill="none">
                <path d="M2 8 Q 13 2, 24 8 T 46 8 T 68 8 T 90 8 T 112 8 T 134 8" stroke="#cfcfcf" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
            {{-- 20 · scissors on the cut line --}}
            <svg width="30" height="26" viewBox="0 0 32 28" fill="none" aria-hidden="true"><circle cx="7" cy="7" r="3.4" stroke="currentColor" stroke-width="2.2"/><circle cx="7" cy="21" r="3.4" stroke="currentColor" stroke-width="2.2"/><path d="M9.5 9 L27 21 M9.5 19 L27 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
        </div>

        <aside class="mm-notes" aria-label="Good to know">
            <div class="mm-note">
                <span class="pin" aria-hidden="true"></span>
                {{-- 21 · note arrow --}}
                <svg class="dz" style="top: 8px; right: 10px; transform: rotate(14deg);" width="30" height="24" viewBox="0 0 40 30" fill="none" aria-hidden="true"><path d="M4 24 C 14 20, 24 14, 30 5 M30 5 l-8 1 M30 5 l-1 8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <h2>margin note ① — members get more</h2>
                <p>Guests get auto-made codes — picking your own is a members' perk.</p>
                <p><a href="{{ \App\Support\DomainUrls::dashboard('/login') }}">Log in</a> for custom slugs, shorter links &amp; click stats.</p>
            </div>
            <div class="mm-note">
                <span class="pin" aria-hidden="true"></span>
                {{-- 22 · note star --}}
                <svg class="dz" style="top: 10px; right: 12px; transform: rotate(-8deg);" width="22" height="22" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 3 Q 17 13, 27 15 Q 17 17, 16 27 Q 15 17, 5 15 Q 15 13, 16 3" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/></svg>
                <h2>margin note ② — fair &amp; private</h2>
                <p>Fair use: 50 links a day per guest. Nothing of yours is kept but a hashed IP for counting.</p>
                <p>Links stay active as long as they're legit — abuse gets removed.</p>
            </div>
            <div class="mm-note">
                <span class="pin" aria-hidden="true"></span>
                {{-- 23 · note seal --}}
                <svg class="dz" style="top: 6px; right: 8px; transform: rotate(10deg);" width="30" height="30" viewBox="0 0 34 34" fill="none" aria-hidden="true"><path d="M17 3 C 9 3, 3.5 9, 3.5 17 C 3.5 25, 9 31, 17 31 C 25 31, 30.5 25, 30.5 17 C 30.5 9, 25 3, 17 3" stroke="currentColor" stroke-width="2.2"/><path d="M11 17 C 13 18.5, 14.5 20, 16 22 C 19 17, 21.5 14, 24 11" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <h2>margin note ③ — official business?</h2>
                <p>Need a link people can trust is really from you?</p>
                <p>That's next door on <a href="https://href.re">href.re</a> — verified, analytics-backed, business only.
                    {{-- 24 · hop-over arrow --}}
                    <svg style="display:inline-block; vertical-align: -3px;" width="26" height="12" viewBox="0 0 36 16" fill="none" aria-hidden="true"><path d="M2 11 C 12 9, 22 8, 30 9 M30 9 l-8 -4 M30 9 l-8 5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </p>
            </div>
        </aside>

        <div class="mm-cut" aria-hidden="true">
            {{-- 25 · second scissors, footer cut --}}
            <svg width="26" height="22" viewBox="0 0 32 28" fill="none" aria-hidden="true"><circle cx="7" cy="7" r="3.4" stroke="currentColor" stroke-width="2.2"/><circle cx="7" cy="21" r="3.4" stroke="currentColor" stroke-width="2.2"/><path d="M9.5 9 L27 21 M9.5 19 L27 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
        </div>

        <footer class="mm-foot">
            made with
            {{-- 26 · footer heart (grayscale, no emoji red) --}}
            <svg style="display:inline-block; vertical-align: -3px;" width="14" height="13" viewBox="0 0 32 30" fill="none" aria-hidden="true"><path d="M16 27 C 8 18, 3 12, 6 7 C 9 3, 14 5, 16 10 C 18 5, 23 3, 26 7 C 29 12, 24 18, 16 27 Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/></svg>
            by ternis.link from <a href="https://ternis.dev">ternis.dev</a> · hosted on <a href="https://ternis.net">ternis.net</a> · official links on <a href="https://href.re">href.re</a>
            {{-- 27 · footer sparkle --}}
            <svg style="display:inline-block; vertical-align: -2px;" width="13" height="13" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 3 Q 17 13, 27 15 Q 17 17, 16 27 Q 15 17, 5 15 Q 15 13, 16 3" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/></svg>
        </footer>
    </main>

    @livewireScripts
</body>
</html>
