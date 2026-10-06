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
