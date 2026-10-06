/* First-run product tour: `startTour(steps)` walks the user through
 * highlighted elements with a tooltip, Next/Back/Skip, ESC to skip,
 * and a localStorage flag per tour id so it runs once.
 *
 * Steps come from a `#tl-tour-steps` JSON blob (rendered by overview
 * pages): { id, autostart, theme, steps: [{target, title, body}] }.
 * `target` is a `[data-tour="…"]` hook; a missing target renders the
 * step centered. Replay via the `tl:tour-start` window event.
 */

function tourSeen(id) {
    try {
        return localStorage.getItem(`tl-tour-${id}`) === 'done';
    } catch {
        return true;
    }
}

function markTourSeen(id) {
    try {
        localStorage.setItem(`tl-tour-${id}`, 'done');
    } catch {
        /* private mode — tour just replays next visit */
    }
}

function startTour(config) {
    if (!config || !Array.isArray(config.steps) || config.steps.length === 0) return;

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const id = config.id || 'default';
    let index = 0;

    const overlay = document.createElement('div');
    overlay.className = 'tl-tour-overlay' + (config.theme === 'public' ? ' tl-tour--public' : '');
    overlay.setAttribute('aria-hidden', 'false');

    const ring = document.createElement('div');
    ring.className = 'tl-tour-ring';
    ring.setAttribute('aria-hidden', 'true');
    overlay.append(ring);

    const tip = document.createElement('div');
    tip.className = 'tl-tour-tip';
    tip.setAttribute('role', 'dialog');
    tip.setAttribute('aria-modal', 'false');
    tip.setAttribute('aria-live', 'polite');
    overlay.append(tip);

    document.body.append(overlay);
    document.body.classList.add('tl-tour-open');

    function target() {
        const step = config.steps[index];
        return step?.target ? document.querySelector(`[data-tour="${step.target}"]`) : null;
    }

    function place() {
        const step = config.steps[index];
        const el = target();

        tip.innerHTML =
            `<p class="tl-tour-kicker">${index + 1} of ${config.steps.length}</p>` +
            `<h2 class="tl-tour-title"></h2>` +
            `<p class="tl-tour-body"></p>` +
            `<div class="tl-tour-actions">` +
            `<button type="button" class="tl-tour-skip" data-act="skip">Skip tour</button>` +
            `<span class="tl-tour-spacer"></span>` +
            (index > 0 ? `<button type="button" class="tl-tour-btn" data-act="back">Back</button>` : '') +
            `<button type="button" class="tl-tour-btn tl-tour-next" data-act="next">${index === config.steps.length - 1 ? 'Done' : 'Next'}</button>` +
            `</div>`;
        tip.querySelector('.tl-tour-title').textContent = step.title;
        tip.querySelector('.tl-tour-body').textContent = step.body;

        if (!el) {
            ring.hidden = true;
            tip.classList.add('tl-tour-centered');
            return;
        }

        ring.hidden = false;
        tip.classList.remove('tl-tour-centered');
        el.scrollIntoView({ block: 'center', behavior: reduced ? 'auto' : 'smooth' });

        const r = el.getBoundingClientRect();
        const pad = 6;
        ring.style.left = `${r.left - pad + window.scrollX}px`;
        ring.style.top = `${r.top - pad + window.scrollY}px`;
        ring.style.width = `${r.width + pad * 2}px`;
        ring.style.height = `${r.height + pad * 2}px`;

        // Tooltip below the target, flipped above when space is short.
        tip.style.left = '';
        const below = r.bottom + 16 + tip.offsetHeight < window.innerHeight;
        tip.style.top = below
            ? `${r.bottom + 16 + window.scrollY}px`
            : `${Math.max(12, r.top - tip.offsetHeight - 16) + window.scrollY}px`;
    }

    function finish() {
        markTourSeen(id);
        document.body.classList.remove('tl-tour-open');
        overlay.remove();
        document.removeEventListener('keydown', onKey);
    }

    function onKey(event) {
        if (event.key === 'Escape') finish();
    }

    tip.addEventListener('click', (event) => {
        const act = event.target.closest('[data-act]')?.getAttribute('data-act');
        if (!act) return;

        if (act === 'skip') {
            finish();
        } else if (act === 'back' && index > 0) {
            index -= 1;
            place();
        } else if (act === 'next') {
            if (index >= config.steps.length - 1) {
                finish();
            } else {
                index += 1;
                place();
            }
        }
    });

    document.addEventListener('keydown', onKey);
    window.addEventListener('resize', place);
    place();

    const observer = new MutationObserver(() => {
        if (!document.body.contains(overlay)) observer.disconnect();
    });
}

function readTourConfig() {
    const el = document.getElementById('tl-tour-steps');
    if (!el) return null;

    try {
        const cfg = JSON.parse(el.textContent || '{}');
        return cfg && Array.isArray(cfg.steps) ? cfg : null;
    } catch {
        return null;
    }
}

function maybeAutostart() {
    const cfg = readTourConfig();
    if (!cfg) return;

    window.__tlTourConfig = cfg;

    if (cfg.autostart && !tourSeen(cfg.id || 'default')) {
        // Let the page (and Livewire) settle before spotlighting.
        setTimeout(() => startTour(cfg), 600);
    }
}

window.startTour = startTour;

document.addEventListener('livewire:navigated', maybeAutostart);
window.addEventListener('tl:tour-start', () => {
    const cfg = window.__tlTourConfig || readTourConfig();
    if (cfg) {
        window.__tlTourConfig = cfg;
        startTour(cfg);
    }
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', maybeAutostart);
} else {
    maybeAutostart();
}
