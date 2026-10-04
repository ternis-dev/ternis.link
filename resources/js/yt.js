/* ==========================================================
 * href.yt — custom JS
 * Preloader, scroll-counter animation for stats.
 * ========================================================== */

(function () {
    'use strict';

    /* ── Preloader ──────────────────────────────────────────
     * Fills the slim red progress bar and fades the overlay
     * after fonts settle. Respects prefers-reduced-motion.    */
    (function initLoader() {
        var loader = document.getElementById('yt-loader');
        var fill   = document.getElementById('yt-loader-fill');
        var pct    = document.getElementById('yt-load-pct');
        if (!loader || !fill) return;

        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var start   = null;
        var FILL_MS = reduced ? 0 : 500;
        var HOLD_MS = reduced ? 0 : 80;

        function setFill(p) {
            var w = Math.min(100, Math.round(p * 100));
            fill.style.width = w + '%';
            if (pct) pct.textContent = w + '%';
        }

        setFill(0);

        function dismiss() {
            loader.classList.add('yt-loader-done');
            var done = false;
            function hide() {
                if (done) return;
                done = true;
                loader.hidden = true;
            }
            loader.addEventListener('transitionend', hide, { once: true });
            setTimeout(hide, 400);
        }

        function waitFonts(cb) {
            if (document.fonts && document.fonts.ready) {
                Promise.race([
                    document.fonts.ready,
                    new Promise(function (r) { setTimeout(r, 800); }),
                ]).then(cb).catch(cb);
            } else {
                cb();
            }
        }

        function animate(ts) {
            if (!start) start = ts;
            var p = Math.min(1, (ts - start) / Math.max(FILL_MS, 1));
            var e = 1 - Math.pow(1 - p, 3);
            setFill(e);
            if (p < 1) {
                requestAnimationFrame(animate);
            } else {
                waitFonts(function () { setTimeout(dismiss, HOLD_MS); });
            }
        }

        function run() {
            if (reduced) { dismiss(); } else { requestAnimationFrame(animate); }
        }

        /* Restore page instantly on bfcache restore */
        window.addEventListener('pageshow', function (e) {
            if (e.persisted && loader) {
                loader.classList.add('yt-loader-done');
                loader.hidden = true;
            }
        });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', run);
        } else {
            run();
        }
    }());

    /* ── Scroll-counter animation ───────────────────────────
     * When a .yt-stat-num element scrolls into view it counts
     * up from 0 to its data-target value.                    */
    (function initCounters() {
        var counters = document.querySelectorAll('.yt-stat-num[data-target]');
        if (!counters.length) return;

        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function easeOut(t) { return 1 - Math.pow(1 - t, 3); }

        function animateCounter(el) {
            if (el.dataset.running) return;
            el.dataset.running = '1';

            var target  = parseInt(el.dataset.target, 10) || 0;
            var suffix  = el.dataset.suffix || '';
            var DURATION = reduced ? 0 : 1200;
            var start   = null;

            function step(ts) {
                if (!start) start = ts;
                var p = Math.min(1, (ts - start) / Math.max(DURATION, 1));
                var v = Math.round(easeOut(p) * target);
                el.textContent = v.toLocaleString() + suffix;
                if (p < 1) {
                    requestAnimationFrame(step);
                }
            }

            if (reduced) {
                el.textContent = target.toLocaleString() + suffix;
            } else {
                requestAnimationFrame(step);
            }
        }

        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        animateCounter(entry.target);
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.4 });

            counters.forEach(function (el) { io.observe(el); });
        } else {
            /* Fallback: run immediately */
            counters.forEach(animateCounter);
        }
    }());

}());
