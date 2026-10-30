/**
 * Frontend caching, pre-loading, and live Web API sync for stats pages.
 *
 * Features:
 * - Instant client-side tab navigation between Overview, Activity, Security, Domains
 * - Background pre-loading and in-memory/session caching of stats pages
 * - Periodic background polling via Web API (/pages/stats/data) every ~45s (tailored by user type)
 * - In-place DOM and Chart.js updates as soon as updates come in
 * - Smart visibility detection: pauses polling when tab is inactive, resumes immediately on focus
 * - HTTP ETag / 304 Not Modified cache validation to save bandwidth and server resources
 */

const STORAGE_DATA_KEY = 'tl_stats_data_v1';
const STORAGE_ETAG_KEY = 'tl_stats_etag_v1';
const STORAGE_TIME_KEY = 'tl_stats_time_v1';

const STATS_PAGES = [
    '/pages/stats',
    '/pages/stats/activity',
    '/pages/stats/security',
    '/pages/stats/domains',
];

const pageHtmlCache = new Map();
let pollTimer = null;
let currentPollInterval = 45; // seconds
let lastPollTime = Date.now();
let isFetching = false;
let currentEtag = null;
let liveData = null;

function isStatsPage(pathname = window.location.pathname) {
    const clean = pathname.replace(/\/+$/, '') || '/';
    return clean === '/pages/stats' || clean.startsWith('/pages/stats/');
}

function normalizePath(url) {
    try {
        const parsed = new URL(url, window.location.origin);
        return parsed.pathname.replace(/\/+$/, '') || '/';
    } catch {
        return url;
    }
}

/**
 * Format relative time string ("just now", "12s ago", "2m ago").
 */
function formatRelativeTime(timestamp) {
    const diff = Math.max(0, Math.floor((Date.now() - timestamp) / 1000));
    if (diff < 5) return 'just now';
    if (diff < 60) return `${diff}s ago`;
    const mins = Math.floor(diff / 60);
    return `${mins}m ago`;
}

/**
 * Update the live sync status indicator in the nav banner.
 */
function updateStatusIndicator(status = 'active') {
    const statusEl = document.querySelector('[data-stats-status]');
    if (!statusEl) return;

    const indicator = statusEl.querySelector('[data-stats-indicator]');
    const lastUpdated = statusEl.querySelector('[data-stats-last-updated]');
    const pollNote = statusEl.querySelector('[data-stats-poll-note]');

    if (indicator) {
        if (status === 'paused') {
            indicator.className = 'inline-flex h-2 w-2 rounded-full bg-amber-400';
        } else if (status === 'offline') {
            indicator.className = 'inline-flex h-2 w-2 rounded-full bg-neutral-400';
        } else {
            indicator.className = 'inline-flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse';
        }
    }

    if (lastUpdated) {
        if (status === 'paused') {
            lastUpdated.textContent = 'Paused (tab inactive)';
        } else if (status === 'offline') {
            lastUpdated.textContent = 'Offline';
        } else {
            const timeStr = formatRelativeTime(lastPollTime);
            lastUpdated.textContent = `Updated ${timeStr}`;
        }
    }

    if (pollNote && currentPollInterval) {
        pollNote.textContent = `Updates every ${currentPollInterval}s`;
    }
}

/**
 * Pre-load a single stats page into memory and sessionStorage.
 */
async function preloadPage(url) {
    const path = normalizePath(url);
    if (pageHtmlCache.has(path)) return;

    try {
        const response = await fetch(path, {
            headers: {
                'X-Stats-Preload': 'true',
            },
        });

        if (!response.ok) return;

        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const content = doc.getElementById('content');

        if (content) {
            const pageData = {
                title: doc.title || 'Network Stats — ternis.link',
                html: content.innerHTML,
                pathname: path,
            };
            pageHtmlCache.set(path, pageData);
            try {
                sessionStorage.setItem(`tl_stats_page_${path}`, JSON.stringify(pageData));
            } catch {
                /* sessionStorage quota or private mode */
            }
        }
    } catch {
        /* network error during background prefetch — ignore */
    }
}

