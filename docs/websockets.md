# WebSockets (live dashboards)

Link tables refresh live when a link is created or deactivated in another tab: the app broadcasts `DashboardLinkChanged` on the private `dashboard.{userId}` channel, and `resources/js/echo.js` refreshes the table plus a toast. Analytics clicks never broadcast — the redirect hot path stays untouched.

## Stack

- Transport: **Soketi** (self-hosted, Pusher protocol) at `wss://ws.ternis.link`, proxied by Caddy to `127.0.0.1:6001`.
- Server SDK: `pusher/pusher-php-server` via Laravel's `pusher` broadcast driver (`config/broadcasting.php`).
- Client: `laravel-echo` + `pusher-js` (`resources/js/echo.js`), config rendered at runtime by the app layout — no rebuild coupling.
- Auth: private channels authorize the owning user only (`routes/channels.php`), over the page's own `/broadcasting/auth` (session cookie, same origin). The socket host never sees cookies.

Why Soketi and not Reverb: Reverb v1 requires `guzzlehttp/psr7 ^2.6`, which conflicts with the locked guzzle 8 (`psr7 ^3.1`). Revisit when Reverb supports psr7 3.

## Server setup

1. Install Soketi alongside the app (Node 18+ required):
   ```
   npm i -g @soketi/soketi
   ```
2. Caddy already proxies `ws.ternis.link` (see `Caddyfile`). DNS `ws.ternis.link` at the server.
3. Environment (`/var/www/ternis-link/.env`):
   ```
   BROADCAST_CONNECTION=pusher
   PUSHER_APP_ID=ternis-link
   PUSHER_APP_KEY=<random-32>
   PUSHER_APP_SECRET=<random-64>
   PUSHER_HOST=ws.ternis.link
   PUSHER_PORT=443
   PUSHER_SCHEME=https
   PUSHER_APP_CLUSTER=eu
   ```
   The same key/secret go into Soketi's config (`SOKETI_DEFAULT_APP_*` or `config.json`).
4. Supervise Soketi (systemd):
   ```
   [Unit]
   Description=Soketi websocket server
   After=network.target

   [Service]
   ExecStart=/usr/bin/soketi start --port=6001
   Environment=SOKETI_DEFAULT_APP_ID=ternis-link
   Environment=SOKETI_DEFAULT_APP_KEY=<same-as-env>
   Environment=SOKETI_DEFAULT_APP_SECRET=<same-as-env>
   Restart=always

   [Install]
   WantedBy=multi-user.target
   ```
5. Broadcasts queue through the default queue connection (`sync` works — one localhost HTTP call per link event; move to redis if it ever shows up in slow-request logs).

## Graceful degradation

Everything works without a socket server: with `BROADCAST_CONNECTION=null` (dev, tests) broadcasts no-op, and `echo.js` stays inert when the layout renders no config or Soketi is unreachable (pusher-js reconnects with backoff; Livewire flows never depend on it).
