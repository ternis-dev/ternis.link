@if ($createdSlug)
    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="font-medium text-emerald-900 dark:text-emerald-200">Link created successfully!</p>
                <p class="mt-1 font-mono text-sm text-neutral-800 dark:text-neutral-200 select-all truncate">
                    https://{{ $createdDomain }}/{{ $createdSlug }}
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2" x-data="{ copied: false }">
                <button
                    type="button"
                    x-on:click="navigator.clipboard.writeText('https://{{ $createdDomain }}/{{ $createdSlug }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-emerald-300 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-900 shadow-sm transition hover:bg-emerald-50 dark:border-emerald-700 dark:bg-neutral-900 dark:text-emerald-200 dark:hover:bg-neutral-800"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    <span x-text="copied ? 'Copied!' : 'Copy'">Copy</span>
                </button>
                <a
                    href="https://{{ $createdDomain }}/{{ $createdSlug }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-transparent px-2 py-1.5 text-xs font-medium text-emerald-700 hover:text-emerald-900 dark:text-emerald-300 dark:hover:text-emerald-100"
                >
                    Visit &rarr;
                </a>
            </div>
        </div>
        <div class="mt-3 flex items-center gap-3 border-t border-emerald-200/60 pt-3 dark:border-emerald-800/60">
            <button
                type="button"
                wire:click="createAnother"
                class="cursor-pointer text-xs font-semibold underline underline-offset-2 hover:opacity-80"
            >
                + Create another link
            </button>
            @if ($modal)
                <button
                    type="button"
                    x-on:click="$dispatch('close-link-creator')"
                    class="cursor-pointer text-xs text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200"
                >
                    Done (close)
                </button>
            @endif
        </div>
    </div>
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
