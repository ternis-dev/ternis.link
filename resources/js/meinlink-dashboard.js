document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy]');
    if (!button) return;
    const original = button.textContent;
    try {
        await navigator.clipboard.writeText(button.dataset.copy);
        button.textContent = 'Kopiert ✓';
        button.setAttribute('aria-live', 'polite');
        setTimeout(() => { button.textContent = original; }, 2200);
    } catch {
        button.textContent = 'Bitte manuell kopieren';
        const input = document.createElement('input');
        input.value = button.dataset.copy;
        input.readOnly = true;
        input.setAttribute('aria-label', 'Kurzlink zum Kopieren');
        button.after(input);
        input.focus();
        input.select();
    }
});
