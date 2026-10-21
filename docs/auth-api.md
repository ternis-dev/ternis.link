# Auth API (cross-origin clients)

The public dashboard ([my.ternis.link](https://my.ternis.link), [my.href.nz](https://my.href.nz)) exposes a small, CORS-protected auth API. It exists for one reason: **a web app on another origin cannot read your session cookie.**

If you build a companion site, a browser extension, or an embed that should act on behalf of the signed-in user, you cannot simply call the REST API — the browser will not attach the dashboard session cookie to a cross-origin request, and the REST API authenticates with bearer tokens anyway.

This API closes that gap in two steps:

1. `GET /auth/api/session` — ask whether the visitor is signed in, and who they are.
2. `POST /auth/api/token` — exchange that session for a **short-lived bearer token** you can use against the regular REST API.

Both endpoints live on the public dashboard host and are reachable from `my.ternis.link` and `my.href.nz` alike.

## CORS: the allowlist is the gate

Cross-origin access is **off by default**. The operator has to list the origins that may call the API:

```bash
CORS_ALLOWED_ORIGINS="https://app.example.com,https://companion.test"
```

Everything else is rejected:

- A browser request from an origin that is not on the list gets **HTTP 403** — the request never reaches the handler.
- The CORS headers (`Access-Control-Allow-Origin`, `Access-Control-Allow-Credentials: true`) are only emitted for allowlisted origins, so a non-browser client cannot read the responses either.
- Credentials are allowed, so the list must never contain `*`. Browsers reject a wildcard with credentials anyway, and it would hand your session to every site on the internet.

The endpoints are exempt from CSRF validation, because a cross-origin client cannot produce a CSRF token. The origin allowlist takes that role: it is enforced server-side, not just in the browser.

## Step 1 — ask who the visitor is

```bash
curl https://my.ternis.link/auth/api/session \
  -H "Origin: https://app.example.com" \
  -H "Cookie: $SESSION_COOKIE"
```

```json
{
  "authenticated": true,
  "host": "my.ternis.link",
  "user": {
    "id": "01K8Z…",
    "name": "Alex Example",
    "email": "alex@example.com",
    "plan": "free"
  }
}
```

A visitor who is not signed in is a normal answer, not an error:

```json
{ "authenticated": false, "host": "my.ternis.link", "user": null }
```

The response is always HTTP 200.

In the browser, send the session cookie with `credentials: 'include'`:

```js
const res = await fetch('https://my.ternis.link/auth/api/session', {
  credentials: 'include',
  headers: { Accept: 'application/json' },
});

const { authenticated, user } = await res.json();
```

## Step 2 — exchange the session for a token

```bash
curl -X POST https://my.ternis.link/auth/api/token \
  -H "Origin: https://app.example.com" \
  -H "Cookie: $SESSION_COOKIE" \
  -H "Content-Type: application/json"
```

```json
{
  "token": "ts_…",
  "token_type": "Bearer",
  "expires_at": "2026-10-21T12:15:00+00:00",
  "expires_in": 900,
  "host": "my.ternis.link",
  "user": { "id": "01K8Z…", "name": "Alex Example", "email": "alex@example.com", "plan": "free" }
}
```

- The raw token is returned **once** and never stored — only its SHA-256 digest is, exactly like an API key.
- Without a session the endpoint answers **HTTP 401**.
- `ttl` may be sent in the body to ask for a different lifetime. It is clamped to 60 … 3600 seconds; the default is 900 (15 minutes).
- Minting a token is recorded in the activity feed.

## Step 3 — use the token

Session tokens are accepted as bearer tokens by the whole REST API, exactly like an API key:

```bash
curl https://links.t-api.de/v1/links \
  -H "Authorization: Bearer ts_…"
```

The difference to an API key: a session token is **short-lived, revocable and minted on demand** — it is meant for a browser session, not for a server-to-server integration. Use a regular API key for that; see [Accounts & API keys](./authentication.md).

## Revoking

```bash
curl -X DELETE https://my.ternis.link/auth/api/token \
  -H "Origin: https://app.example.com" \
  -H "Authorization: Bearer ts_…"
```

Answers **HTTP 204**. The token stops working immediately; subsequent API calls with it return HTTP 401.

## Notes

- Both hosts (`my.ternis.link`, `my.href.nz`) serve the same app and the same session. The `host` field tells you which one answered.
- Tokens expire on their own; there is no refresh token. Mint a new one when needed.
- The endpoints are rate limited (30 requests per minute per IP).
- Tokens are bound to the account that was signed in. They act with exactly that account's permissions and quota.