/**
 * Pre-load all sibling stats pages in the background.
 */
function preloadSiblingPages() {
    // Check sessionStorage first to restore any preloaded pages
    STATS_PAGES.forEach((path) => {
        if (!pageHtmlCache.has(path)) {
            try {
                const stored = sessionStorage.getItem(`tl_stats_page_${path}`);
                if (stored) {
                    pageHtmlCache.set(path, JSON.parse(stored));
                }
            } catch {
                /* ignore */
            }
        }
    });

    // Cache current page if not already cached
    const currentPath = normalizePath(window.location.pathname);
    const content = document.getElementById('content');
    if (content && !pageHtmlCache.has(currentPath)) {
        pageHtmlCache.set(currentPath, {
            title: document.title,
            html: content.innerHTML,
            pathname: currentPath,
        });
    }

    // Prefetch un-cached sibling pages when browser is idle
    const prefetchRemaining = () => {
        STATS_PAGES.forEach((path) => {
            if (path !== currentPath && !pageHtmlCache.has(path)) {
                preloadPage(path);
            }
        });
    };

    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(prefetchRemaining, { timeout: 2000 });
    } else {
        setTimeout(prefetchRemaining, 800);
    }
}

/**
 * Instant client-side navigation between stats tabs.
 */
async function navigateToStatsPage(targetUrl, pushState = true) {
    const targetPath = normalizePath(targetUrl);
    const currentPath = normalizePath(window.location.pathname);

    if (targetPath === currentPath) return;

    let pageData = pageHtmlCache.get(targetPath);

    if (!pageData) {
        // Not in memory, try sessionStorage
        try {
            const stored = sessionStorage.getItem(`tl_stats_page_${targetPath}`);
            if (stored) pageData = JSON.parse(stored);
        } catch {
            /* ignore */
        }
    }

    if (!pageData) {
        // Fetch immediately
        try {
            const response = await fetch(targetPath);
            if (!response.ok) {
                window.location.href = targetUrl;
                return;
            }
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const content = doc.getElementById('content');
            if (!content) {
                window.location.href = targetUrl;
                return;
            }
            pageData = {
                title: doc.title,
                html: content.innerHTML,
                pathname: targetPath,
            };
            pageHtmlCache.set(targetPath, pageData);
        } catch {
            window.location.href = targetUrl;
            return;
        }
    }

    const content = document.getElementById('content');
    if (!content) {
        window.location.href = targetUrl;
        return;
    }

    // Perform DOM transition
    content.innerHTML = pageData.html;
    document.title = pageData.title;

    if (pushState) {
        window.history.pushState({ statsUrl: targetUrl }, '', targetUrl);
    }

    // Update active tab in nav
    updateNavActiveTab(targetPath);

    // Initialize Alpine on the freshly swapped tree if present
    if (window.Alpine) {
        try {
            window.Alpine.initTree(content);
        } catch {
            /* ignore */
        }
    }

    // Re-initialize Chart.js instances
    if (window.tlInitCharts) {
        window.tlInitCharts(content);
    }

    // Re-apply latest live data to the newly mounted view
    if (liveData) {
        applyStatsData(liveData);
    }

    // Scroll to top of content smoothly
    window.scrollTo({ top: 0, behavior: 'instant' });
}

/**
 * Update the active visual state in <x-stats.nav />.
 */
function updateNavActiveTab(activePath) {
    const nav = document.querySelector('[data-stats-nav]');
    if (!nav) return;

    const links = nav.querySelectorAll('a[data-stats-tab]');
    links.forEach((a) => {
        const href = normalizePath(a.getAttribute('href'));
        const isActive = href === activePath;

        if (isActive) {
            a.setAttribute('aria-current', 'page');
            a.className = 'rounded-lg px-4 py-2 text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 bg-neutral-900 text-white dark:bg-white dark:text-neutral-900';
        } else {
            a.removeAttribute('aria-current');
            a.className = 'rounded-lg px-4 py-2 text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800';
        }
    });
}

