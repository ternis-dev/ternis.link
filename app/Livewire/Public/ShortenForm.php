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

    public function create(LinkService $linkService): void
    {
        $key = 'public-shorten:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('destination_url', 'Too many links created. Please wait a moment and try again.');

            return;
        }

        $this->validate();

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
                }
            }

            return;
        } catch (ThrottleRequestsException) {
            $this->addError('destination_url', 'Too many links created. Please wait a moment and try again.');

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
        $this->reset(['destination_url', 'shortUrl', 'originalUrl', 'urlState']);
        $this->urlState = 'idle';
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
