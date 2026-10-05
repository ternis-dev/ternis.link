<?php

namespace App\Livewire\Public;

use App\Exceptions\JunkUrlException;
use App\Exceptions\UnsafeUrlException;
use App\Models\Domain;
use App\Models\Link;
use App\Services\LinkService;
use App\Services\QrCodeService;
use App\Services\TurnstileService;
use App\Support\IpHash;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ShortenForm extends Component
{
    public bool $compact = false;

    /** Clean mode for /new: hides meter, previews, tray and doodles. */
    public bool $minimal = false;

    /**
     * Template variant: sketch (href.nz) or board (meinlink.at). Same
     * state and actions — only the markup differs.
     */
    public string $theme = 'sketch';

    /** Form language: en (href.nz) or de (meinlink.at). */
    public string $locale = 'en';

    public string $destination_url = '';

    /** Domain choice on public hosts: href.nz or href.yt. */
    public string $selectedDomain = 'href.nz';

    public ?string $turnstile_token = null;

    public ?string $shortUrl = null;

    public ?string $originalUrl = null;

    /** Live input state: idle|invalid|valid (format hint only). */
    public string $urlState = 'idle';

    /** True when the last submit failed on the daily guest quota. */
    public bool $quotaExceeded = false;

    /**
     * Error personality for the notice card:
     * idle|empty|invalid|too_long|quota|throttle|junk|security.
     */
    public string $errorKind = 'idle';

    protected function rules(): array
    {
        return [
            'destination_url' => ['required', 'url', 'max:'.LinkService::PUBLIC_MAX_URL_LENGTH],
            'selectedDomain' => ['required', 'string', 'in:href.nz,href.yt'],
        ];
    }

    protected function messages(): array
    {
        if ($this->locale === 'de') {
            return [
                'destination_url.required' => 'Bitte füge einen Link ein, den du kürzen möchtest.',
                'destination_url.url' => 'Das sieht nicht nach einer gültigen URL aus — sie muss mit https:// beginnen.',
                'destination_url.max' => 'Diese URL ist zu lang — bleib bitte unter 2.048 Zeichen.',
            ];
        }

        return [
            'destination_url.required' => 'Please paste a link to shorten.',
            'destination_url.url' => 'That doesn’t look like a valid URL — make sure it starts with https://.',
            'destination_url.max' => 'That URL is too long — keep it under 2,048 characters.',
            'selectedDomain.in' => 'Please choose href.nz or href.yt.',
        ];
    }

    /** Toggle inline QR code viewer on result card. */
    public bool $showQr = true;

    public function mount(): void
    {
        $host = strtolower((string) request()->getHost());
        if ($host === (string) config('domains.yt_host', 'href.yt')) {
            $this->selectedDomain = 'href.yt';
            if ($this->theme === 'sketch') {
                $this->theme = 'yt';
            }
        } else {
            $this->selectedDomain = 'href.nz';
        }
    }

    public function toggleQr(): void
    {
        $this->showQr = ! $this->showQr;
    }

    public function getQrSvgDataUriProperty(): ?string
    {
        if (! $this->shortUrl) {
            return null;
        }

        return app(QrCodeService::class)->renderDataUri(
            payload: $this->shortUrl,
            format: 'svg',
            size: 260,
            margin: 2,
            foregroundColor: '#000000',
            backgroundColor: '#ffffff'
        );
    }

    public function getQrPngDataUriProperty(): ?string
    {
        if (! $this->shortUrl) {
            return null;
        }

        return app(QrCodeService::class)->renderDataUri(
            payload: $this->shortUrl,
            format: 'png',
            size: 600,
            margin: 4,
            foregroundColor: '#000000',
            backgroundColor: '#ffffff'
        );
    }

    /**
     * UI strings for the guest form. English is the default (href.nz);
     * German serves meinlink.at. Placeholders use vsprintf (%s, %d).
     */
    private const STRINGS = [
        'form.title' => ['en' => 'Shorten a link', 'de' => 'Link kürzen'],
        'form.title.suffix' => ['en' => ' — no account needed', 'de' => ' — kein Konto nötig'],
        'form.sub' => [
            'en' => 'Paste any long URL. Guests get an auto-generated 8-character link on this domain.',
            'de' => 'Füge eine lange URL ein. Gäste bekommen einen automatisch erzeugten 8-Zeichen-Link auf dieser Domain.',
        ],
        'form.sub.minimal' => [
            'en' => 'Paste a destination — guests welcome, members get more.',
            'de' => 'Füg ein Ziel ein — Gäste willkommen, Mitglieder bekommen mehr.',
        ],
        'meter.title' => ['en' => 'Guest links left today', 'de' => 'Gäste-Links heute übrig'],
        'meter.text' => [
            'en' => '%d of %d free links left today',
            'de' => '%d von %d Gratis-Links heute übrig',
        ],
        'label.destination' => ['en' => 'Destination URL', 'de' => 'Ziel-URL'],
        'state.valid' => ['en' => 'Looks good', 'de' => 'Sieht gut aus'],
        'state.invalid' => ['en' => 'Check the URL', 'de' => 'URL prüfen'],
        'state.ready' => ['en' => 'Ready', 'de' => 'Bereit'],
        'input.placeholder' => [
            'en' => 'https://example.com/very-long-url…',
            'de' => 'https://beispiel.de/sehr-lange-url…',
        ],
        'tool.paste' => ['en' => 'Paste from clipboard', 'de' => 'Aus Zwischenablage einfügen'],
        'tool.clear' => ['en' => 'Clear field', 'de' => 'Feld leeren'],
        'submit' => ['en' => 'Shorten', 'de' => 'Antrag einreichen'],
        'submitting' => ['en' => 'Shortening…', 'de' => 'Wird bearbeitet…'],
        'focus.valid' => [
            'en' => 'Looks good — hit Shorten when you’re ready.',
            'de' => 'Sieht gut aus — reich den Antrag ein, wenn du bereit bist.',
        ],
        'focus.invalid' => [
            'en' => 'Tip: links start with https://',
            'de' => 'Tipp: Links beginnen mit https://',
        ],
        'focus.idle' => [
            'en' => 'Paste the full link, starting with https://',
            'de' => 'Füge den kompletten Link ein, beginnend mit https://',
        ],
        'fix.text' => ['en' => 'Did you mean', 'de' => 'Meintest du'],
        'fix.btn' => ['en' => 'Use this URL', 'de' => 'Diese URL nehmen'],
        'dup.title' => ['en' => 'Already shortened', 'de' => 'Bereits gekürzt'],
        'dup.copy' => ['en' => 'Copy', 'de' => 'Kopieren'],
        'dup.copy.aria' => ['en' => 'Copy existing short link', 'de' => 'Vorhandenen Kurzlink kopieren'],
        'preview.will' => ['en' => 'You’re shortening', 'de' => 'Du kürzt'],
        'preview.get' => ['en' => 'You’ll get', 'de' => 'Du bekommst'],
        'hint.minimal' => [
            'en' => 'Include <code>https://</code> — guests welcome, no account needed.',
            'de' => 'Mit <code>https://</code> — Gäste willkommen, kein Konto nötig.',
        ],
        'hint.full' => [
            'en' => 'Include <code>https://</code>. Guests get auto-made codes — <a href="%s" style="color: inherit; font-weight: 600;">log in</a> for custom slugs, shorter links &amp; click stats.',
            'de' => 'Mit <code>https://</code>. Gäste bekommen automatisch Codes — <a href="%s" style="color: inherit; font-weight: 600;">log dich ein</a> für eigene Kürzel, kürzere Links &amp; Klick-Statistiken.',
        ],
        'oops.title.quota' => ['en' => 'Daily limit reached', 'de' => 'Tageslimit erreicht'],
        'oops.title.too_long' => ['en' => 'That link is too long', 'de' => 'Der Link ist zu lang'],
        'oops.title.throttle' => ['en' => 'Slow down a moment', 'de' => 'Einen Moment langsamer'],
        'oops.title.empty' => ['en' => 'Paste a link first', 'de' => 'Füg zuerst einen Link ein'],
        'oops.title.junk' => ['en' => 'That doesn’t look like a real link', 'de' => 'Das sieht nicht nach einem echten Link aus'],
        'oops.title.invalid' => ['en' => 'That link doesn’t look right', 'de' => 'Der Link sieht nicht richtig aus'],
        'oops.title.security' => ['en' => 'Security check', 'de' => 'Sicherheitsprüfung'],
        'oops.nudge' => [
            'en' => 'Members get a higher daily limit — <a href="%s">log in</a> to keep going.',
            'de' => 'Mitglieder haben ein höheres Tageslimit — <a href="%s">log dich ein</a> und mach weiter.',
        ],
        'result.kicker' => [
            'en' => 'Done — your short link is ready',
            'de' => 'Fertig — dein Kurzlink ist bereit',
        ],
        'result.stamp' => ['en' => 'Approved', 'de' => 'Bewilligt'],
        'result.from' => ['en' => 'Shortened from', 'de' => 'Gekürzt aus'],
        'result.copy' => ['en' => 'Copy', 'de' => 'Kopieren'],
        'result.copy.aria' => ['en' => 'Copy short link to clipboard', 'de' => 'Kurzlink in Zwischenablage kopieren'],
        'result.open' => ['en' => 'Open link', 'de' => 'Link öffnen'],
        'result.qr' => ['en' => 'QR code', 'de' => 'QR-Code'],
        'result.again' => ['en' => 'Shorten another', 'de' => 'Noch einen kürzen'],
        'result.copied' => ['en' => 'Copied to clipboard.', 'de' => 'In Zwischenablage kopiert.'],
        'copy.done' => ['en' => 'Copied', 'de' => 'Kopiert'],
        'result.copyfail' => [
            'en' => 'Copy failed — select the link manually.',
            'de' => 'Kopieren fehlgeschlagen — markiere den Link manuell.',
        ],
        'tray.title' => ['en' => 'recent links on this device', 'de' => 'neueste Links auf diesem Gerät'],
        'tray.hide' => ['en' => 'hide', 'de' => 'ausblenden'],
        'tray.show' => ['en' => 'show', 'de' => 'anzeigen'],
        'tray.empty' => [
            'en' => 'No links shortened yet — your recent links will show up here.',
            'de' => 'Noch keine Links gekürzt — deine neuesten Links erscheinen hier.',
        ],
        'tray.copy' => ['en' => 'copy', 'de' => 'kopieren'],
        'tray.qr' => ['en' => 'qr', 'de' => 'qr'],
        'tray.clear' => ['en' => 'clear history', 'de' => 'Verlauf löschen'],
        'err.throttle' => [
            'en' => 'Too many tries in a row — wait a few seconds and try again.',
            'de' => 'Zu viele Versuche hintereinander — warte kurz und versuch es erneut.',
        ],
        'err.quota' => [
            'en' => 'Daily link limit reached — log in for a higher limit.',
            'de' => 'Tageslimit erreicht — log dich ein für ein höheres Limit.',
        ],
        'err.junk' => [
            'en' => 'That URL looks like scanner or test data and won’t be shortened.',
            'de' => 'Diese URL sieht nach Scanner- oder Testdaten aus und wird nicht gekürzt.',
        ],
        'err.unsafe' => [
            'en' => 'That URL can’t be shortened for safety reasons.',
            'de' => 'Diese URL kann aus Sicherheitsgründen nicht gekürzt werden.',
        ],
        'err.turnstile.unavailable' => [
            'en' => 'Security check is currently unavailable — please try again later.',
            'de' => 'Sicherheitsprüfung ist gerade nicht verfügbar — bitte später erneut versuchen.',
        ],
        'err.turnstile.empty' => [
            'en' => 'Please complete the security check.',
            'de' => 'Bitte löse die Sicherheitsprüfung.',
        ],
        'err.turnstile.failed' => [
            'en' => 'Security check failed — please try again.',
            'de' => 'Sicherheitsprüfung fehlgeschlagen — bitte erneut versuchen.',
        ],
        'cf.loading' => ['en' => 'Loading security check…', 'de' => 'Sicherheitsprüfung wird geladen…'],
        'cf.expired' => [
            'en' => 'The security challenge expired — please solve it again.',
            'de' => 'Die Sicherheitsprüfung ist abgelaufen — bitte erneut lösen.',
        ],
        'cf.timeout' => [
            'en' => 'The security challenge timed out — please try again.',
            'de' => 'Die Sicherheitsprüfung hat zu lange gedauert — bitte erneut versuchen.',
        ],
        'cf.error' => [
            'en' => 'The security challenge failed to load — the site key may be wrong for this domain.',
            'de' => 'Die Sicherheitsprüfung konnte nicht geladen werden — der Site-Key passt evtl. nicht zu dieser Domain.',
        ],
        'cf.unsupported' => [
            'en' => 'Your browser cannot display the security challenge — please update it and reload.',
            'de' => 'Dein Browser kann die Sicherheitsprüfung nicht anzeigen — bitte aktualisieren und neu laden.',
        ],
        'cf.startfail' => [
            'en' => 'The security challenge failed to start — please reload the page.',
            'de' => 'Die Sicherheitsprüfung konnte nicht starten — bitte Seite neu laden.',
        ],
        'cf.invisible' => [
            'en' => 'The security challenge is invisible — the site key may be set to “Invisible” or blocked for this domain.',
            'de' => 'Die Sicherheitsprüfung ist unsichtbar — der Site-Key ist evtl. unsichtbar geschaltet oder für diese Domain blockiert.',
        ],
        'cf.blocked' => [
            'en' => 'The security challenge failed to load — an ad-blocker may be blocking it. Allow this site and reload the page.',
            'de' => 'Die Sicherheitsprüfung konnte nicht geladen werden — ein Werbeblocker blockiert sie evtl. Seite erlauben und neu laden.',
        ],
    ];

    /**
     * Translate a UI key (vsprintf placeholders when args given).
     */
    public function t(string $key, mixed ...$args): string
    {
        $template = self::STRINGS[$key][$this->locale]
            ?? self::STRINGS[$key]['en']
            ?? $key;

        return $args !== [] ? vsprintf($template, $args) : $template;
    }

    /**
     * Service/validator messages stay verbatim in English; in German
     * they are replaced by the matching dictionary entry.
     */
    private function err(string $key, string $fallback): string
    {
        return $this->locale === 'de' ? $this->t($key) : $fallback;
    }

    /**
     * Live format hint while typing — real validation still
     * happens on submit. Clears a previous submit error as soon
     * as the user starts fixing the input.
     */
    public function updatedDestinationUrl(): void
    {
        $this->destination_url = trim($this->destination_url);
        $value = $this->destination_url;

        if ($value === '') {
            $this->urlState = 'idle';
            $this->resetValidation('destination_url');
            $this->errorKind = 'idle';
            $this->quotaExceeded = false;

            return;
        }

        $valid = strlen($value) <= LinkService::PUBLIC_MAX_URL_LENGTH && filter_var($value, FILTER_VALIDATE_URL) !== false;
        $this->urlState = $valid ? 'valid' : 'invalid';

        if ($valid) {
            $this->resetValidation('destination_url');
            $this->errorKind = 'idle';
            $this->quotaExceeded = false;
        }
    }

    /**
     * Guest links left today for this IP (quota meter display).
     */
    public function getQuotaLeftProperty(): int
    {
        $used = Link::where('creator_ip_hash', IpHash::make(request()->ip()))
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        return max(0, LinkService::ANONYMOUS_DAILY_LIMIT - $used);
    }

    /**
     * Trimmed input length for the public URL counter.
     */
    public function getCharCountProperty(): int
    {
        return mb_strlen(trim($this->destination_url));
    }

    /**
     * What the link will shorten to, once the input looks valid —
     * shown as a preview before submitting.
     */
    public function getNormalizedPreviewProperty(): ?string
    {
        if ($this->urlState !== 'valid') {
            return null;
        }

        return trim($this->destination_url);
    }

    /**
     * Hostname the short link will live on.
     */
    public function getPreviewHostProperty(): string
    {
        try {
            return $this->resolveDomain()->hostname;
        } catch (\Throwable) {
            return 'href.nz';
        }
    }

    /**
     * When the input is invalid only because the scheme is missing
     * (or is plain http://), offer the fixed URL for one-click repair.
     */
    public function getFixablePreviewProperty(): ?string
    {
        if ($this->urlState !== 'invalid') {
            return null;
        }

        $value = trim($this->destination_url);

        if ($value === '' || str_contains($value, ' ')) {
            return null;
        }

        if (str_starts_with($value, 'http://')) {
            $fixed = 'https://'.substr($value, 7);
        } elseif (str_starts_with($value, '//')) {
            $fixed = 'https:'.$value;
        } elseif (preg_match('#^[a-z][a-z0-9+.-]*://#i', $value)) {
            // Some other scheme (ftp:, file:, …) — don't guess.
            return null;
        } else {
            $fixed = 'https://'.ltrim($value, '/');
        }

        if (strlen($fixed) > LinkService::PUBLIC_MAX_URL_LENGTH || ! filter_var($fixed, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $fixed;
    }

    /**
     * Apply the scheme fix suggested by fixablePreview.
     */
    public function applyFix(): void
    {
        $fixed = $this->fixablePreview;

        if ($fixed === null) {
            return;
        }

        $this->destination_url = $fixed;
        $this->urlState = 'valid';
        $this->quotaExceeded = false;
        $this->errorKind = 'idle';
        $this->resetValidation();
    }

    /**
     * An existing active guest link for the same destination on this
     * domain — no need to shorten twice. Authenticated links are excluded:
     * showing them here would expose their analytics-bearing URL as a
     * guest result.
     */
    public function getDuplicateProperty(): ?Link
    {
        if ($this->urlState !== 'valid' || $this->shortUrl !== null) {
            return null;
        }

        try {
            $domain = $this->resolveDomain();
        } catch (\Throwable) {
            return null;
        }

        return Link::accessible()
            ->whereNull('user_id')
            ->where('domain_id', $domain->id)
            ->where('destination_url', trim($this->destination_url))
            ->orderByDesc('created_at')
            ->first();
    }

    public function create(LinkService $linkService, TurnstileService $turnstile): void
    {
        $key = 'public-shorten:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->destination_url = trim($this->destination_url);
            $this->addError('destination_url', $this->err('err.throttle', 'Too many tries in a row — wait a few seconds and try again.'));
            $this->errorKind = 'throttle';

            return;
        }

        $this->destination_url = trim($this->destination_url);
        $this->quotaExceeded = false;
        $this->errorKind = 'idle';

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $failed = $e->validator->failed()['destination_url'] ?? [];
            $this->errorKind = isset($failed['Max'])
                ? 'too_long'
                : (isset($failed['Required']) ? 'empty' : 'invalid');

            throw $e;
        }

        if ($turnstile->isEnabled()) {
            if (! $turnstile->isWidgetAvailable()) {
                // Misconfigured server (secret set, site key missing):
                // no challenge can render, so say so instead of asking
                // users to complete one. Still fail closed.
                Log::warning('Turnstile enforced without a site key — guest submissions blocked until TURNSTILE_SITE_KEY is set.');
                $this->addError('turnstile_token', $this->err('err.turnstile.unavailable', 'Security check is currently unavailable — please try again later.'));
                $this->errorKind = 'security';

                return;
            }

            if (empty($this->turnstile_token)) {
                $this->addError('turnstile_token', $this->err('err.turnstile.empty', 'Please complete the security check.'));
                $this->errorKind = 'security';

                return;
            }

            if (! $turnstile->verify(
                $this->turnstile_token,
                request()->ip(),
                expectedHostname: request()->getHost(),
            )) {
                $this->addError('turnstile_token', $this->err('err.turnstile.failed', 'Security check failed — please try again.'));
                $this->errorKind = 'security';
                $this->turnstile_token = null;
                $this->dispatch('reset-turnstile');

                return;
            }

            $this->turnstile_token = null;
            $this->dispatch('reset-turnstile');
        }

        $domain = $this->resolveDomain();

        try {
            // Guests always get an auto-generated 8-char slug — no custom slugs.
            $link = $linkService->create(
                destinationUrl: $this->destination_url,
                domain: $domain,
                user: null,
                customSlug: null,
                creatorIp: request()->ip(),
            );
        } catch (ValidationException $e) {
            $this->turnstile_token = null;
            $this->dispatch('reset-turnstile');

            // Scanner junk gets its own notice card, not the quota one.
            if ($e instanceof JunkUrlException) {
                foreach ($e->errors() as $field => $messages) {
                    foreach ((array) $messages as $message) {
                        $this->addError($field, $this->err('err.junk', (string) $message));
                    }
                }
                $this->errorKind = 'junk';

                return;
            }

            // Structurally unsafe targets (intranet, credentials, …).
            if ($e instanceof UnsafeUrlException) {
                foreach ($e->errors() as $field => $messages) {
                    foreach ((array) $messages as $message) {
                        $this->addError($field, $this->err('err.unsafe', (string) $message));
                    }
                }
                $this->errorKind = 'invalid';

                return;
            }

            // Daily quota errors come from the service, not component rules.
            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    if (str_contains((string) $message, 'Daily link limit')) {
                        $this->addError($field, $this->err('err.quota', (string) $message));
                        $this->quotaExceeded = true;
                        $this->errorKind = 'quota';
                    } else {
                        $this->addError($field, $message);

                        if ($this->errorKind === 'idle') {
                            $this->errorKind = 'invalid';
                        }
                    }
                }
            }

            return;
        } catch (ThrottleRequestsException) {
            $this->turnstile_token = null;
            $this->dispatch('reset-turnstile');

            $this->addError('destination_url', $this->err('err.throttle', 'Too many tries in a row — wait a few seconds and try again.'));
            $this->errorKind = 'throttle';

            return;
        }

        RateLimiter::hit($key, 60);

        $this->shortUrl = "https://{$domain->hostname}/{$link->slug}";
        $this->originalUrl = $this->destination_url;

        $this->destination_url = '';
        $this->urlState = 'idle';
    }

    public function resetForm(): void
    {
        $this->reset(['destination_url', 'shortUrl', 'originalUrl', 'urlState', 'quotaExceeded', 'errorKind', 'turnstile_token']);
        $this->urlState = 'idle';
        $this->errorKind = 'idle';
        $this->resetValidation();
        $this->dispatch('reset-turnstile');
    }

    private function resolveDomain(): Domain
    {
        $hostname = $this->selectedDomain === 'href.yt'
            ? (string) config('domains.yt_host', 'href.yt')
            : (string) config('domains.public_host', 'href.nz');

        return Domain::where('hostname', $hostname)->firstOrFail();
    }

    public function render()
    {
        return view('livewire.public.shorten-form');
    }
}