/**
 * Update a single stat KPI number with subtle pulse animation.
 */
function updateStatValue(key, numericValue) {
    const formatted = key === 'threats_avg_score' ? `${numericValue}/100` : Number(numericValue).toLocaleString();
    const selectors = [
        `[data-stat-value="${key}"]`,
        `[data-stat-key="${key}"] [data-stat-value]`,
        `[data-stat-key="${key}"] .tabular-nums`,
    ];

    const elements = document.querySelectorAll(selectors.join(', '));
    elements.forEach((el) => {
        const current = el.textContent.trim();
        if (current !== formatted) {
            el.textContent = formatted;
            el.classList.remove('tl-stat-pulse');
            // Trigger reflow to restart CSS animation
            void el.offsetWidth;
            el.classList.add('tl-stat-pulse');
        }
    });
}

/**
 * Apply live stats payload to DOM elements across all subpages.
 */
function applyStatsData(data) {
    if (!data) return;

    // 1. Overview KPIs
    if (data.overview) {
        for (const [key, val] of Object.entries(data.overview)) {
            updateStatValue(key, val);
        }
    }

    // 2. Activity Page KPIs and Daily Table
    if (data.activity) {
        const { links, clicks, qr } = data.activity;
        if (links?.values) {
            const sum = links.values.reduce((a, b) => a + Number(b), 0);
            updateStatValue('activity_links', sum);
        }
        if (clicks?.values) {
            const sum = clicks.values.reduce((a, b) => a + Number(b), 0);
            updateStatValue('activity_clicks', sum);
        }
        if (qr?.values) {
            const sum = qr.values.reduce((a, b) => a + Number(b), 0);
            updateStatValue('activity_qr', sum);
        }

        const tbody = document.querySelector('tbody[data-stats-activity-table]');
        if (tbody && links?.labels) {
            links.labels.forEach((day, idx) => {
                const row = tbody.querySelector(`tr[data-row-day="${day}"]`);
                if (row) {
                    const lCell = row.querySelector('[data-col="links"]');
                    const cCell = row.querySelector('[data-col="clicks"]');
                    const qCell = row.querySelector('[data-col="qr"]');
                    if (lCell && links.values[idx] !== undefined) lCell.textContent = Number(links.values[idx]).toLocaleString();
                    if (cCell && clicks?.values?.[idx] !== undefined) cCell.textContent = Number(clicks.values[idx]).toLocaleString();
                    if (qCell && qr?.values?.[idx] !== undefined) qCell.textContent = Number(qr.values[idx]).toLocaleString();
                }
            });
        }
    }

    // 3. Domains Page Table and Canvases
    if (data.domains && Array.isArray(data.domains)) {
        const tbody = document.querySelector('tbody[data-stats-domains-table]');
        if (tbody) {
            data.domains.forEach((d) => {
                const row = tbody.querySelector(`tr[data-domain="${d.hostname}"]`);
                if (row) {
                    const lCell = row.querySelector('[data-col="links"]');
                    const cCell = row.querySelector('[data-col="clicks"]');
                    if (lCell) lCell.textContent = Number(d.links_count).toLocaleString();
                    if (cCell) cCell.textContent = Number(d.links_sum_click_count).toLocaleString();
                }
            });
        }

        const linksCanvas = document.querySelector('canvas[data-chart-name="domain-links"]');
        const clicksCanvas = document.querySelector('canvas[data-chart-name="domain-clicks"]');

        if (linksCanvas || clicksCanvas) {
            const labels = data.domains.map((d) => d.hostname);
            const linkVals = data.domains.map((d) => d.links_count);
            const clickVals = data.domains.map((d) => d.links_sum_click_count);
            const colorsLight = data.domains.map((d) => d.color?.light || '#2563eb');
            const colorsDark = data.domains.map((d) => d.color?.dark || '#60a5fa');

            if (linksCanvas) {
                linksCanvas.dataset.chartLabels = JSON.stringify(labels);
                linksCanvas.dataset.chartValues = JSON.stringify(linkVals);
                linksCanvas.dataset.chartColorsLight = JSON.stringify(colorsLight);
                linksCanvas.dataset.chartColorsDark = JSON.stringify(colorsDark);
                if (window.tlInitCharts) window.tlInitCharts(linksCanvas);
            }
            if (clicksCanvas) {
                clicksCanvas.dataset.chartLabels = JSON.stringify(labels);
                clicksCanvas.dataset.chartValues = JSON.stringify(clickVals);
                clicksCanvas.dataset.chartColorsLight = JSON.stringify(colorsLight);
                clicksCanvas.dataset.chartColorsDark = JSON.stringify(colorsDark);
                if (window.tlInitCharts) window.tlInitCharts(clicksCanvas);
            }
        }
    }

    // 4. Multi-series Charts on Overview Page
    if (data.charts) {
        ['creations', 'clicks', 'qr'].forEach((name) => {
            const canvas = document.querySelector(`canvas[data-chart-name="${name}"]`);
            if (canvas && data.charts[name]) {
                const series = data.charts[name];
                canvas.dataset.chartLabels = JSON.stringify(series.labels);
                canvas.dataset.chartDatasets = JSON.stringify(series.datasets);
                if (window.tlInitCharts) {
                    window.tlInitCharts(canvas);
                }
            }
        });
    }

    // 5. Heatmap Tab Badges
    if (data.heatmaps?.summaries) {
        for (const [mKey, summary] of Object.entries(data.heatmaps.summaries)) {
            const badge = document.querySelector(`[data-heatmap-tab-total="${mKey}"]`);
            if (badge && summary.calendarTotal !== undefined) {
                badge.textContent = Number(summary.calendarTotal).toLocaleString();
            }
        }
    }

    // 6. Security Page Breakdown Bars & Triage KPIs
    const securityBreakdown = data.security?.breakdown || data.breakdown;
    if (securityBreakdown?.categories) {
        for (const [catKey, cat] of Object.entries(securityBreakdown.categories)) {
            const bar = document.querySelector(`[data-cat-bar="${catKey}"]`);
            if (bar) {
                bar.style.width = `${Math.max(1, cat.percent)}%`;
                bar.title = `${cat.label}: ${cat.percent}% (${Number(cat.count).toLocaleString()})`;
            }
            const percentEl = document.querySelector(`[data-cat-percent="${catKey}"]`);
            if (percentEl) {
                percentEl.textContent = `${cat.percent}%`;
            }
            const countEl = document.querySelector(`[data-cat-count="${catKey}"]`);
            if (countEl) {
                countEl.textContent = Number(cat.count).toLocaleString();
            }
        }
    }
}

