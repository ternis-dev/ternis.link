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
    public const MIN_GENERATED_LENGTH = 3;

    public const MAX_GENERATED_LENGTH = 64;

    /**
     * Modal mode: hides the Cancel link (a Close button is shown
     * instead) and notifies listening tables on success.
     */
    public bool $modal = false;

    protected $listeners = [
        'open-link-creator' => 'handleOpenModal',
    ];

    public function handleOpenModal(): void
    {
        $this->createdSlug = null;
        $this->createdDomain = null;
        $this->resetErrorBag();
    }

    public function createAnother(): void
    {
        $this->createdSlug = null;
        $this->createdDomain = null;
        $this->destination_url = '';
        $this->slug = null;
        $this->description = null;
        $this->tags = null;
        $this->expires_at = null;
        $this->resetErrorBag();
    }

    public string $destination_url = '';

    public ?string $slug = null;

    public ?string $domain_id = null;

    public ?int $slug_length = null;

    public ?string $description = null;

    public ?string $tags = null;

    public ?string $expires_at = null;

    public ?string $createdSlug = null;

    public ?string $createdDomain = null;

    protected function rules(): array
    {
        $minLength = auth()->user()?->plan?->min_slug_length ?? LinkService::AUTHENTICATED_DEFAULT_SLUG_LENGTH;

        $rules = [
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
            'description' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'string', 'max:255'],
        ];

        // The length picker is only enforced when it applies: eligible
        // user, auto-generated slug (no custom slug entered).
        if ($this->canChooseSlugLength() && trim((string) $this->slug) === '') {
            [$min, $max] = $this->slugLengthBounds();
            $rules['slug_length'] = ['required', 'integer', "min:{$min}", "max:{$max}"];
        }

        return $rules;
    }

    protected function messages(): array
    {
        $minLength = auth()->user()?->plan?->min_slug_length ?? LinkService::AUTHENTICATED_DEFAULT_SLUG_LENGTH;
        $planName = auth()->user()?->plan?->name ?? 'current';
        [$lengthMin, $lengthMax] = $this->slugLengthBounds();

        return [
            'slug.min' => "The slug must be at least {$minLength} characters for your plan ({$planName}).",
            'slug.unique' => 'This slug is already taken on the selected domain.',
            'slug_length.required' => 'Choose a length for the auto-generated slug.',
            'slug_length.integer' => 'The slug length must be a whole number.',
            'slug_length.min' => "The slug length must be at least {$lengthMin} characters.",
            'slug_length.max' => "The slug length may not exceed {$lengthMax} characters.",
            'description.max' => 'The description may not exceed 500 characters.',
        ];
    }

    /**
     * Whether the length picker applies to the current user.
     */
    public function canChooseSlugLength(): bool
    {
        return (bool) auth()->user()?->canChooseSlugLength();
    }

    /**
     * Picker bounds: global 3–64 floor/ceiling, never below the
     * plan minimum (admins always get the full range).
     *
     * @return array{int, int} [min, max]
     */
    public function slugLengthBounds(): array
    {
        if (auth()->user()?->isAdmin()) {
            return [self::MIN_GENERATED_LENGTH, self::MAX_GENERATED_LENGTH];
        }

        $planMin = auth()->user()?->plan?->min_slug_length ?? LinkService::AUTHENTICATED_DEFAULT_SLUG_LENGTH;

        return [max(self::MIN_GENERATED_LENGTH, $planMin), self::MAX_GENERATED_LENGTH];
    }

    public function mount(): void
    {
        $firstDomain = $this->getAvailableDomains()->first();
        if ($firstDomain) {
            $this->domain_id = $firstDomain->id;
        }

        if ($this->canChooseSlugLength()) {
            $this->slug_length = $this->slugLengthBounds()[0];
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
        $customSlug = trim((string) $this->slug) !== '' ? trim((string) $this->slug) : null;

        // A custom slug always wins; the picker only sizes auto-generated ones.
        $generatedLength = $customSlug === null && $this->canChooseSlugLength() && $this->slug_length !== null
            ? (int) $this->slug_length
            : null;

        if (($invalid = LinkService::invalidTags($this->tags)) !== []) {
            $this->addError('tags', 'Tags may only contain lowercase letters, numbers and dashes: '.implode(', ', array_slice($invalid, 0, 3)).'.');

            return;
        }

        try {
            $link = $linkService->create(
                destinationUrl: $this->destination_url,
                domain: $domain,
                user: $user,
                customSlug: $customSlug,
                expiresAt: $this->expires_at ? new \DateTime($this->expires_at) : null,
                generatedLength: $generatedLength,
                description: $this->description,
                tags: $this->tags,
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

        if ($this->modal) {
            $this->dispatch('link-created');
        }

        // Reset form (keep the chosen length)
        $this->destination_url = '';
        $this->slug = null;
        $this->description = null;
        $this->tags = null;
        $this->expires_at = null;
    }

    public function render()
    {
        return view('livewire.dashboard.link-form', [
            'domains' => $this->getAvailableDomains(),
            'canChooseSlugLength' => $this->canChooseSlugLength(),
            'slugLengthMin' => $this->slugLengthBounds()[0],
            'slugLengthMax' => $this->slugLengthBounds()[1],
        ]);
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
