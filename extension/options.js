/* Options page. Depends on src/api.js (window.TernisLink). */
document.addEventListener('DOMContentLoaded', async () => {
  const s = await window.TernisLink.getSettings();
  document.getElementById('apiKey').value = s.apiKey;
  document.getElementById('apiBase').value = s.apiBase;

  document.getElementById('save').onclick = async () => {
    const apiKey = document.getElementById('apiKey').value.trim();
    const apiBase = document.getElementById('apiBase').value.trim();
    // Changing the key invalidates the cached domain choice.
    await window.TernisLink.setSettings({ apiKey, apiBase, defaultDomainId: '' });
    const status = document.getElementById('status');
    if (apiKey) {
      try {
        const domains = await window.TernisLink.listDomains(
          apiBase || window.TernisLink.DEFAULT_API_BASE, apiKey,
        );
        status.textContent = `Saved ✓ — ${domains.length} domain(s) found.`;
      } catch (e) {
        status.textContent = `Saved, but key check failed: ${window.TernisLink.friendlyError(e)}`;
      }
    } else {
      status.textContent = 'Saved ✓ — guest mode (href.nz).';
    }
  };
});