/**
 * Fetch the latest stats from the Web API endpoint (/pages/stats/data).
 */
async function fetchLatestStats() {
    if (!isStatsPage() || document.hidden || !navigator.onLine || isFetching) {
        return;
    }

    isFetching = true;

    try {
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        if (currentEtag) {
            headers['If-None-Match'] = currentEtag;
        }

        const response = await fetch('/pages/stats/data', { headers });

        if (response.status === 304) {
            // Data has not changed
            lastPollTime = Date.now();
            updateStatusIndicator('active');
            return;
        }

        if (response.ok) {
            const data = await response.json();
            liveData = data;
            lastPollTime = Date.now();

            const etag = response.headers.get('ETag');
            if (etag) currentEtag = etag;

            if (data.meta?.poll_interval) {
                currentPollInterval = Number(data.meta.poll_interval);
            }

            // Save to localStorage cache
            try {
                localStorage.setItem(STORAGE_DATA_KEY, JSON.stringify(data));
                if (currentEtag) localStorage.setItem(STORAGE_ETAG_KEY, currentEtag);
                localStorage.setItem(STORAGE_TIME_KEY, String(lastPollTime));
            } catch {
                /* localStorage quota or private mode */
            }

            // Apply updates to DOM in-place
            applyStatsData(data);
            updateStatusIndicator('active');
        }
    } catch {
        /* Network error — will retry on next poll interval */
    } finally {
        isFetching = false;
        scheduleNextPoll();
    }
}

