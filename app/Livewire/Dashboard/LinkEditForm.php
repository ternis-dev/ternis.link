<?php

namespace App\Livewire\Dashboard;

use App\Models\ActivityLog;
use App\Models\Link;
use App\Services\LinkService;
use App\Support\Activity;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class LinkEditForm extends Component
{
    public Link $link;

    public string $destination_url = '';

    public ?string $description = null;

    public ?string $og_title = null;

    public ?string $og_description = null;

    public ?string $og_image_url = null;

    public ?string $password = null;

    public bool $has_password = false;

    public ?string $utm_source = null;

    public ?string $utm_medium = null;

    public ?string $utm_campaign = null;

    public ?string $tags = null;

    public bool $user_tracking_enabled = false;

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
            'og_title' => ['nullable', 'string', 'max:120'],
            'og_description' => ['nullable', 'string', 'max:300'],
            'og_image_url' => ['nullable', 'url', 'starts_with:https', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'max:72'],
            'utm_source' => ['nullable', 'string', 'max:100', 'regex:'.LinkService::UTM_PATTERN],
            'utm_medium' => ['nullable', 'string', 'max:100', 'regex:'.LinkService::UTM_PATTERN],
            'utm_campaign' => ['nullable', 'string', 'max:100', 'regex:'.LinkService::UTM_PATTERN],
            'tags' => ['nullable', 'string', 'max:255'],
            'user_tracking_enabled' => ['boolean'],
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
                'og_title' => $this->og_title,
                'og_description' => $this->og_description,
                'og_image_url' => $this->og_image_url,
                'password' => $this->password !== null && trim($this->password) !== '' ? $this->password : null,
                'utm_source' => $this->utm_source,
                'utm_medium' => $this->utm_medium,
                'utm_campaign' => $this->utm_campaign,
                'tags' => $this->tags !== null && trim($this->tags) !== '' ? LinkService::normalizeTags($this->tags) : null,
                'user_tracking_enabled' => $this->user_tracking_enabled,
                'expires_at' => $this->expires_at ? new \DateTime($this->expires_at) : null,
                'is_active' => $this->is_active,
            ], auth()->user());
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

        Activity::record(ActivityLog::LINK_UPDATED, auth()->user(), $this->link, [
            'slug' => $this->link->slug,
        ]);
    }

    public function removePassword(LinkService $linkService): void
    {
        $link = $this->editableLink($this->link->id);
        $linkService->clearPassword($link);
        $this->link = $link->fresh();
        $this->syncFromModel();
        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.dashboard.link-edit-form');
    }

    /**
     * Owner-only lookup; anything else is a 404 (no existence leak).
     * Cross-user edits happen on the admin host, never from dash.
     */
    private function editableLink(string $linkId): Link
    {
        return auth()->user()->links()->notRemoved()->findOrFail($linkId);
    }

    private function syncFromModel(): void
    {
        $this->destination_url = (string) $this->link->destination_url;
        $this->description = $this->link->description;
        $this->og_title = $this->link->og_title;
        $this->og_description = $this->link->og_description;
        $this->og_image_url = $this->link->og_image_url;
        $this->password = null;
        $this->has_password = $this->link->password_hash !== null;
        $this->utm_source = $this->link->utm_source;
        $this->utm_medium = $this->link->utm_medium;
        $this->utm_campaign = $this->link->utm_campaign;
        $this->tags = $this->link->tags ? implode(', ', $this->link->tags) : null;
        $this->user_tracking_enabled = (bool) $this->link->user_tracking_enabled;
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
