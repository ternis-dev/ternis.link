<?php

namespace App\Livewire\Dashboard;

use App\Models\Domain;
use App\Services\LinkService;
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
        return [
            'destination_url' => ['required', 'url', 'max:2048'],
            'domain_id' => ['required', 'exists:domains,id'],
            'slug' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9_-]+$/', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
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

        $domain = Domain::findOrFail($this->domain_id);

        $link = $linkService->create(
            destinationUrl: $this->destination_url,
            domain: $domain,
            user: auth()->user(),
            customSlug: $this->slug ?: null,
            expiresAt: $this->expires_at ? new \DateTime($this->expires_at) : null,
        );

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
     * Get domains the current user is allowed to create links on.
     */
    private function getAvailableDomains()
    {
        $user = auth()->user();

        return Domain::where('is_active', true)
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
