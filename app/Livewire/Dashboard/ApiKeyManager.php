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

    public ?string $newlyCreatedKey = null;

    protected array $rules = [
        'keyName' => ['required', 'string', 'max:255'],
    ];

    /**
     * Generate a new API key.
     */
    public function createKey(): void
    {
        $this->validate();

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
        ]);

        Activity::record(ActivityLog::API_KEY_CREATED, auth()->user(), $key, [
            'name' => $key->name,
            'key_prefix' => $key->key_prefix,
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
