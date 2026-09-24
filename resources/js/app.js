/* Theme toggle: `.dark` on <html>, persisted in localStorage.
 * First paint is handled by an inline head script (see layouts/app);
 * this file wires up every `[data-theme-toggle]` button. */

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-theme-toggle]');
    if (!button) return;

    const dark = document.documentElement.classList.toggle('dark');
    try {
        localStorage.setItem('tl-theme', dark ? 'dark' : 'light');
    } catch {
        /* private mode — theme just won't persist */
    }
});
