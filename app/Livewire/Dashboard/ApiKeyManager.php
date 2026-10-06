<?php

namespace App\Livewire\Dashboard;

use App\Models\ActivityLog;
use App\Models\ApiKey;
use App\Models\ApiVersion;
use App\Support\Activity;
use App\Support\DomainUrls;
use App\Support\Notifier;
use Illuminate\Support\Str;
use Livewire\Component;

class ApiKeyManager extends Component
{
    public string $keyName = '';

    public bool $newKeyShowOnDashboard = true;

    public ?string $newlyCreatedKey = null;

    public ?string $editingKeyId = null;

    public string $editingKeyName = '';

    /**
     * Generate a new API key.
     */
    public function createKey(): void
    {
        $this->validate([
            'keyName' => ['required', 'string', 'max:255'],
            'newKeyShowOnDashboard' => ['boolean'],
        ]);

        // Generate a raw key with tl_ prefix. Only the SHA-256 digest
        // is persisted (ApiKey::hashToken); the raw token lives only in
        // $newlyCreatedKey and is rendered ONCE in the success alert.
        $rawKey = 'tl_'.Str::random(48);

        $key = ApiKey::create([
            'user_id' => auth()->id(),
            'key_hash' => ApiKey::hashToken($rawKey),
            'key_prefix' => substr($rawKey, 0, 8),
            'api_version' => ApiVersion::latestVersion(),
            'name' => $this->keyName,
            'show_on_dashboard' => $this->newKeyShowOnDashboard,
        ]);

        Activity::record(ActivityLog::API_KEY_CREATED, auth()->user(), $key, [
            'name' => $key->name,
            'key_prefix' => $key->key_prefix,
            'show_on_dashboard' => $key->show_on_dashboard,
        ]);

        Notifier::security(
            auth()->user(),
            'New API key created',
            ["A new API key “{$key->name}” ({$key->key_prefix}…) was created on your account."],
            DomainUrls::dashboard('/api-keys'),
            'View API keys',
        );

        // Show the key to the user ONCE
        $this->newlyCreatedKey = $rawKey;
        $this->keyName = '';
        $this->newKeyShowOnDashboard = true;

        $this->dispatch('notify', message: "API key “{$key->name}” created — copy it now, it won't be shown again.", type: 'success');
    }

    /**
     * Toggle whether links made with this key appear on the main
     * dashboard list. Hidden keys keep their links on a dedicated
     * per-key page (nothing is moved or deleted).
     */
    public function toggleVisibility(string $keyId): void
    {
        $key = auth()->user()->apiKeys()->findOrFail($keyId);
        $key->update(['show_on_dashboard' => ! $key->show_on_dashboard]);

        Activity::record(ActivityLog::API_KEY_UPDATED, auth()->user(), $key->fresh(), [
            'name' => $key->name,
            'key_prefix' => $key->key_prefix,
            'show_on_dashboard' => $key->fresh()->show_on_dashboard,
        ]);

        $this->dispatch('notify', message: $key->fresh()->show_on_dashboard ? "Links for “{$key->name}” show on the dashboard." : "Links for “{$key->name}” moved to its key page.", type: 'info');
    }

    public function startEditing(string $keyId): void
    {
        $key = auth()->user()->apiKeys()->findOrFail($keyId);
        $this->editingKeyId = $key->id;
        $this->editingKeyName = $key->name;
    }

    public function cancelEditing(): void
    {
        $this->editingKeyId = null;
        $this->editingKeyName = '';
        $this->resetValidation();
    }

    public function saveKeyName(): void
    {
        if (! $this->editingKeyId) {
            return;
        }

        $this->validate([
            'editingKeyName' => ['required', 'string', 'max:255'],
        ]);

        $key = auth()->user()->apiKeys()->findOrFail($this->editingKeyId);
        $key->update(['name' => $this->editingKeyName]);

        Activity::record(ActivityLog::API_KEY_UPDATED, auth()->user(), $key->fresh(), [
            'name' => $key->fresh()->name,
            'key_prefix' => $key->key_prefix,
            'show_on_dashboard' => $key->fresh()->show_on_dashboard,
        ]);

        $this->cancelEditing();

        $this->dispatch('notify', message: "API key renamed to “{$key->fresh()->name}”.", type: 'success');
    }

    public function revokeKey(string $keyId): void
    {
        $key = auth()->user()->apiKeys()->findOrFail($keyId);
        $key->update(['revoked_at' => now()]);

        Activity::record(ActivityLog::API_KEY_REVOKED, auth()->user(), $key, [
            'name' => $key->name,
            'key_prefix' => $key->key_prefix,
        ]);

        Notifier::security(
            auth()->user(),
            'API key revoked',
            ["The API key “{$key->name}” ({$key->key_prefix}…) on your account was revoked."],
            DomainUrls::dashboard('/api-keys'),
            'View API keys',
        );

        $this->dispatch('notify', message: "API key “{$key->name}” revoked.", type: 'error');
    }

    public function dismissNewKey(): void
    {
        $this->newlyCreatedKey = null;
    }

    public function render()
    {
        $apiKeys = auth()->user()
            ->apiKeys()
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.dashboard.api-key-manager', compact('apiKeys'));
    }
}
