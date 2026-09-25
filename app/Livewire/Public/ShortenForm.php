<?php

namespace App\Livewire\Public;

use App\Enums\DomainType;
use App\Exceptions\JunkUrlException;
use App\Models\Domain;
use App\Models\Link;
use App\Services\LinkService;
use App\Services\TurnstileService;
use App\Support\IpHash;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ShortenForm extends Component
{
    public string $destination_url = '';

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
            'destination_url' => ['required', 'url', 'max:2048'],
        ];
    }

    protected function messages(): array
    {
        return [
            'destination_url.required' => 'Please paste a link to shorten.',
            'destination_url.url' => 'That doesn’t look like a valid URL — make sure it starts with https://.',
            'destination_url.max' => 'That URL is too long — keep it under 2,048 characters.',
        ];
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

        $valid = strlen($value) <= 2048 && filter_var($value, FILTER_VALIDATE_URL) !== false;
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
     * Trimmed input length for the 2048-char counter.
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

        if (strlen($fixed) > 2048 || ! filter_var($fixed, FILTER_VALIDATE_URL)) {
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
     * An existing active link for the same destination on this domain —
     * no need to shorten twice.
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
            $this->addError('destination_url', 'Too many tries in a row — wait a few seconds and try again.');
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
            if (empty($this->turnstile_token)) {
                $this->addError('turnstile_token', 'Please complete the security check.');
                $this->errorKind = 'security';

                return;
            }

            if (! $turnstile->verify(
                $this->turnstile_token,
                request()->ip(),
                expectedHostname: request()->getHost(),
            )) {
                $this->addError('turnstile_token', 'Security check failed — please try again.');
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
                        $this->addError($field, $message);
                    }
                }
                $this->errorKind = 'junk';

                return;
            }

            // Daily quota errors come from the service, not component rules.
            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $this->addError($field, $message);

                    if (str_contains((string) $message, 'Daily link limit')) {
                        $this->quotaExceeded = true;
                        $this->errorKind = 'quota';
                    } elseif ($this->errorKind === 'idle') {
                        $this->errorKind = 'invalid';
                    }
                }
            }

            return;
        } catch (ThrottleRequestsException) {
            $this->turnstile_token = null;
            $this->dispatch('reset-turnstile');

            $this->addError('destination_url', 'Too many tries in a row — wait a few seconds and try again.');
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

    /**
     * Guest links always live on the current public domain,
     * falling back to href.nz.
     */
    private function resolveDomain(): Domain
    {
        $current = request()->attributes->get('domain_model');

        if ($current instanceof Domain
            && $current->isSystemDomain()
            && $current->type === DomainType::Public
            && $current->isUsableForLinks()) {
            return $current;
        }

        return Domain::where('hostname', 'href.nz')->firstOrFail();
    }

    public function render()
    {
        return view('livewire.public.shorten-form');
    }
}
