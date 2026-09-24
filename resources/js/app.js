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