/**
 * Schedule the next poll timer.
 */
function scheduleNextPoll(delayMs) {
    if (pollTimer) clearTimeout(pollTimer);

    if (document.hidden || !isStatsPage()) {
        return;
    }

    const ms = delayMs ?? (currentPollInterval * 1000);
    pollTimer = setTimeout(fetchLatestStats, ms);
}

/**
 * Restore cached stats from localStorage if available.
 */
function restoreCachedStats() {
    try {
        const cachedRaw = localStorage.getItem(STORAGE_DATA_KEY);
        const cachedEtag = localStorage.getItem(STORAGE_ETAG_KEY);
        const cachedTime = localStorage.getItem(STORAGE_TIME_KEY);

        if (cachedEtag) currentEtag = cachedEtag;
        if (cachedTime) lastPollTime = Number(cachedTime);

        if (cachedRaw) {
            liveData = JSON.parse(cachedRaw);
            if (liveData.meta?.poll_interval) {
                currentPollInterval = Number(liveData.meta.poll_interval);
            }
            // Immediately apply cached stats to prevent layout shifts
            applyStatsData(liveData);
        }
    } catch {
        /* invalid JSON or storage disabled */
    }
}

/**
 * Initialize event listeners for stats client.
 */
let initialized = false;

export function initStats() {
    if (!isStatsPage()) {
        if (pollTimer) clearTimeout(pollTimer);
        return;
    }

    restoreCachedStats();
    preloadSiblingPages();
    updateStatusIndicator('active');
    scheduleNextPoll();

    if (initialized) return;
    initialized = true;

    // Relative time ticker (updates "Updated 15s ago" every 10 seconds)
    setInterval(() => {
        if (isStatsPage() && !document.hidden) {
            updateStatusIndicator('active');
        }
    }, 10000);

    // Hover-triggered prefetch on nav links
    document.addEventListener('pointerenter', (event) => {
        const a = event.target.closest('a[data-stats-tab], a[href^="/pages/stats"]');
        if (!a) return;
        const href = a.getAttribute('href');
        if (href && isStatsPage(href)) {
            preloadPage(href);
        }
    }, { capture: true, passive: true });

    // Intercept clicks on stats navigation links for instant switching
    document.addEventListener('click', (event) => {
        const a = event.target.closest('a[data-stats-tab], a[href^="/pages/stats"]');
        if (!a) return;

        // Skip modified clicks (Ctrl/Cmd/Shift/Alt/new tab)
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        if (a.target && a.target !== '_self') return;

        const href = a.getAttribute('href');
        if (!href || href.endsWith('.md')) return;

        const path = normalizePath(href);
        if (isStatsPage(path)) {
            event.preventDefault();
            navigateToStatsPage(path, true);
        }
    });

    // Handle browser back/forward buttons (popstate)
    window.addEventListener('popstate', () => {
        if (isStatsPage()) {
            navigateToStatsPage(window.location.pathname, false);
        }
    });

    // Visibility change handler: pause when tab is hidden, resume when visible
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            if (pollTimer) clearTimeout(pollTimer);
            updateStatusIndicator('paused');
        } else {
            updateStatusIndicator('active');
            const elapsed = Date.now() - lastPollTime;
            if (elapsed >= currentPollInterval * 1000) {
                fetchLatestStats();
            } else {
                scheduleNextPoll((currentPollInterval * 1000) - elapsed);
            }
        }
    });

    // Online / offline handling
    window.addEventListener('online', () => {
        updateStatusIndicator('active');
        fetchLatestStats();
    });

    window.addEventListener('offline', () => {
        if (pollTimer) clearTimeout(pollTimer);
        updateStatusIndicator('offline');
    });
}

// Auto-run on DOMContentLoaded or Livewire page navigation
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStats);
} else {
    initStats();
}

document.addEventListener('livewire:navigated', initStats);
