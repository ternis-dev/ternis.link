/* ==========================================================
 * href.yt — custom JS
 * Preloader, scroll-counter, creator presets, simulator tabs,
 * keyboard shortcuts, and interactive creator utilities.
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

    /* ── Creator URL Presets ────────────────────────────────
     * Fills the destination input and dispatches input event
     * so Livewire updates the model reactively.              */
    (function initPresets() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-preset-url]');
            if (!btn) return;

            var url = btn.getAttribute('data-preset-url');
            if (!url) return;

            var input = document.getElementById('public_destination_url');
            if (!input) return;

            input.value = url;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.focus();

            // Smooth scroll to form if not in view
            var formZone = document.getElementById('shorten') || input;
            if (formZone && typeof formZone.scrollIntoView === 'function') {
                formZone.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }());

    /* ── Live Preview Simulator Tabs ────────────────────────
     * Switches between Description, Pinned Comment, and Outro */
    (function initSimulator() {
        document.addEventListener('click', function (e) {
            var tab = e.target.closest('[data-sim-tab]');
            if (!tab) return;

            var tabs = document.querySelectorAll('.yt-sim-tab');
            var targetId = tab.getAttribute('aria-controls');
            var views = document.querySelectorAll('.yt-sim-view');

            tabs.forEach(function (t) {
                var isActive = (t === tab);
                t.classList.toggle('is-active', isActive);
                t.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            views.forEach(function (v) {
                if (v.id === targetId) {
                    v.classList.add('is-active');
                    v.hidden = false;
                } else {
                    v.classList.remove('is-active');
                    v.hidden = true;
                }
            });
        });
    }());

    /* ── Keyboard Shortcuts ─────────────────────────────────
     * Press '/' to focus link input from anywhere.           */
    (function initKeyShortcuts() {
        document.addEventListener('keydown', function (e) {
            // Ignore if active element is an input, textarea, or contenteditable
            var active = document.activeElement;
            var isInput = active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.isContentEditable);

            if (e.key === '/' && !isInput && !e.ctrlKey && !e.metaKey && !e.altKey) {
                var input = document.getElementById('public_destination_url');
                if (input) {
                    e.preventDefault();
                    input.focus();
                    input.select();
                }
            } else if (e.key === 'Escape' && isInput) {
                active.blur();
            }
        });
    }());

}());
