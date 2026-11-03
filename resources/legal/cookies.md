# Cookie Policy

> Last updated: 2026-11-03

This Cookie Policy explains how ternis.link and its network domains use cookies and local browser storage, the specific purpose of each cookie, and its maximum retention lifetime.

**Controller:** Ternis / ternis.link ([legal@ternis.dev](mailto:legal@ternis.dev) / [platforms@ternis.dev](mailto:platforms@ternis.dev)). For our general data processing practices, see our [Privacy Policy](privacy).

---

## 1. Our Privacy-First Cookie Philosophy

We believe link redirection, click analytics, and URL shortening should respect user privacy by default:

- **Zero Advertising or Marketing Cookies:** We do not use advertising trackers, marketing pixels, or third-party behavioral profiling cookies anywhere on our network.
- **Cookie-Free Redirect Analytics:** When visitors click a short link, visits are recorded server-side using one-way cryptographic IP hashes and coarse technical metadata. **No cookies are placed on visitor browsers during standard link redirection.**
- **Strictly Necessary and Essential Only:** Cookies are set exclusively when required for account authentication, security enforcement, CSRF defense, or human verification against automated bot attacks.

---

## 2. Complete Inventory of Cookies

The table below lists all cookies that may be set by services across our network:

| Cookie Name | Provider / Domain | Purpose & Category | Security Flags | Max Lifetime |
|---|---|---|---|---|
| **`{app}_session`** (e.g. `ternis_link_session`) | First-party (`dash.ternis.link`, `admin.ternis.link`, origin host) | **Strictly Necessary**: Maintains secure authenticated dashboard sessions, remembers sign-in state, and holds temporary flash messages. | `HttpOnly`, `Secure`, `SameSite=Lax` | **Session / 120 minutes** of inactivity (or up to 30 days if "Remember Me" is selected) |
| **`XSRF-TOKEN`** | First-party (`ternis.link`, `dash.ternis.link`) | **Strictly Necessary**: Cryptographic token preventing Cross-Site Request Forgery (CSRF) attacks on form submissions. | `Secure`, `SameSite=Lax` | **2 hours** (matches session) |
| **`tl_human_verified`** | First-party (`.ternis.link` wildcard) | **Strictly Necessary (Security)**: Issued by `protected.ternis.link` after a visitor solves a verification challenge (Turnstile or Custom CAPTCHA). Contains an encrypted timestamp and salt to remember human verification and bypass repeated challenges across the link network. | `HttpOnly`, `Secure`, `SameSite=Lax` | **2 hours (120 minutes)** from issuance |
| **`clicked_locale`** | First-party (`clicked.at`) | **Functional / Preference**: Remembers visitor language choice (`en` or `de`) on the clicked.at newsletter link shortener. | `SameSite=Lax` | **1 year** |
| **`cf_clearance` / `__cf_bm`** | Cloudflare (`challenges.cloudflare.com`) | **Security / Anti-Bot (Optional)**: Set by Cloudflare Turnstile only when a link or gateway is configured with Cloudflare verification. Evaluates client connection telemetry to distinguish humans from automated attack bots. | `HttpOnly`, `Secure`, `SameSite=None` | **5 minutes to 1 year** (managed by Cloudflare) |

> [!NOTE]
> If a short link or gateway is configured with our **Self-Hosted Privacy CAPTCHA**, no third-party Cloudflare cookies (`cf_clearance`, `__cf_bm`) are ever set. All arithmetic challenges and verification tokens are handled entirely by our own application servers.

---

## 3. Local Browser Storage (`localStorage`)

In addition to HTTP cookies, our web interfaces use standard browser `localStorage` for client-side user preferences:

| Storage Key | Storage Type | Purpose | Max Lifetime |
|---|---|---|---|
| **`tl-theme`** | `localStorage` | Remembers your interface appearance preference (`light` or `dark`). Read before page render to avoid visual flashing. | Persistent until cleared by user or browser cache reset |

Browser `localStorage` remains strictly on your device and is never transmitted to our servers with HTTP requests.

---

## 4. Legal Basis (GDPR & ePrivacy Directive)

Under the EU ePrivacy Directive (Directive 2002/58/EC as amended) and GDPR Article 6(1):

1. **Strictly Necessary Cookies** (`{app}_session`, `XSRF-TOKEN`, `tl_human_verified`): These are exempt from consent requirements because they are strictly necessary to deliver the service explicitly requested by the user (authentication, security protection, and CSRF defense).
2. **Preference Cookies & Storage** (`clicked_locale`, `tl-theme`): Processed based on legitimate interests (GDPR Art. 6(1)(f)) to provide a localized and accessible interface without tracking.

---

## 5. How to Control or Delete Cookies

You can control, block, or delete cookies at any time through your browser settings:

- **Chrome / Chromium:** Settings &rarr; Privacy and security &rarr; Cookies and other site data.
- **Firefox:** Settings &rarr; Privacy & Security &rarr; Enhanced Tracking Protection &rarr; Cookies.
- **Safari:** Settings &rarr; Safari &rarr; Advanced &rarr; Block All Cookies.
- **Edge:** Settings &rarr; Cookies and site permissions &rarr; Manage and delete cookies and site data.

Please note that blocking strictly necessary cookies will prevent you from signing in to the dashboard or completing security challenges on protected links.

---

## 6. Questions & Contact

If you have questions about our use of cookies or technical security tokens, contact:
- **Email:** [legal@ternis.dev](mailto:legal@ternis.dev) or [platforms@ternis.dev](mailto:platforms@ternis.dev)
- **Abuse Reports:** [abuse@ternis.dev](mailto:abuse@ternis.dev)
- **Privacy Policy:** [https://ternis.link/pages/legal/privacy](https://ternis.link/pages/legal/privacy)
