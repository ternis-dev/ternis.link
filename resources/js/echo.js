/* Live dashboard updates over the Pusher protocol (self-hosted Soketi).
 *
 * Runtime config comes from a `#tl-echo-config` JSON blob rendered by
 * the app layout for signed-in users (ws host, key, cluster). Absent
 * config or libraries → silently disabled; every Livewire flow works
 * unchanged without a socket (tests, local dev, offline).
 *
 * Subscribes to the user's private `dashboard.{id}` channel. Incoming
 * `link.changed` events toast and ask listening tables to refresh
 * (Livewire `dashboard-link-changed` → `$refresh`). Own actions are
 * excluded server-side (toOthers + X-Socket-ID), so no echo of self.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

function readConfig() {
    const el = document.getElementById('tl-echo-config');
    if (!el) return null;

    try {
        const cfg = JSON.parse(el.textContent || '{}');
        if (!cfg.key || !cfg.wsHost || !cfg.userId) return null;

        return cfg;
    } catch {
        return null;
    }
}

function initEcho() {
    const cfg = readConfig();
    if (!cfg || typeof Echo === 'undefined' || typeof Pusher === 'undefined') return;

    window.Pusher = Pusher;

    const echo = new Echo({
        broadcaster: 'pusher',
        key: cfg.key,
        wsHost: cfg.wsHost,
        wsPort: cfg.wsPort ?? 443,
        wssPort: cfg.wssPort ?? 443,
        forceTLS: cfg.forceTLS ?? true,
        disableStats: true,
        enabledTransports: ['ws', 'wss'],
        cluster: cfg.cluster ?? 'eu',
        // Session-cookie auth against the page's own host; the socket
        // host only ever sees the signed auth payload, never cookies.
        authEndpoint: `${window.location.origin}/broadcasting/auth`,
    });

    window.Echo = echo;

    echo.private(`dashboard.${cfg.userId}`).listen('.link.changed', (event) => {
        const slug = event?.slug ?? 'a link';
        const action = event?.action ?? 'changed';

        if (typeof window.showToast === 'function') {
            window.showToast(`“${slug}” ${action} elsewhere — list refreshed.`, 'info', 5000);
        }

        if (window.Livewire) {
            window.Livewire.dispatch('dashboard-link-changed');
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEcho);
} else {
    initEcho();
}
