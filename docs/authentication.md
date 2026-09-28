# Accounts & API keys

## Sign in without a password

There are no local passwords and no registration forms. Signing in is one button — **Log in with Ternis Auth** — which hands you over to Ternis Auth, verifies you, and brings you back to your dashboard. If your session expires, you simply sign in again.

Members get custom slugs, shorter links, QR codes, click stats, custom domains, and API keys. Guests keep the free shortener with nothing to manage.

Your login session lives on `dash.ternis.link`. That's why signing in from `href.nz` briefly takes you there and back — browsers don't share sessions across domains, so the hop is the feature, not a bug.

## Roles

Your role decides which corners of the network you can use: regular members get the dashboard, family and partners get the `ternis.link` areas, and admins additionally get the admin console and business areas. If you open something above your role, you'll get a plain "no access" page pointing you back.

## API keys

API keys let scripts and apps act as you. Create one in the dashboard under API keys: you see the full key **once** — it starts with `tl_` — after that only a masked prefix (`tl_abc1****`) is shown. Only a hash of the key is stored, so a leaked database can't leak your keys. Revoke a key the moment you don't need it anymore.

Use the key as a Bearer token against `https://links.t-api.de/v1`:

```bash
curl https://links.t-api.de/v1/links \
  -H "Authorization: Bearer tl_your_key_here"
```

Create a link with a custom slug:

```bash
curl -X POST https://links.t-api.de/v1/links \
  -H "Authorization: Bearer tl_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{"destination_url": "https://example.com/very-long-page", "slug": "my-launch"}'
```

No account and just scripting something quick? `POST /v1/links/public` creates guest links without any key — same rules as the [href.nz form](https://href.nz): auto-generated slugs, fair-use limits.

## API conventions

- **Versioning:** every response carries `API-Version` and `API-Latest-Version` headers. The path version (`/v1/`) is what you code against.
- **Errors** are JSON: `{ "message": "…" }`, or `{ "message": "…", "errors": { … } }` for validation problems. Over the rate limit you get `429` with a `Retry-After` header — back off and retry.
- **Deleting** a link via `DELETE /v1/links/{link}` deactivates it: it stops resolving, its stats stay.
- The full contract is [OpenAPI 3.1](/api-v1-openapi.yaml).

Click analytics for your own links live at `GET /v1/links/{link}/clicks` (rows) and `GET /v1/links/{link}/clicks/summary` (aggregates) — the same numbers the dashboard charts are drawn from.

The complete reference — domains, API keys, notifications, activity, settings, QR codes — with copy-paste examples lives in the [API guide](./api).
