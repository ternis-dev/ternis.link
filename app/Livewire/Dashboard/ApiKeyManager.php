<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\WithTableColumns;
use App\Models\ActivityLog;
use App\Models\ApiKey;
use App\Models\ApiVersion;
use App\Support\Activity;
use App\Support\DomainUrls;
use App\Support\Notifier;
use App\Support\PublicDashboard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;

class ApiKeyManager extends Component
{
    use WithTableColumns;

    /**
     * Presentation theme: 'dashboard' (dash.ternis.link) or 'public'
     * (public dashboard). Only the per-key link and the notification
     * target differ.
     */
    public string $theme = 'dashboard';

    protected function tableKey(): string
    {
        return 'dashboard.api-keys';
    }

    protected function availableColumns(): array
    {
        return [
            'name' => 'Name',
            'prefix' => 'Prefix',
            'version' => 'API Version',
            'created' => 'Created',
            'expires' => 'Expires',
            'last_used' => 'Last Used',
            'dashboard' => 'Dashboard',
            'status' => 'Status',
        ];
    }

    public string $keyName = '';

    public ?int $apiVersion = null;

    public string $expirationChoice = 'never';

    public ?string $customExpiresAt = null;

    public bool $newKeyShowOnDashboard = true;

    public ?string $newlyCreatedKey = null;

    // Settings Modal state
    public bool $showingSettingsModal = false;

    public ?string $settingsKeyId = null;

    public string $settingsName = '';

    public int $settingsApiVersion = 2;

    public bool $settingsShowOnDashboard = true;

    public ?string $settingsExpiresAt = null;

    public ?string $editingKeyId = null;

    public string $editingKeyName = '';

    public function mount(): void
    {
        $this->apiVersion = ApiVersion::latestVersion();
    }

    /**
     * Generate a new API key.
     */
    public function createKey(): void
    {
        $this->validate([
            'keyName' => ['required', 'string', 'max:255'],
            'apiVersion' => ['required', 'integer', 'exists:api_versions,version'],
            'newKeyShowOnDashboard' => ['boolean'],
            'customExpiresAt' => ['nullable', 'date'],
        ]);

        $expiresAt = match ($this->expirationChoice) {
            '30_days' => now()->addDays(30),
            '60_days' => now()->addDays(60),
            '90_days' => now()->addDays(90),
            '1_year' => now()->addYear(),
            'custom' => $this->customExpiresAt ? Carbon::parse($this->customExpiresAt) : null,
            default => null,
        };

        // Generate a raw key with tl_ prefix. Only the SHA-256 digest
        // is persisted (ApiKey::hashToken); the raw token lives only in
        // $newlyCreatedKey and is rendered ONCE in the success alert.
        $rawKey = 'tl_'.Str::random(48);

        $key = ApiKey::create([
            'user_id' => auth()->id(),
            'key_hash' => ApiKey::hashToken($rawKey),
            'key_prefix' => substr($rawKey, 0, 8),
            'api_version' => $this->apiVersion ?: ApiVersion::latestVersion(),
            'name' => $this->keyName,
            'show_on_dashboard' => $this->newKeyShowOnDashboard,
            'expires_at' => $expiresAt,
        ]);

        Activity::record(ActivityLog::API_KEY_CREATED, auth()->user(), $key, [
            'name' => $key->name,
            'key_prefix' => $key->key_prefix,
            'api_version' => $key->api_version,
            'show_on_dashboard' => $key->show_on_dashboard,
            'expires_at' => $key->expires_at?->toIso8601String(),
        ]);

        Notifier::security(
            auth()->user(),
            'New API key created',
            ["A new API key “{$key->name}” ({$key->key_prefix}…) was created on your account."],
            $this->apiKeysUrl(),
            'View API keys',
        );

        // Show the key to the user ONCE
        $this->newlyCreatedKey = $rawKey;
        $this->keyName = '';
        $this->apiVersion = ApiVersion::latestVersion();
        $this->expirationChoice = 'never';
        $this->customExpiresAt = null;
        $this->newKeyShowOnDashboard = true;

        $this->dispatch('notify', message: "API key “{$key->name}” created — copy it now, it won't be shown again.", type: 'success');
    }

