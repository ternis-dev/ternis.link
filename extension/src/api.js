/* Shared API + settings helpers for popup / background / options.
 * No build step: loaded as a classic script, exposes globalThis.TernisLink
 * (globalThis — never window — so it also evaluates in the MV3 service
 * worker, where window does not exist). */
(function () {
  const DEFAULT_API_BASE = 'https://links.t-api.de/v1';

  function normalizeBase(v) {
    const s = String(v || '').trim().replace(/\/+$/, '');
    return s || DEFAULT_API_BASE;
  }

  async function getSettings() {
    const s = await chrome.storage.sync.get({
      apiKey: '',
      apiBase: DEFAULT_API_BASE,
      defaultDomainId: '',
    });
    return { apiKey: s.apiKey.trim(), apiBase: normalizeBase(s.apiBase), defaultDomainId: s.defaultDomainId };
  }

  async function setSettings(patch) {
    if (patch.apiBase !== undefined) patch.apiBase = normalizeBase(patch.apiBase);
    await chrome.storage.sync.set(patch);
  }

  function shortUrlOf(link) {
    if (link.short_url) return link.short_url;
    const host = link.domain?.hostname || 'href.nz';
    return `https://${host}/${link.slug}`;
  }

  async function apiFetch(apiBase, apiKey, path, opts = {}) {
    const res = await fetch(`${apiBase}${path}`, {
      ...opts,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(apiKey ? { Authorization: `Bearer ${apiKey}` } : {}),
        ...(opts.headers || {}),
      },
    });
    const text = await res.text();
    let data = null;
    try { data = text ? JSON.parse(text) : null; } catch { data = { message: text }; }
    if (!res.ok) {
      const err = new Error(data?.message || `Request failed (${res.status})`);
      err.status = res.status;
      err.data = data;
      throw err;
    }
    return data;
  }

  async function listDomains(apiBase, apiKey) {
    const data = await apiFetch(apiBase, apiKey, '/domains');
    return data.data || data || [];
  }

  /** Shorten a URL. Auth mode when apiKey is set, guest mode otherwise. */
  async function shorten(destinationUrl, { slug = '' } = {}) {
    const { apiKey, apiBase, defaultDomainId } = await getSettings();
    const s = String(slug || '').trim();

    if (apiKey) {
      let domainId = defaultDomainId;
      if (!domainId) {
        const domains = await listDomains(apiBase, apiKey);
        const first = domains.find((d) => d.hostname === 'href.nz') || domains[0];
        if (!first) throw new Error('No domains available for this API key.');
        domainId = first.id;
      }
      const body = { destination_url: destinationUrl, domain_id: domainId };
      if (s) body.slug = s;
      const link = await apiFetch(apiBase, apiKey, '/links', { method: 'POST', body: JSON.stringify(body) });
      const payload = link.data || link;
      // Attach hostname when the API only returns domain_id.
      if (!payload.domain && domainId) {
        try {
          const domains = await listDomains(apiBase, apiKey);
          payload.domain = domains.find((d) => d.id === domainId) || null;
        } catch { /* keep short_url fallback */ }
      }
      return { link: payload, shortUrl: shortUrlOf(payload), mode: 'key' };
    }

    if (s) throw new Error('Custom slugs need an API key — add one in settings or leave the slug empty.');
    const link = await apiFetch(apiBase, '', '/links/public', {
      method: 'POST',
      body: JSON.stringify({ destination_url: destinationUrl }),
    });
    const payload = link.data || link;
    return { link: payload, shortUrl: shortUrlOf(payload), mode: 'guest' };
  }

  async function pushHistory(entry) {
    const { history = [] } = await chrome.storage.local.get({ history: [] });
    history.unshift({ ...entry, at: Date.now() });
    await chrome.storage.local.set({ history: history.slice(0, 10) });
  }

  function friendlyError(err) {
    if (err?.status === 401) return 'Invalid API key — check settings (keys start with tl_).';
    if (err?.status === 429) return 'Rate limited — wait a moment and retry.';
    if (err?.status === 422 && err?.data?.errors) {
      const first = Object.values(err.data.errors)[0];
      return Array.isArray(first) ? first[0] : String(first);
    }
    return err?.message || 'Something went wrong.';
  }

  globalThis.TernisLink = {
    DEFAULT_API_BASE, getSettings, setSettings, listDomains, shorten, pushHistory, friendlyError, shortUrlOf,
  };
})();
