<?php

namespace App\Livewire\Meinlink;

use App\Enums\DomainType;
use App\Exceptions\JunkUrlException;
use App\Exceptions\UnsafeUrlException;
use App\Models\Domain;
use App\Models\Link;
use App\Services\LinkService;
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

    public bool $minimal = false;

    public string $destination_url = '';

    public ?string $turnstile_token = null;

    public ?string $shortUrl = null;

    public ?string $originalUrl = null;

    /** Live input state: idle|invalid|valid. */
    public string $urlState = 'idle';

    public bool $quotaExceeded = false;

    public string $errorKind = 'idle';

    public ?string $errorMessage = null;

    protected function rules(): array
    {
        return [
            'destination_url' => ['required', 'url', 'max:'.LinkService::PUBLIC_MAX_URL_LENGTH],
        ];
    }

    protected function messages(): array
    {
        return [
            'destination_url.required' => 'Bitte gib eine Ziel-URL ein.',
            'destination_url.url' => 'Das sieht nicht nach einer gültigen URL aus — sie muss mit https:// oder http:// beginnen.',
            'destination_url.max' => 'Diese URL ist zu lang — maximal '.LinkService::PUBLIC_MAX_URL_LENGTH.' Zeichen erlaubt.',
        ];
    }

    public function updatedDestinationUrl(mixed $value): void
    {
        $raw = trim((string) $value);

        if ($this->shortUrl !== null) {
            $this->shortUrl = null;
            $this->originalUrl = null;
        }

        $this->errorMessage = null;
        $this->resetValidation('destination_url');

        if ($raw === '') {
            $this->urlState = 'idle';
            $this->errorKind = 'idle';

            return;
        }

        if (filter_var($raw, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $raw)) {
            $this->urlState = 'valid';
            $this->errorKind = 'idle';

            return;
        }

        $this->urlState = 'invalid';
    }

    public function getFixablePreviewProperty(): ?string
    {
        $raw = trim($this->destination_url);

        if ($raw === '' || preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $raw)) {
            return null;
        }

        if (! str_contains($raw, '.') || str_contains($raw, ' ')) {
            return null;
        }

        $candidate = 'https://'.$raw;

        return filter_var($candidate, FILTER_VALIDATE_URL) ? $candidate : null;
    }

    public function applyFix(): void
    {
        $fixed = $this->fixablePreview;

        if ($fixed) {
            $this->destination_url = $fixed;
            $this->urlState = 'valid';
            $this->errorMessage = null;
            $this->resetValidation('destination_url');
        }
    }

    public function getQuotaLeftProperty(): int
    {
        $ip = request()->ip() ?? '127.0.0.1';
        $ipHash = IpHash::make($ip);
        $domain = $this->resolveDomain();

        $used = Link::where('domain_id', $domain->id)
            ->where('creator_ip_hash', $ipHash)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        return max(0, LinkService::ANONYMOUS_DAILY_LIMIT - $used);
    }

    public function getDuplicateProperty(): ?Link
    {
        $raw = trim($this->destination_url);

        if ($raw === '' || ! filter_var($raw, FILTER_VALIDATE_URL)) {
            return null;
        }

        $ip = request()->ip() ?? '127.0.0.1';
        $domain = $this->resolveDomain();

        return Link::where('domain_id', $domain->id)
            ->where('destination_url', $raw)
            ->where('creator_ip_hash', IpHash::make($ip))
            ->where('created_at', '>=', now()->subHours(24))
            ->latest('id')
            ->first();
    }

    public function create(LinkService $linkService, TurnstileService $turnstile): void
    {
        $key = 'meinlink-shorten:'.(request()->ip() ?? '127.0.0.1');

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->destination_url = trim($this->destination_url);
            $this->addError('destination_url', 'Zu viele Versuche hintereinander — bitte warte einen Moment.');
            $this->errorKind = 'throttle';

            return;
        }

        RateLimiter::hit($key, 60);

        $this->destination_url = trim($this->destination_url);
        $this->quotaExceeded = false;
        $this->errorKind = 'idle';
        $this->errorMessage = null;

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
                Log::warning('Turnstile enforced without site key on meinlink.at');
                $this->addError('turnstile_token', 'Die Sicherheitsprüfung ist aktuell nicht verfügbar — bitte versuche es später.');
                $this->errorKind = 'security';

                return;
            }

            if (empty($this->turnstile_token)) {
                $this->addError('turnstile_token', 'Bitte führe die Sicherheitsprüfung durch.');
                $this->errorKind = 'security';

                return;
            }

            if (! $turnstile->verify(
                $this->turnstile_token,
                request()->ip(),
                expectedHostname: request()->getHost(),
            )) {
                $this->addError('turnstile_token', 'Sicherheitsprüfung fehlgeschlagen — bitte versuche es erneut.');
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

            if ($e instanceof JunkUrlException) {
                $this->addError('destination_url', 'Diese URL sieht nach Test- oder Scanner-Daten aus und wird nicht gekürzt.');
                $this->errorKind = 'junk';

                return;
            }

            if ($e instanceof UnsafeUrlException) {
                $this->addError('destination_url', 'Diese URL kann aus Sicherheitsgründen nicht gekürzt werden.');
                $this->errorKind = 'invalid';

                return;
            }

            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    if (str_contains((string) $message, 'Daily link limit')) {
                        $this->addError('destination_url', 'Tageslimit erreicht — als Mitglied erhältst du ein höheres Limit.');
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

            $this->addError('destination_url', 'Zu viele Anfragen — bitte warte einen Augenblick.');
            $this->errorKind = 'throttle';

            return;
        }

        $this->shortUrl = "https://{$domain->hostname}/{$link->slug}";
        $this->originalUrl = $this->destination_url;
        $this->destination_url = '';
        $this->urlState = 'idle';
    }

    public function resetForm(): void
    {
        $this->reset(['destination_url', 'shortUrl', 'originalUrl', 'urlState', 'quotaExceeded', 'errorKind', 'turnstile_token', 'errorMessage']);
        $this->urlState = 'idle';
        $this->errorKind = 'idle';
        $this->resetValidation();
        $this->dispatch('reset-turnstile');
    }

    public function resolveDomain(): Domain
    {
        $current = request()->attributes->get('domain_model');

        if ($current instanceof Domain
            && $current->isSystemDomain()
            && $current->type === DomainType::Public
            && $current->isUsableForLinks()) {
            return $current;
        }

        $meinlinkHost = (string) config('domains.meinlink_host', 'meinlink.at');

        $domain = Domain::where('hostname', $meinlinkHost)->first();

        if (! $domain) {
            $domain = Domain::firstOrCreate(
                ['hostname' => $meinlinkHost],
                ['type' => DomainType::Public, 'is_active' => true],
            );
        }

        return $domain;
    }

    public function render()
    {
        return view('livewire.meinlink.shorten-form');
    }
}
