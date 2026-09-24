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

        @if ($canChooseSlugLength)
            <x-ui.input
                label="Generated Slug Length"
                name="slug_length"
                type="number"
                wire:model="slug_length"
                required
                min="{{ $slugLengthMin }}"
                max="{{ $slugLengthMax }}"
                step="1"
                hint="Length of auto-generated slugs ({{ $slugLengthMin }}–{{ $slugLengthMax }} characters). Ignored when a custom slug is set."
            />
        @endif

        <x-ui.input
            label="Description (optional)"
            name="description"
            type="text"
            wire:model="description"
            placeholder="What is this link for?"
            maxlength="500"
        />

        <x-ui.input
            label="Tags (optional)"
            name="tags"
            type="text"
            wire:model="tags"
            placeholder="docs, release, q4"
            hint="Comma-separated, lowercase letters, numbers and dashes only."
            maxlength="255"
        />

        <x-ui.input
            label="Expiration Date (optional)"
            name="expires_at"
            type="datetime-local"
            wire:model="expires_at"
        />

        <div class="flex gap-3 pt-1">
            <x-ui.button type="submit" variant="primary">Create Short Link</x-ui.button>
            @if ($modal)
                <x-ui.button x-on:click="$dispatch('close-link-creator')">Close</x-ui.button>
            @else
                <x-ui.button href="{{ route('dashboard.links') }}">Cancel</x-ui.button>
            @endif
        </div>
    </form>
</x-ui.card>
