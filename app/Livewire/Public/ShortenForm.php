<?php

namespace App\Livewire\Public;

use App\Enums\DomainType;
use App\Models\Domain;
use App\Models\Link;
use App\Services\LinkService;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ShortenForm extends Component
{
    public string $destination_url = '';

    public ?string $shortUrl = null;

    public ?string $originalUrl = null;

    /** Live input state: idle|invalid|valid (format hint only). */
    public string $urlState = 'idle';

    /** True when the last submit failed on the daily guest quota. */
    public bool $quotaExceeded = false;

    /**
     * Error personality for the oops card:
     * idle|invalid|too_long|quota|throttle.
     */
    public string $errorKind = 'idle';

    protected function rules(): array
    {
        return [
            'destination_url' => ['required', 'url', 'max:2048'],
        ];
    }

    /**
     * Lightweight format hint while typing — real validation still
     * happens on submit.
     */
    public function updatedDestinationUrl(): void
    {
        $value = trim($this->destination_url);

        if ($value === '') {
            $this->urlState = 'idle';

            return;
        }

        $this->urlState = filter_var($value, FILTER_VALIDATE_URL) && strlen($value) <= 2048
            ? 'valid'
            : 'invalid';
    }

    /**
     * Guest links left today for this IP (quota meter display).
     */
    public function getQuotaLeftProperty(): int
    {
        $used = Link::where('creator_ip_hash', hash('sha256', (string) request()->ip()))
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        return max(0, LinkService::ANONYMOUS_DAILY_LIMIT - $used);
    }

    /**
     * Trimmed input length for the 2048-char counter.
     */
    public function getCharCountProperty(): int
    {
        return strlen(trim($this->destination_url));
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
     * When the input is invalid only because the scheme is missing,
     * offer the fixed URL for one-click repair.
     */
    public function getFixablePreviewProperty(): ?string
    {
        if ($this->urlState !== 'invalid') {
            return null;
        }

        $fixed = 'https://'.ltrim(trim($this->destination_url));

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

    public function create(LinkService $linkService): void
    {
        $key = 'public-shorten:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('destination_url', 'Too many links created. Please wait a moment and try again.');
            $this->errorKind = 'throttle';

            return;
        }

        $this->quotaExceeded = false;
        $this->errorKind = 'idle';

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $message = (string) $e->validator->errors()->first('destination_url');
            $this->errorKind = str_contains($message, '2048') ? 'too_long' : 'invalid';

            throw $e;
        }

        $domain = $this->resolveDomain();

        try {
            // Guests always get an auto-generated 8-char slug — no custom slugs.
            $link = $linkService->create(
                destinationUrl: $this->destination_url,
                domain: $domain,
                user: null,
                customSlug: null,
                creatorIpHash: hash('sha256', (string) request()->ip()),
            );
        } catch (ValidationException $e) {
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
            $this->addError('destination_url', 'Too many links created. Please wait a moment and try again.');
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
        $this->reset(['destination_url', 'shortUrl', 'originalUrl', 'urlState', 'quotaExceeded', 'errorKind']);
        $this->urlState = 'idle';
        $this->errorKind = 'idle';
        $this->resetValidation();
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
