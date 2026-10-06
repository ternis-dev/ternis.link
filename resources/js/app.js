/* Theme toggle: `.dark` on <html>, persisted in localStorage.
 * First paint is handled by an inline head script (see layouts/app);
 * this file wires up every `[data-theme-toggle]` button. */

import './charts.js';
import { initCharts } from './charts.js';

function applyTheme(dark, persist = true) {
    document.documentElement.classList.toggle('dark', dark);

    if (persist) {
        try {
            localStorage.setItem('tl-theme', dark ? 'dark' : 'light');
        } catch {
            /* private mode — theme just won't persist */
        }
    }

    document.dispatchEvent(new CustomEvent('tl:theme-changed'));
    initCharts();
}

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-theme-toggle]');
    if (!button) return;

    applyTheme(!document.documentElement.classList.contains('dark'));
});

/* Server-side theme preference saved in Settings (detail: {theme} or [theme]). */
window.addEventListener('tl:theme-preference', (event) => {
    const detail = event.detail ?? {};
    const theme = detail.theme ?? detail[0] ?? 'system';

    try {
        if (theme === 'system') {
            localStorage.removeItem('tl-theme');
            applyTheme(matchMedia('(prefers-color-scheme: dark)').matches, false);
        } else {
            applyTheme(theme === 'dark');
        }
    } catch {
        /* private mode */
    }
});

/* Copy-to-clipboard: any `[data-copy]` button copies its value and
 * confirms inline ("Copied ✓" for 1.2s). Clipboard API with a
 * textarea fallback for non-secure contexts. */
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy]');
    if (!button || button.disabled) return;

    const text = button.getAttribute('data-copy') ?? '';

    try {
        await navigator.clipboard.writeText(text);
    } catch {
        const area = document.createElement('textarea');
        area.value = text;
        document.body.append(area);
        area.select();
        try {
            document.execCommand('copy');
        } catch {
            /* clipboard unavailable — leave the value selected */
        }
        area.remove();
    }

    if (!button.hasAttribute('data-copy-label')) {
        button.setAttribute('data-copy-label', button.textContent ?? '');
    }
    button.textContent = 'Copied ✓';
    setTimeout(() => {
        button.textContent = button.getAttribute('data-copy-label') ?? '';
    }, 1200);
});

/* Toasts: `showToast(message, type, duration, options)`.
 *
 *   showToast('Short link created.', 'success');
 *   showToast('Import finished.', 'info', 6000);
 *   showToast('Oops.', 'error', { title: 'Something failed', duration: 8000 });
 *   showToast.success('Done.'); // shortcuts: .success/.error/.info
 *
 * Types: success | error | info (anything else falls back to info).
 * Duration in ms (default 4000, error 6000); 0 disables auto-dismiss.
 * Renders into a lazily created bottom-right stack (aria-live polite,
 * max 4, hover pauses dismissal, close button, reduced-motion aware).
 * On public-dashboard pages (.pd present) the info accent is indigo.
 */
const TOAST_ICONS = {
    success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>',
    error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 6v9M12 18.5v.5"/></svg>',
    info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.5V8"/></svg>',
};

function toastContainer() {
    let el = document.getElementById('tl-toasts');
    if (el) return el;

    el = document.createElement('div');
    el.id = 'tl-toasts';
    el.className = 'tl-toasts';
    if (document.querySelector('.pd')) el.classList.add('tl-toasts--public');
    el.setAttribute('role', 'status');
    el.setAttribute('aria-live', 'polite');
    document.body.append(el);

    return el;
}

function showToast(message, type = 'info', duration, options = {}) {
    if (typeof duration === 'object' && duration !== null) {
        options = duration;
        duration = undefined;
    }

    const kind = ['success', 'error', 'info'].includes(type) ? type : 'info';
    const { title = null, duration: optDuration = null } = options;
    const ms = duration ?? optDuration ?? (kind === 'error' ? 6000 : 4000);
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const stack = toastContainer();
    while (stack.children.length >= 4) stack.firstElementChild?.remove();

    const el = document.createElement('div');
    el.className = `tl-toast tl-toast--${kind}`;
    el.innerHTML =
        `<span class="tl-toast-icon" aria-hidden="true">${TOAST_ICONS[kind]}</span>` +
        `<div class="tl-toast-body">` +
        (title ? `<p class="tl-toast-title"></p>` : '') +
        `<p class="tl-toast-message"></p>` +
        `</div>` +
        `<button type="button" class="tl-toast-close" aria-label="Dismiss notification">×</button>`;
    el.querySelector('.tl-toast-message').textContent = String(message ?? '');
    if (title) el.querySelector('.tl-toast-title').textContent = String(title);
    stack.append(el);

    let timer = null;
    const dismiss = () => {
        clearTimeout(timer);
        if (reduced) {
            el.remove();
        } else {
            el.classList.add('tl-toast--leaving');
            setTimeout(() => el.remove(), 180);
        }
    };
    el.querySelector('.tl-toast-close').addEventListener('click', dismiss);

    if (ms > 0 && !reduced) {
        const arm = () => {
            clearTimeout(timer);
            timer = setTimeout(dismiss, ms);
        };
        el.addEventListener('mouseenter', () => clearTimeout(timer));
        el.addEventListener('mouseleave', arm);
        arm();
    } else if (ms > 0 && reduced) {
        timer = setTimeout(dismiss, ms);
    }

    if (!reduced) {
        requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('tl-toast--in')));
    } else {
        el.classList.add('tl-toast--in');
    }

    return dismiss;
}

showToast.success = (message, duration, options) => showToast(message, 'success', duration, options);
showToast.error = (message, duration, options) => showToast(message, 'error', duration, options);
showToast.info = (message, duration, options) => showToast(message, 'info', duration, options);

window.showToast = showToast;

/* Livewire bridge: components dispatch `notify` (message + type) and
 * creations announce `link-created` — both surface as toasts. */
document.addEventListener('livewire:init', () => {
    window.Livewire.on('notify', (data) => {
        const payload = Array.isArray(data) ? data[0] : data;
        showToast(payload?.message ?? 'Done.', payload?.type ?? 'info');
    });
    window.Livewire.on('link-created', () => showToast('Short link created.', 'success'));
});
