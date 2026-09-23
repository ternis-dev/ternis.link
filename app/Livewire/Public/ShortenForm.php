<?php

namespace App\Livewire\Public;

use App\Enums\DomainType;
use App\Models\Domain;
use App\Services\LinkService;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ShortenForm extends Component
{
    public string $destination_url = '';

    public ?string $slug = null;

    public ?string $shortUrl = null;

    protected function rules(): array
    {
        $minLength = LinkService::ANONYMOUS_MIN_SLUG_LENGTH;

        return [
            'destination_url' => ['required', 'url', 'max:2048'],
            'slug' => [
                'nullable',
                'string',
                'regex:/^[a-zA-Z0-9_-]+$/',
                'max:255',
                "min:{$minLength}",
                Rule::unique('links', 'slug')->where(fn ($query) => $query->where('domain_id', $this->resolveDomain()->id)),
            ],
        ];
    }

    protected function messages(): array
    {
        $minLength = LinkService::ANONYMOUS_MIN_SLUG_LENGTH;

        return [
            'slug.min' => "The slug must be at least {$minLength} characters for guest links. Log in for shorter slugs.",
            'slug.unique' => 'This slug is already taken. Try another one.',
        ];
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
            $link = $linkService->create(
                destinationUrl: $this->destination_url,
                domain: $domain,
                user: null,
                customSlug: $this->slug ?: null,
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

        $this->destination_url = '';
        $this->slug = null;
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
