/* Popup: shorten the active tab. Depends on src/api.js (window.TernisLink). */
(function () {
  const $ = (id) => document.getElementById(id);
  const api = () => window.TernisLink;

  async function currentTabUrl() {
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    return tab?.url || '';
  }

  function showError(msg) {
    const el = $('error');
    el.hidden = !msg;
    el.textContent = msg || '';
  }

  function showResult(shortUrl) {
    $('result').hidden = false;
    const a = $('shortUrl');
    a.href = shortUrl;
    a.textContent = shortUrl;
    $('qrImg').hidden = true;
    $('qrImg').removeAttribute('src');
  }

  async function renderHistory() {
    const { history = [] } = await chrome.storage.local.get({ history: [] });
    const ul = $('history');
    ul.innerHTML = '';
    if (!history.length) {
      ul.innerHTML = '<li><span style="opacity:.6">Nothing yet.</span></li>';
      return;
    }
    for (const h of history) {
      const li = document.createElement('li');
      const a = document.createElement('a');
      a.href = h.shortUrl; a.target = '_blank'; a.rel = 'noopener';
      a.textContent = h.shortUrl; a.title = h.destination || h.shortUrl;
      const t = document.createElement('time');
      t.textContent = new Date(h.at).toLocaleDateString();
      li.append(a, t);
      ul.append(li);
    }
  }

  async function loadDomains(settings) {
    const sel = $('domain');
    if (!settings.apiKey) {
      $('authFields').hidden = true;
      $('guestNote').hidden = false;
      $('modeBadge').textContent = 'guest';
      return;
    }
    $('authFields').hidden = false;
    $('guestNote').hidden = true;
    $('modeBadge').textContent = 'key';
    sel.innerHTML = '<option value="">Loading…</option>';
    try {
      const domains = await api().listDomains(settings.apiBase, settings.apiKey);
      sel.innerHTML = '';
      for (const d of domains) {
        const o = document.createElement('option');
        o.value = d.id;
        o.textContent = d.hostname + (d.user_id ? '' : ' (system)');
        if (d.id === settings.defaultDomainId) o.selected = true;
        sel.append(o);
      }
      if (!sel.value && sel.options.length) {
        sel.selectedIndex = 0;
        await api().setSettings({ defaultDomainId: sel.value });
      }
    } catch (e) {
      sel.innerHTML = '<option value="">Could not load domains</option>';
      showError(api().friendlyError(e));
    }
  }

  document.addEventListener('DOMContentLoaded', async () => {
    const settings = await api().getSettings();
    $('url').value = await currentTabUrl();
    await loadDomains(settings);
    await renderHistory();

    $('optionsLink').onclick = (e) => { e.preventDefault(); chrome.runtime.openOptionsPage(); };
    $('guestOptions').onclick = (e) => { e.preventDefault(); chrome.runtime.openOptionsPage(); };
    $('refreshDomains').onclick = async () => loadDomains(await api().getSettings());
    $('domain').onchange = async (e) => api().setSettings({ defaultDomainId: e.target.value });

    $('shorten').onclick = async () => {
      showError('');
      const btn = $('shorten');
      const destination = $('url').value.trim();
      const slug = $('slug')?.value || '';
      if (!/^https?:\/\//i.test(destination)) {
        showError('Enter a full http(s) URL.');
        return;
      }
      btn.disabled = true;
      try {
        const domainSel = $('domain')?.value;
        if (domainSel && (await api().getSettings()).apiKey) {
          await api().setSettings({ defaultDomainId: domainSel });
        }
        const { shortUrl, mode } = await api().shorten(destination, { slug });
        showResult(shortUrl);
        await api().pushHistory({ shortUrl, destination, mode });
        await renderHistory();
        await navigator.clipboard.writeText(shortUrl).catch(() => {});
      } catch (e) {
        showError(api().friendlyError(e));
      } finally {
        btn.disabled = false;
      }
    };

    $('copy').onclick = async () => {
      const v = $('shortUrl').textContent;
      await navigator.clipboard.writeText(v).catch(() => {});
      $('copy').textContent = 'Copied ✓';
      setTimeout(() => ($('copy').textContent = 'Copy'), 1200);
    };

    $('qr').onclick = async () => {
      const shortUrl = $('shortUrl').textContent;
      const { apiBase } = await api().getSettings();
      const base = apiBase.replace(/\/v1$/, '');
      // Public QR endpoint needs no auth: GET /v1/qr?url=… (SVG default).
      const img = $('qrImg');
      img.src = `${apiBase}/qr?url=${encodeURIComponent(shortUrl)}`;
      img.hidden = false;
    };
  });
})();
