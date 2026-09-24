<?php

namespace App\Livewire\Dashboard;

use App\Models\ApiKey;
use App\Models\ApiVersion;
use Illuminate\Support\Str;
use Livewire\Component;

class ApiKeyManager extends Component
{
    public string $keyName = '';

    public ?string $newlyCreatedKey = null;

    protected array $rules = [
        'keyName' => ['required', 'string', 'max:255'],
    ];

    protected function messages(): array
    {
        return [
            'keyName.required' => 'Give the key a label so you can recognise it later.',
            'keyName.max' => 'That label is too long — keep it under 255 characters.',
        ];
    }

    /**
     * Generate a new API key.
     */
    public function createKey(): void
    {
        $this->keyName = trim($this->keyName);
        $this->validate();

        // Generate a raw key with tl_ prefix. Only the SHA-256 digest
        // is persisted (ApiKey::hashToken); the raw token lives only in
        // $newlyCreatedKey and is rendered ONCE in the success alert.
        $rawKey = 'tl_'.Str::random(48);

        ApiKey::create([
            'user_id' => auth()->id(),
            'key_hash' => ApiKey::hashToken($rawKey),
            'key_prefix' => substr($rawKey, 0, 8),
            'api_version' => ApiVersion::latestVersion(),
            'name' => $this->keyName,
        ]);

        // Show the key to the user ONCE
        $this->newlyCreatedKey = $rawKey;
        $this->keyName = '';
    }

    public function revokeKey(int $keyId): void
    {
        $key = auth()->user()->apiKeys()->findOrFail($keyId);
        $key->update(['revoked_at' => now()]);
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
