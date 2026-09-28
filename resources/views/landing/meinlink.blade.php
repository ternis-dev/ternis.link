<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>meinlink.at — lange Links rein, kurze Links raus</title>
    <meta name="description" content="meinlink.at — der österreichische Linkkürzer ohne Konto. Lange URL einfügen, 8-Zeichen-Kurzlink erhalten. Gratis, schnell, keine Anmeldung.">
    <meta name="theme-color" content="#f6f3ec">
    <link rel="canonical" href="https://meinlink.at/">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="meinlink.at">
    <meta property="og:title" content="meinlink.at — lange Links rein, kurze Links raus">
    <meta property="og:description" content="Lange URL einfügen, 8-Zeichen-meinlink.at-Link erhalten. Kein Konto nötig.">
    <meta property="og:url" content="https://meinlink.at/">
    <meta name="twitter:card" content="summary">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/landing-meinlink.css'])
    @livewireStyles
    @if (config('services.turnstile.key'))
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
    @endif
</head>
<body class="sk-root ml-root">

    <div class="sk-loader" id="sk-loader" aria-hidden="true" role="presentation">
        <div class="sk-loader-inner">
            <p class="sk-load-brand">meinlink.at</p>
            <div class="sk-load-row">
                <svg class="sk-load-bar" viewBox="0 0 360 64" fill="none" aria-hidden="true">
                    <defs>
                        <clipPath id="sk-load-clip">
                            <rect x="18" y="18" width="324" height="28"/>
                        </clipPath>
                        <pattern id="sk-load-hatch" width="9" height="9" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
                            <line x1="0" y1="0" x2="0" y2="9" stroke="#2b2b2b" stroke-width="2.6"/>
                        </pattern>
                        <filter id="sk-load-rough" x="-10%" y="-30%" width="120%" height="160%">
                            <feTurbulence type="fractalNoise" baseFrequency="0.04" numOctaves="2" seed="3" result="noise"/>
                            <feDisplacementMap in="SourceGraphic" in2="noise" scale="3.5"/>
                        </filter>
                    </defs>
                    <g clip-path="url(#sk-load-clip)">
                        <rect class="sk-loader-fill" x="18" y="14" width="0" height="36" fill="url(#sk-load-hatch)"/>
                    </g>
                    <g filter="url(#sk-load-rough)">
                        <path class="sk-load-frame"
                              d="M14 14 C 120 11, 240 15, 346 12 L346 50 C 240 53, 120 49, 14 52 Z"/>
                        <path class="sk-load-frame-sketch"
                              d="M20 20 C 130 18, 230 21, 340 19 M20 44 C 130 46, 240 43, 340 45"/>
                    </g>
                </svg>
                <span class="sk-load-pct" id="sk-load-pct">0%</span>
            </div>
            <p class="sk-load-status">lädt das Skizzenbuch…</p>
        </div>
    </div>

    <div class="sk-gauge" id="sk-gauge" role="scrollbar" aria-orientation="vertical" aria-label="Seite scrollen" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" tabindex="0">
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
    <a class="sk-skip" href="#shorten">Zum Kürzen springen</a>

    <div class="sk-wrap sk-enter">
        <header class="sk-head">
            <a href="/" class="sk-brand" aria-label="meinlink.at Startseite">meinlink<span>.at</span></a>
            <nav aria-label="Konto">
                @auth
                    <a href="{{ \App\Support\DomainUrls::dashboard('/') }}" class="sk-login">
                        Dashboard öffnen
                        <svg width="26" height="12" viewBox="0 0 28 14" fill="none" aria-hidden="true"><path d="M2 10 C 10 8, 18 7.5, 24 8.5 M24 8.5 L18.5 5 M24 8.5 L18.5 12" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                @else
                    <a href="{{ url('/login') }}" class="sk-login">
                        Mitglieder-Login
                        <svg width="26" height="12" viewBox="0 0 28 14" fill="none" aria-hidden="true"><path d="M2 10 C 10 8, 18 7.5, 24 8.5 M24 8.5 L18.5 5 M24 8.5 L18.5 12" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <svg width="22" height="17" viewBox="0 0 24 18" fill="none" aria-hidden="true"><path d="M3.5 5 h17 v9 h-17 Z M4 6.5 12 12.5 20 6.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                @endauth
            </nav>
        </header>

        <section class="sk-hero">
            {{-- Berggipfel --}}
            <svg class="dk dk-hide-sm" style="top: 2px; left: 4px; transform: rotate(-6deg);" width="64" height="44" viewBox="0 0 64 44" fill="none" aria-hidden="true"><path d="M4 38 L22 10 L30 24 L37 14 L60 38" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 17 L22 10 L27 17 M33 20 L37 14 L41 20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" opacity="0.7"/></svg>

            {{-- Edelweiß --}}
            <svg class="dk ml-dk-red" style="top: 0; right: 30px; transform: rotate(12deg);" width="36" height="36" viewBox="0 0 36 36" fill="none" aria-hidden="true"><circle cx="18" cy="18" r="3" stroke="currentColor" stroke-width="2"/><ellipse cx="18" cy="9" rx="3" ry="5" stroke="currentColor" stroke-width="1.8"/><ellipse cx="18" cy="27" rx="3" ry="5" stroke="currentColor" stroke-width="1.8"/><ellipse cx="9" cy="18" rx="5" ry="3" stroke="currentColor" stroke-width="1.8"/><ellipse cx="27" cy="18" rx="5" ry="3" stroke="currentColor" stroke-width="1.8"/></svg>

            {{-- zweiter Gipfel, links unten --}}
            <svg class="dk dk-soft dk-hide-sm" style="top: 210px; left: -6px; transform: rotate(-8deg);" width="40" height="30" viewBox="0 0 64 44" fill="none" aria-hidden="true"><path d="M4 38 L22 10 L30 24 L37 14 L60 38" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>

            <span class="sk-kicker">der Kürzer ohne Konto</span>
            <svg style="display:inline-block; vertical-align: super;" width="15" height="15" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M8 2.5v11 M2.9 5.2l10.2 5.9 M13.1 5.2 2.9 11.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <svg class="dk dk-faint dk-hide-sm" style="top: 64px; right: 6px; transform: rotate(8deg);" width="46" height="18" viewBox="0 0 60 22" fill="none" aria-hidden="true"><path d="M3 14 C 12 8, 18 18, 28 12 S 46 10, 57 9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>

            <h1 class="sk-title" id="page-title">lange Links rein,<br>
                <span class="sk-u">kurze Links
                    <svg viewBox="0 0 150 14" fill="none" preserveAspectRatio="none" aria-hidden="true"><path d="M4 9.5 C 35 5.5, 60 11.5, 90 8 S 130 8, 146 6.5" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/></svg>
                </span> raus.
            </h1>

            <p class="sk-sub">Füg unten eine lange URL ein und erhalte einen
                <span class="sk-o">8-Zeichen
                    <svg viewBox="0 0 150 54" fill="none" preserveAspectRatio="none" aria-hidden="true"><path d="M74 6 C 45 4.5, 14 9, 11 24 C 8 39, 42 48, 76 47 C 110 46, 139 41, 139 25 C 139 11, 105 5, 76 6.5" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                </span>
                meinlink.at-Link. Gratis, sofort
                <svg style="display:inline-block; vertical-align: -2px;" width="13" height="18" viewBox="0 0 24 32" fill="none" aria-hidden="true"><path d="M13.5 2.5 6.5 17.5 H12 L10.5 29.5 19 13.5 H13.2 Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>,
                keine Anmeldung, kein Aufwand
                <svg style="display:inline-block; vertical-align: -2px;" width="14" height="13" viewBox="0 0 24 22" fill="none" aria-hidden="true"><path d="M12 19.5 C 7 14, 3.5 10.5, 4.8 7 C 6 3.9, 10 4.5, 12 8.5 C 14 4.5, 18 3.9, 19.2 7 C 20.5 10.5, 17 14, 12 19.5 Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>.
            </p>

            <ol class="sk-steps" aria-label="So geht's">
                <li><span class="n" aria-hidden="true">1</span> Lange URL einfügen</li>
                <svg class="sk-step-arrow" width="26" height="16" viewBox="0 0 26 16" fill="none" aria-hidden="true"><path d="M2 10 C 9 8.5, 15 8.5, 21 9.5 M21 9.5 L15.5 6 M21 9.5 L15.5 13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <li><span class="n" aria-hidden="true">2</span> Kürzen drücken</li>
                <svg class="sk-step-arrow" width="26" height="16" viewBox="0 0 26 16" fill="none" aria-hidden="true"><path d="M2 10 C 9 8.5, 15 8.5, 21 9.5 M21 9.5 L15.5 6 M21 9.5 L15.5 13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <li><span class="n" aria-hidden="true">3</span> Kopieren &amp; teilen
                    <svg width="15" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4.5 12.5 10 18 19.5 6.5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </li>
            </ol>
        </section>

        <section class="sk-form-zone" id="shorten" aria-label="Link kürzen">
            <svg class="dk dk-soft dk-hide-sm" style="top: -46px; left: 44px; transform: rotate(6deg);" width="44" height="52" viewBox="0 0 48 56" fill="none" aria-hidden="true"><path d="M12 5 C 25 11, 35 23, 33.5 44 M33.5 44 L24.5 39.5 M33.5 44 L35.5 34" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <livewire:public.shorten-form locale="de" />
        </section>

        <ul class="sk-trust" aria-label="Auf einen Blick">
            <li>
                <svg width="14" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4.5 12.5 10 18 19.5 6.5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Kein Konto nötig
            </li>
            <li>
                <svg width="14" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4.5 12.5 10 18 19.5 6.5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                8-Zeichen-Links
            </li>
            <li>
                <svg width="14" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4.5 12.5 10 18 19.5 6.5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                50 pro Tag Fair Use
            </li>
        </ul>

        <aside class="sk-notes" aria-label="Gut zu wissen">
            <svg class="dk dk-soft dk-hide-sm" style="top: -38px; left: 50%; margin-left: -20px; transform: rotate(8deg);" width="40" height="34" viewBox="0 0 44 38" fill="none" aria-hidden="true"><path d="M7 5 C 19 9, 30 17, 32.5 31 M32.5 31 L24 27.5 M32.5 31 L33.5 22.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <div class="sk-note">
                <span class="sk-pin" aria-hidden="true"></span>
                <svg class="dk" style="top: 10px; right: 12px;" width="24" height="30" viewBox="0 0 24 30" fill="none" aria-hidden="true"><path d="M12 3 C 8 3, 5 6.2, 5 10 c0 2.5 1.3 4.2 2.7 5.3 .7.6 1 1.1 1 2.2 h6.6 c0-1.1.3-1.6 1-2.2 C 17.7 14.2, 19 12.5, 19 10 C 19 6.2, 16 3, 12 3 Z M9.5 21.5 h5 M10.5 25 h3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <h2>Mitglieder bekommen mehr</h2>
                <p>Gäste bekommen automatisch erzeugte Codes — eigene Kürzel sind ein Mitglieder-Vorteil.</p>
                <p><a href="{{ url('/login') }}">Log dich ein</a> für eigene Slugs, kürzere Links &amp; Klick-Statistiken.</p>
            </div>
            <div class="sk-note">
                <span class="sk-pin" aria-hidden="true"></span>
                <svg class="dk" style="top: 10px; right: 12px;" width="22" height="28" viewBox="0 0 24 30" fill="none" aria-hidden="true"><path d="M6.5 13.5 h11 v11 h-11 Z M9 13.5 V10 a3 3 0 0 1 6 0 v3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="18.5" r="1.2" fill="currentColor"/></svg>
                <h2>fair &amp; privat</h2>
                <p>Fair Use: 50 Links pro Tag und Gast. Gespeichert wird nichts außer einem gehashten IP-Zähler.</p>
                <p>Links bleiben aktiv, solange sie in Ordnung sind — Missbrauch wird entfernt.</p>
            </div>
            <div class="sk-note">
                <span class="sk-pin" aria-hidden="true"></span>
                <svg class="dk" style="top: 8px; right: 10px; transform: rotate(10deg);" width="30" height="30" viewBox="0 0 32 32" fill="none" aria-hidden="true"><circle cx="16" cy="16" r="12" stroke="currentColor" stroke-width="2"/><path d="m10.5 16 3.8 3.8 7.2-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <h2>offiziell unterwegs?</h2>
                <p>Du brauchst einen Link, dem man ansieht, dass er wirklich von dir ist?</p>
                <p>Den gibt's nebenan auf <a href="https://href.re">href.re</a> — geprüft, mit Statistik, nur für Unternehmen.
                    <svg style="display:inline-block; vertical-align: -3px;" width="26" height="12" viewBox="0 0 28 14" fill="none" aria-hidden="true"><path d="M2 10 C 10 8, 18 7.5, 24 8.5 M24 8.5 L18.5 5 M24 8.5 L18.5 12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </p>
            </div>
        </aside>

        <div class="sk-cut" aria-hidden="true">
            <svg width="28" height="24" viewBox="0 0 32 28" fill="none" aria-hidden="true"><circle cx="7" cy="8" r="3.2" stroke="currentColor" stroke-width="2"/><circle cx="7" cy="20" r="3.2" stroke="currentColor" stroke-width="2"/><path d="M9.8 10.2 26 20 M9.8 17.8 26 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </div>

        <footer class="sk-foot">
            skizziert von ternis.link von <a href="https://ternis.dev">ternis.dev</a> · gehostet auf <a href="https://ternis.net">ternis.net</a> · offizielle Links auf <a href="https://href.re">href.re</a>
            · <a href="https://ternis.link/pages/legal/privacy">Datenschutz</a> · <a href="https://ternis.link/pages/legal/terms">AGB</a> · <a href="https://ternis.dev/en/legal/imprint">Impressum</a>
            <svg style="display:inline-block; vertical-align: -2px;" width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2.5c.7 4.8 2.1 6.9 7.5 7.5-5.4.6-6.8 2.7-7.5 7.5-.7-4.8-2.1-6.9-7.5-7.5 5.4-.6 6.8-2.7 7.5-7.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
            <svg style="display:inline-block; vertical-align: -2px;" width="13" height="12" viewBox="0 0 24 22" fill="none" aria-hidden="true"><path d="M12 19.5 C 7 14, 3.5 10.5, 4.8 7 C 6 3.9, 10 4.5, 12 8.5 C 14 4.5, 18 3.9, 19.2 7 C 20.5 10.5, 17 14, 12 19.5 Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
        </footer>
    </div>

    @livewireScripts

    <script>
    (function () {
        var loader = document.getElementById('sk-loader');
        var fill   = loader ? loader.querySelector('.sk-loader-fill') : null;
        var pct    = document.getElementById('sk-load-pct');
        if (!loader || !fill) return;

        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var FILL_W  = 324;
        var start   = null;
        var FILL_MS = reduced ? 0 : 700;
        var HOLD_MS = reduced ? 0 : 120;

        function setFill(eased) {
            fill.setAttribute('width', String(Math.round(FILL_W * eased)));
            if (pct) pct.textContent = Math.round(eased * 100) + '%';
        }

        setFill(0);

        function dismiss() {
            loader.classList.add('sk-loader-done');
            loader.addEventListener('transitionend', function onEnd() {
                loader.removeEventListener('transitionend', onEnd);
                loader.hidden = true;
            });
        }

        function animateFill(ts) {
            if (!start) start = ts;
            var elapsed  = ts - start;
            var progress = Math.min(1, elapsed / Math.max(FILL_MS, 1));
            var eased    = 1 - Math.pow(1 - progress, 3);
            setFill(eased);

            if (progress < 1) {
                requestAnimationFrame(animateFill);
            } else {
                setTimeout(dismiss, HOLD_MS);
            }
        }

        function run() {
            if (reduced) {
                dismiss();
            } else {
                requestAnimationFrame(animateFill);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', run);
        } else {
            run();
        }
    })();
    </script>

    <script>
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
