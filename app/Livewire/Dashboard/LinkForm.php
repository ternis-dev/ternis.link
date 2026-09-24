<?php

namespace App\Livewire\Dashboard;

use App\Models\Domain;
use App\Services\LinkService;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class LinkForm extends Component
{
    public string $destination_url = '';

    public ?string $slug = null;

    public int $domain_id = 0;

    public ?string $expires_at = null;

    public ?string $createdSlug = null;

    public ?string $createdDomain = null;

    protected function rules(): array
    {
        $minLength = auth()->user()?->plan?->min_slug_length ?? LinkService::AUTHENTICATED_DEFAULT_SLUG_LENGTH;

        return [
            'destination_url' => ['required', 'url', 'max:2048'],
            'domain_id' => ['required', 'exists:domains,id'],
            'slug' => [
                'nullable',
                'string',
                'regex:/^[a-zA-Z0-9_-]+$/',
                'max:255',
                "min:{$minLength}",
                Rule::unique('links', 'slug')->where(fn ($query) => $query->where('domain_id', $this->domain_id)),
            ],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    protected function messages(): array
    {
        $minLength = auth()->user()?->plan?->min_slug_length ?? LinkService::AUTHENTICATED_DEFAULT_SLUG_LENGTH;
        $planName = auth()->user()?->plan?->name ?? 'current';

        return [
            'slug.min' => "The slug must be at least {$minLength} characters for your plan ({$planName}).",
            'slug.unique' => 'This slug is already taken on the selected domain.',
        ];
    }

    public function mount(): void
    {
        $firstDomain = $this->getAvailableDomains()->first();
        if ($firstDomain) {
            $this->domain_id = $firstDomain->id;
        }
    }

    public function create(LinkService $linkService): void
    {
        $this->validate();

        $user = auth()->user();
        $max = $user?->plan?->max_links_per_day;

        if ($user && $max !== null
            && $user->links()->where('created_at', '>=', now()->startOfDay())->count() >= $max) {
            $planName = $user->plan?->name ?? 'current';
            $this->addError(
                'destination_url',
                "Daily link limit reached ({$max} per day on the {$planName} plan). Try again tomorrow."
            );

            return;
        }

        $domain = Domain::findOrFail($this->domain_id);

        try {
            $link = $linkService->create(
                destinationUrl: $this->destination_url,
                domain: $domain,
                user: $user,
                customSlug: $this->slug ?: null,
                expiresAt: $this->expires_at ? new \DateTime($this->expires_at) : null,
            );
        } catch (ValidationException $e) {
            // Service-level rejections (scanner junk, slug races) land
            // on the matching field instead of blowing up the form.
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

        $this->createdSlug = $link->slug;
        $this->createdDomain = $domain->hostname;

        // Reset form
        $this->destination_url = '';
        $this->slug = null;
        $this->expires_at = null;
    }

    public function render()
    {
        $domains = $this->getAvailableDomains();

        return view('livewire.dashboard.link-form', compact('domains'));
    }

    /**
     * Get domains the current user is allowed to create links on:
     * active system domains plus the user's own verified domains.
     */
    private function getAvailableDomains()
    {
        $user = auth()->user();

        return Domain::where('is_active', true)
            ->where(function ($query) use ($user) {
                $query->whereNull('domains.user_id');

                if ($user->isAdmin()) {
                    $query->orWhereNotNull('domains.verified_at');
                } else {
                    $query->orWhere(function ($q) use ($user) {
                        $q->where('domains.user_id', $user->id)
                            ->whereNotNull('domains.verified_at');
                    });
                }
            })
            ->when(! $user->isAdmin(), function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('type', 'public');
                    $q->orWhere('user_id', $user->id);

                    if ($user->isFamily() || $user->isAdmin()) {
                        $q->orWhere('type', 'ternis');
                    }

                    if ($user->isAdmin()) {
                        $q->orWhere('type', 'business');
                    }
                });
            })
            ->orderBy('hostname')
            ->get();
    }
}
