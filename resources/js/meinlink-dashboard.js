const copyTimers = new WeakMap();

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy]');
    if (!button) return;
    const textSpan = button.querySelector('span') || button;
    const original = button.dataset.copyLabel ??= textSpan.textContent;
    clearTimeout(copyTimers.get(button));
    try {
        await navigator.clipboard.writeText(button.dataset.copy);
        textSpan.textContent = 'Kopiert ✓';
        button.setAttribute('aria-live', 'polite');
        button.parentElement.querySelector('[data-copy-fallback]')?.remove();
        copyTimers.set(button, setTimeout(() => { textSpan.textContent = original; }, 2200));
    } catch {
        textSpan.textContent = 'Bitte manuell kopieren';
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
        if (!input) return;
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.textContent = visible ? 'Passwort verbergen' : 'Passwort anzeigen';
    });
}
