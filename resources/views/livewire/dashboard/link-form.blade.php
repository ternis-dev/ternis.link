<x-ui.card title="New Short Link">
    @if ($createdSlug)
        <x-ui.alert tone="success" class="mb-6">
            <strong>Link created successfully!</strong><br>
            Short URL: <code>https://{{ $createdDomain }}/{{ $createdSlug }}</code>
        </x-ui.alert>
    @endif

    <form wire:submit="create" class="space-y-5">
        <x-ui.input
            label="Destination URL *"
            name="destination_url"
            type="url"
            wire:model="destination_url"
            placeholder="https://example.com/very-long-url"
            required
        />

        <x-ui.select label="Domain *" name="domain_id" wire:model="domain_id" required>
            @foreach ($domains as $domain)
                <option value="{{ $domain->id }}">{{ $domain->hostname }} ({{ $domain->type->value ?? $domain->type }})</option>
            @endforeach
        </x-ui.select>

        <x-ui.input
            label="Custom Slug (optional)"
            name="slug"
            type="text"
            wire:model="slug"
            placeholder="Leave blank for automatic generation"
            hint="Alphanumeric characters, dashes, and underscores only. Min length: {{ auth()->user()->plan?->min_slug_length ?? \App\Services\LinkService::AUTHENTICATED_DEFAULT_SLUG_LENGTH }} chars."
        />

        <x-ui.input
            label="Expiration Date (optional)"
            name="expires_at"
            type="datetime-local"
            wire:model="expires_at"
        />

        <div class="flex gap-3 pt-1">
            <x-ui.button type="submit" variant="primary">Create Short Link</x-ui.button>
            <x-ui.button href="{{ route('dashboard.links') }}">Cancel</x-ui.button>
        </div>
    </form>
</x-ui.card>
