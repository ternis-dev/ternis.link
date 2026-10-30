const copyTimers = new WeakMap();
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy]');
    if (!button) return;
    const original = button.dataset.copyLabel ??= button.textContent;
    clearTimeout(copyTimers.get(button));
    try {
        await navigator.clipboard.writeText(button.dataset.copy);
        button.textContent = 'Kopiert ✓';
        button.setAttribute('aria-live', 'polite');
        button.parentElement.querySelector('[data-copy-fallback]')?.remove();
        copyTimers.set(button, setTimeout(() => { button.textContent = original; }, 2200));
    } catch {
        button.textContent = 'Bitte manuell kopieren';
        let input = button.parentElement.querySelector('[data-copy-fallback]');
        if (!input) {
            input = document.createElement('input');
            input.dataset.copyFallback = '';
            input.className = 'copy-fallback';
            input.readOnly = true;
            input.setAttribute('aria-label', 'Kurzlink zum Kopieren');
            button.after(input);
        }
        input.value = button.dataset.copy;
        input.focus();
        input.select();
    }
});

for (const button of document.querySelectorAll('[data-password-toggle]')) {
    button.hidden = false;
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.textContent = visible ? 'Passwort verbergen' : 'Passwort anzeigen';
    });
}
