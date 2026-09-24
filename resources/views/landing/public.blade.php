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
</head>
<body class="sk-root">
    <div class="sk-gauge" id="sk-gauge" role="scrollbar" aria-orientation="vertical" aria-label="Scroll page" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" tabindex="0">
        <svg viewBox="0 0 40 1000" preserveAspectRatio="none" aria-hidden="true">
            <defs>
                <clipPath id="sk-gauge-clip">
                    <path d="M12 22 C 10 300, 15 650, 12 978 A8 8 0 0 0 28 978 C 25 650, 30 300, 28 22 A8 8 0 0 0 12 22 Z" />
                </clipPath>
                <pattern id="sk-hatch" width="9" height="9" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
                    <line x1="0" y1="0" x2="0" y2="9" stroke="#2b2b2b" stroke-width="2.6" />
                </pattern>
                <filter id="sk-rough" x="-20%" y="-20%" width="140%" height="140%">
                    <feTurbulence type="fractalNoise" baseFrequency="0.035" numOctaves="2" seed="7" result="noise" />
                    <feDisplacementMap in="SourceGraphic" in2="noise" scale="4" />
                </filter>
            </defs>
            <g filter="url(#sk-rough)">
                <path class="sk-gauge-outline" d="M12 22 C 9 290, 16 640, 11 978 A8 8 0 0 0 28 978 C 26 640, 31 290, 28 22 A8 8 0 0 0 12 22 Z" />
                <path class="sk-gauge-sketch" d="M12 22 C 11 200, 13 420, 12 640 M28 340 C 27 560, 29 780, 27 978 M4 22 C 14 20, 24 20, 34 23 M6 978 C 15 980, 25 980, 34 977" />
            </g>
            <g clip-path="url(#sk-gauge-clip)">
                <rect id="sk-gauge-fill" x="0" y="1000" width="40" height="0" fill="url(#sk-hatch)" />
            </g>
        </svg>
    </div>
    <a class="sk-skip" href="#shorten">Skip to the shortener</a>

    <div class="sk-wrap">
        <header class="sk-head">
            <a href="/" class="sk-brand" aria-label="href.nz home">href<span>.nz</span></a>
            <nav aria-label="Account">
                @auth
                    <a href="{{ \App\Support\DomainUrls::dashboard('/dashboard') }}" class="sk-login">
                        open dashboard
                        {{-- 1 · login arrow --}}
                        <svg width="26" height="12" viewBox="0 0 38 16" fill="none" aria-hidden="true"><path d="M2 11 C 13 9, 24 8, 31 9 M31 9 l-8 -4 M31 9 l-8 5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                @else
                    <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}" class="sk-login">
                        members log in
                        {{-- 1 · login arrow --}}
                        <svg width="26" height="12" viewBox="0 0 38 16" fill="none" aria-hidden="true"><path d="M2 11 C 13 9, 24 8, 31 9 M31 9 l-8 -4 M31 9 l-8 5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {{-- 2 · envelope --}}
                        <svg width="22" height="17" viewBox="0 0 32 25" fill="none" aria-hidden="true"><path d="M3 5 C 10 4, 22 4, 29 5 L29 20 C 22 21, 10 21, 3 20 Z M3 6.5 L15 15.5 L29 6.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                @endauth
            </nav>
        </header>

        <section class="sk-hero">
            {{-- 3 · paper plane --}}
            <svg class="dk dk-hide-sm" style="top: 6px; left: 8px; transform: rotate(-12deg);" width="54" height="40" viewBox="0 0 60 44" fill="none" aria-hidden="true"><path d="M4 24 L52 5 L36 39 L27 28 Z M27 28 L52 5 M27 28 L22 38" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 32 C 12 34, 18 34, 24 33" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" opacity="0.6"/></svg>

            {{-- 4 · star burst --}}
            <svg class="dk" style="top: 0; right: 30px; transform: rotate(12deg);" width="32" height="32" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 3 Q 17 13, 27 15 Q 17 17, 16 27 Q 15 17, 5 15 Q 15 13, 16 3" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/></svg>

            {{-- 29 · second star, lower left --}}
            <svg class="dk dk-soft dk-hide-sm" style="top: 210px; left: -6px; transform: rotate(-14deg);" width="24" height="24" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 3 Q 17 13, 27 15 Q 17 17, 16 27 Q 15 17, 5 15 Q 15 13, 16 3" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/></svg>

            <span class="sk-kicker">the no-account shortener</span>
            {{-- asterisk --}}
            <svg style="display:inline-block; vertical-align: super;" width="15" height="15" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M8 2 L8 14 M2.5 5 L13.5 11 M13.5 5 L2.5 11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            {{-- squiggle flourish --}}
            <svg class="dk dk-faint dk-hide-sm" style="top: 64px; right: 6px; transform: rotate(8deg);" width="46" height="18" viewBox="0 0 60 22" fill="none" aria-hidden="true"><path d="M3 14 C 12 6, 20 18, 30 11 S 48 12, 57 8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>

            <h1 class="sk-title" id="page-title">long links go in,<br>
                <span class="sk-u">short links
                    {{-- 5 · headline underline --}}
                    <svg viewBox="0 0 150 14" fill="none" preserveAspectRatio="none" aria-hidden="true"><path d="M3 10 C 30 4, 52 13, 78 8 S 124 9, 147 6" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/></svg>
                </span> come out.
            </h1>

            <p class="sk-sub">Paste any long URL below and get back an
                <span class="sk-o">8-character
                    {{-- 6 · hand circle --}}
                    <svg viewBox="0 0 150 54" fill="none" preserveAspectRatio="none" aria-hidden="true"><path d="M75 5 C 42 3, 10 9, 8 25 C 6 42, 44 50, 79 48 C 114 46, 142 40, 141 24 C 140 10, 104 4, 71 6" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                </span>
                href.nz link. Free, instant
                {{-- 7 · lightning --}}
                <svg style="display:inline-block; vertical-align: -2px;" width="13" height="18" viewBox="0 0 24 34" fill="none" aria-hidden="true"><path d="M18 3 L8 19 L15 19 L11 31 L22 13 L15 13 L20 3 Z" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/></svg>,
                no sign-up, no fuss
                {{-- 8 · heart --}}
                <svg style="display:inline-block; vertical-align: -2px;" width="14" height="13" viewBox="0 0 32 30" fill="none" aria-hidden="true"><path d="M16 27 C 8 18, 3 12, 6 7 C 9 3, 14 5, 16 10 C 18 5, 23 3, 26 7 C 29 12, 24 18, 16 27 Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/></svg>.
            </p>

            <ol class="sk-steps" aria-label="How it works">
                <li><span class="n" aria-hidden="true">1</span> Paste the long URL</li>
                {{-- 9 · step arrow --}}
                <svg class="sk-step-arrow" width="26" height="16" viewBox="0 0 26 16" fill="none" aria-hidden="true"><path d="M2 10 C 9 8, 15 8, 21 10 M21 10 l-6 -4 M21 10 l-6 4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <li><span class="n" aria-hidden="true">2</span> Hit Shorten</li>
                {{-- 10 · step arrow --}}
                <svg class="sk-step-arrow" width="26" height="16" viewBox="0 0 26 16" fill="none" aria-hidden="true"><path d="M2 10 C 9 8, 15 8, 21 10 M21 10 l-6 -4 M21 10 l-6 4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <li><span class="n" aria-hidden="true">3</span> Copy &amp; share
                    {{-- 11 · mini check --}}
                    <svg width="15" height="14" viewBox="0 0 26 22" fill="none" aria-hidden="true"><path d="M3 12 C 6 14, 8 16, 11 19 C 14 13, 18 8, 24 3" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </li>
            </ol>
        </section>

        <section class="sk-form-zone" id="shorten" aria-label="Shorten a link">
            {{-- 12 · curly pointer down to the form --}}
            <svg class="dk dk-soft dk-hide-sm" style="top: -46px; left: 44px; transform: rotate(6deg);" width="44" height="52" viewBox="0 0 48 56" fill="none" aria-hidden="true"><path d="M10 4 C 26 10, 38 22, 36 46 M36 46 l-10 -5 M36 46 l2 -11" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <livewire:public.shorten-form />
        </section>

        <ul class="sk-trust" aria-label="At a glance">
            <li>
                {{-- 13 · check --}}
                <svg width="14" height="13" viewBox="0 0 26 22" fill="none" aria-hidden="true"><path d="M3 12 C 6 14, 8 16, 11 19 C 14 13, 18 8, 24 3" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                No account needed
            </li>
            <li>
                {{-- 14 · check --}}
                <svg width="14" height="13" viewBox="0 0 26 22" fill="none" aria-hidden="true"><path d="M3 12 C 6 14, 8 16, 11 19 C 14 13, 18 8, 24 3" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                8-character links
            </li>
            <li>
                {{-- 15 · check --}}
                <svg width="14" height="13" viewBox="0 0 26 22" fill="none" aria-hidden="true"><path d="M3 12 C 6 14, 8 16, 11 19 C 14 13, 18 8, 24 3" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                50 / day fair use
            </li>
        </ul>

        <aside class="sk-notes" aria-label="Good to know">
            {{-- looping arrow into the notes --}}
            <svg class="dk dk-soft dk-hide-sm" style="top: -38px; left: 50%; margin-left: -20px; transform: rotate(8deg);" width="40" height="34" viewBox="0 0 44 38" fill="none" aria-hidden="true"><path d="M6 4 C 20 8, 32 16, 34 32 M34 32 l-9 -4 M34 32 l1 -10" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <div class="sk-note">
                <span class="sk-pin" aria-hidden="true"></span>
                {{-- 16 · lightbulb --}}
                <svg class="dk" style="top: 10px; right: 12px;" width="24" height="30" viewBox="0 0 28 36" fill="none" aria-hidden="true"><path d="M14 3 C 8 3, 4 8, 4 13 C 4 17, 6 19, 8 21 L8 26 L20 26 L20 21 C 22 19, 24 17, 24 13 C 24 8, 20 3, 14 3 Z M10 30 L18 30 M11 33 L17 33" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <h2>members get more</h2>
                <p>Guests get auto-made codes — picking your own is a members' perk.</p>
                <p><a href="{{ \App\Support\DomainUrls::dashboard('/login') }}">Log in</a> for custom slugs, shorter links &amp; click stats.</p>
            </div>
            <div class="sk-note">
                <span class="sk-pin" aria-hidden="true"></span>
                {{-- 17 · lock --}}
                <svg class="dk" style="top: 10px; right: 12px;" width="22" height="28" viewBox="0 0 28 34" fill="none" aria-hidden="true"><path d="M6 15 L6 29 L22 29 L22 15 Z M9.5 15 L9.5 10 a4.5 4.5 0 0 1 9 0 L18.5 10 M9.5 15 L9.5 10.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="14" cy="22" r="1.8" fill="currentColor"/></svg>
                <h2>fair &amp; private</h2>
                <p>Fair use: 50 links a day per guest. Nothing of yours is kept but a hashed IP for counting.</p>
                <p>Links stay active as long as they're legit — abuse gets removed.</p>
            </div>
            <div class="sk-note">
                <span class="sk-pin" aria-hidden="true"></span>
                {{-- 18 · seal --}}
                <svg class="dk" style="top: 8px; right: 10px; transform: rotate(10deg);" width="30" height="30" viewBox="0 0 34 34" fill="none" aria-hidden="true"><path d="M17 3 C 9 3, 3.5 9, 3.5 17 C 3.5 25, 9 31, 17 31 C 25 31, 30.5 25, 30.5 17 C 30.5 9, 25 3, 17 3" stroke="currentColor" stroke-width="2.2"/><path d="M11 17 C 13 18.5, 14.5 20, 16 22 C 19 17, 21.5 14, 24 11" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <h2>official business?</h2>
                <p>Need a link people can trust is really from you?</p>
                <p>That's next door on <a href="https://href.re">href.re</a> — verified, analytics-backed, business only.
                    {{-- 19 · hop-over arrow --}}
                    <svg style="display:inline-block; vertical-align: -3px;" width="26" height="12" viewBox="0 0 36 16" fill="none" aria-hidden="true"><path d="M2 11 C 12 9, 22 8, 30 9 M30 9 l-8 -4 M30 9 l-8 5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </p>
            </div>
        </aside>

        <div class="sk-cut" aria-hidden="true">
            {{-- 20 · scissors --}}
            <svg width="28" height="24" viewBox="0 0 32 28" fill="none" aria-hidden="true"><circle cx="7" cy="7" r="3.4" stroke="currentColor" stroke-width="2.2"/><circle cx="7" cy="21" r="3.4" stroke="currentColor" stroke-width="2.2"/><path d="M9.5 9 L27 21 M9.5 19 L27 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
        </div>

        <footer class="sk-foot">
            sketched by ternis.link from <a href="https://ternis.dev">ternis.dev</a> · hosted on <a href="https://ternis.net">ternis.net</a> · official links on <a href="https://href.re">href.re</a>
            {{-- footer sparkle --}}
            <svg style="display:inline-block; vertical-align: -2px;" width="13" height="13" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 3 Q 17 13, 27 15 Q 17 17, 16 27 Q 15 17, 5 15 Q 15 13, 16 3" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/></svg>
            {{-- footer heart --}}
            <svg style="display:inline-block; vertical-align: -2px;" width="13" height="12" viewBox="0 0 32 30" fill="none" aria-hidden="true"><path d="M16 27 C 8 18, 3 12, 6 7 C 9 3, 14 5, 16 10 C 18 5, 23 3, 26 7 C 29 12, 24 18, 16 27 Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/></svg>
        </footer>
    </div>

    @livewireScripts

    <script>
    /* Drawn scrollbar: hatched gauge tube on the right edge. The
     * hatching shades upward with scroll; the pencil knob rides the
     * fill surface and is draggable, the tube is click-to-jump,
     * keys work when focused. */
    (function () {
        if (typeof document === 'undefined') return;

        function init() {
            var gauge = document.getElementById('sk-gauge');
            var hatch = document.getElementById('sk-gauge-fill');
            if (!gauge || !hatch) return;

            function max() {
                return Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
            }

            function render() {
                var m = max();
                if (m <= 0) {
                    gauge.style.display = 'none';
                    return;
                }
                gauge.style.display = '';
                var p = Math.min(1, Math.max(0, (window.scrollY || 0) / m));
                /* viewBox is 1000 tall: hatching rises from the bottom. */
                hatch.setAttribute('y', String(1000 - 1000 * p));
                hatch.setAttribute('height', String(1000 * p));
                gauge.setAttribute('aria-valuenow', String(Math.round(p * 100)));
            }

            var ticking = false;
            function requestRender() {
                if (!ticking) {
                    ticking = true;
                    requestAnimationFrame(function () {
                        ticking = false;
                        render();
                    });
                }
            }

            window.addEventListener('scroll', requestRender, { passive: true });
            window.addEventListener('resize', render);

            /* Keyboard support. */
            gauge.addEventListener('keydown', function (event) {
                var m = max();
                var y = window.scrollY || 0;
                if (event.key === 'ArrowDown') window.scrollTo({ top: y + 80 });
                else if (event.key === 'ArrowUp') window.scrollTo({ top: y - 80 });
                else if (event.key === 'PageDown') window.scrollTo({ top: y + window.innerHeight * 0.9 });
                else if (event.key === 'PageUp') window.scrollTo({ top: y - window.innerHeight * 0.9 });
                else if (event.key === 'Home') window.scrollTo({ top: 0 });
                else if (event.key === 'End') window.scrollTo({ top: m });
                else return;
                event.preventDefault();
            });

            render();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
    </script>
</body>
</html>