    /**
     * Open settings editor for an existing API key.
     */
    public function openSettings(string $keyId): void
    {
        $key = auth()->user()->apiKeys()->findOrFail($keyId);
        $this->settingsKeyId = $key->id;
        $this->settingsName = $key->name;
        $this->settingsApiVersion = $key->api_version;
        $this->settingsShowOnDashboard = (bool) $key->show_on_dashboard;
        $this->settingsExpiresAt = $key->expires_at?->format('Y-m-d\TH:i');
        $this->showingSettingsModal = true;
        $this->resetValidation();
    }

    public function closeSettings(): void
    {
        $this->showingSettingsModal = false;
        $this->settingsKeyId = null;
        $this->resetValidation();
    }

    public function saveSettings(): void
    {
        if (! $this->settingsKeyId) {
            return;
        }

        $this->validate([
            'settingsName' => ['required', 'string', 'max:255'],
            'settingsApiVersion' => ['required', 'integer', 'exists:api_versions,version'],
            'settingsShowOnDashboard' => ['boolean'],
            'settingsExpiresAt' => ['nullable', 'date'],
        ]);

        $key = auth()->user()->apiKeys()->findOrFail($this->settingsKeyId);
        $key->update([
            'name' => $this->settingsName,
            'api_version' => $this->settingsApiVersion,
            'show_on_dashboard' => $this->settingsShowOnDashboard,
            'expires_at' => $this->settingsExpiresAt ? Carbon::parse($this->settingsExpiresAt) : null,
        ]);

        Activity::record(ActivityLog::API_KEY_UPDATED, auth()->user(), $key->fresh(), [
            'name' => $key->fresh()->name,
            'key_prefix' => $key->key_prefix,
            'show_on_dashboard' => $key->fresh()->show_on_dashboard,
            'api_version' => $key->fresh()->api_version,
            'expires_at' => $key->fresh()->expires_at?->toIso8601String(),
        ]);

        $this->closeSettings();
        $this->dispatch('notify', message: "API key “{$key->fresh()->name}” settings saved.", type: 'success');
    }

    /**
     * Quick inline toggle for whether links made with this key appear on the main
     * dashboard list.
     */
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

    /**
     * Quick upgrade or change of an API key's target API version.
     */
    public function updateVersion(string $keyId, int $version): void
    {
        $key = auth()->user()->apiKeys()->findOrFail($keyId);

        $targetVersion = ApiVersion::where('version', $version)->firstOrFail();

        $key->update(['api_version' => $targetVersion->version]);

        Activity::record(ActivityLog::API_KEY_UPDATED, auth()->user(), $key->fresh(), [
            'name' => $key->name,
            'key_prefix' => $key->key_prefix,
            'api_version' => $key->fresh()->api_version,
        ]);

        $this->dispatch('notify', message: "API key “{$key->name}” switched to v{$key->fresh()->api_version}.", type: 'success');
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
            $this->apiKeysUrl(),
            'View API keys',
        );

        $this->dispatch('notify', message: "API key “{$key->name}” revoked.", type: 'error');
    }

    public function dismissNewKey(): void
    {
        $this->newlyCreatedKey = null;
    }

    /**
     * Absolute URL of the API keys page for the current theme.
     */
    private function apiKeysUrl(): string
    {
        return $this->theme === 'public'
            ? DomainUrls::publicDashboard('/api-keys')
            : DomainUrls::dashboard('/api-keys');
    }

    /**
     * Route name prefix of the per-key page for the current theme.
     */
    public function routePrefix(): string
    {
        return $this->theme === 'public'
            ? PublicDashboard::routePrefix()
            : 'dashboard.';
    }

    public function render()
    {
        $apiKeys = auth()->user()
            ->apiKeys()
            ->orderByDesc('created_at')
            ->get();

        $availableVersions = ApiVersion::orderByDesc('version')->get();
        $latestVersion = ApiVersion::latestVersion();

        $visibleColumns = $this->visibleColumns();

        return view('livewire.dashboard.api-key-manager', [
            'apiKeys' => $apiKeys,
            'availableVersions' => $availableVersions,
            'latestVersion' => $latestVersion,
            'availableColumns' => $this->availableColumns(),
            'visibleColumns' => $visibleColumns,
            'routePrefix' => $this->routePrefix(),
            'columnCount' => count($visibleColumns) + 1, // + fixed Actions column
        ]);
    }
}
