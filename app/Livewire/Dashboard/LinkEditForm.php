<?php

namespace App\Livewire\Dashboard;

use App\Models\Link;
use App\Services\LinkService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class LinkEditForm extends Component
{
    public Link $link;

    public string $destination_url = '';

    public ?string $description = null;

    public ?string $tags = null;

    public ?string $expires_at = null;

    public bool $is_active = true;

    public bool $saved = false;

    public function mount(Link $link): void
    {
        $this->link = $this->editableLink($link->id);
        $this->syncFromModel();
    }

    protected function rules(): array
    {
        // An untouched expiry (even a past one) always validates; only
        // a CHANGED value must lie in the future.
        $expires = $this->expiresChanged() ? ['nullable', 'date', 'after:now'] : ['nullable', 'date'];

        return [
            'destination_url' => ['required', 'url', 'max:2048'],
            'description' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'string', 'max:255'],
            'expires_at' => $expires,
            'is_active' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'destination_url.required' => 'Please paste a destination URL.',
            'destination_url.url' => 'That doesn’t look like a valid URL — include https:// at the start.',
            'destination_url.max' => 'That URL is too long — keep it under 2,048 characters.',
            'description.max' => 'The description may not exceed 500 characters.',
            'expires_at.date' => 'The expiration doesn’t look like a valid date.',
            'expires_at.after' => 'The expiration date must be in the future.',
        ];
    }

    public function save(LinkService $linkService): void
    {
        $this->validate();

        // Re-resolve every save: ownership may have changed since mount,
        // and tampered ids must 404, not leak.
        $link = $this->editableLink($this->link->id);

        if (($invalid = LinkService::invalidTags($this->tags)) !== []) {
            $this->addError('tags', 'Tags may only contain lowercase letters, numbers and dashes: '.implode(', ', array_slice($invalid, 0, 3)).'.');

            return;
        }

        try {
            $this->link = $linkService->update($link, [
                'destination_url' => trim($this->destination_url),
                'description' => $this->description,
                'tags' => $this->tags !== null && trim($this->tags) !== '' ? LinkService::normalizeTags($this->tags) : null,
                'expires_at' => $this->expires_at ? new \DateTime($this->expires_at) : null,
                'is_active' => $this->is_active,
            ]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
        }

        $this->syncFromModel();
        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.dashboard.link-edit-form');
    }

    /**
     * Owner-or-admin lookup; anything else is a 404 (no existence leak).
     */
    private function editableLink(string $linkId): Link
    {
        return auth()->user()->isAdmin()
            ? Link::findOrFail($linkId)
            : auth()->user()->links()->findOrFail($linkId);
    }

    private function syncFromModel(): void
    {
        $this->destination_url = (string) $this->link->destination_url;
        $this->description = $this->link->description;
        $this->tags = $this->link->tags ? implode(', ', $this->link->tags) : null;
        $this->expires_at = $this->link->expires_at?->format('Y-m-d\TH:i');
        $this->is_active = (bool) $this->link->is_active;
        $this->resetValidation();
    }

    private function expiresChanged(): bool
    {
        $original = $this->link->expires_at?->format('Y-m-d\TH:i');

        return ($this->expires_at ?: null) !== $original;
    }
}
