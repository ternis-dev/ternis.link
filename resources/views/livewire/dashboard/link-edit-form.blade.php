<x-ui.card title="Edit Short Link">
    <p class="mb-5 text-sm text-neutral-500 dark:text-neutral-400">
        Short URL: <code>https://{{ $link->domain->hostname ?? 'href.nz' }}/{{ $link->slug }}</code><br>
        The slug and domain can’t be changed — only the destination, expiry and status.
    </p>

    @if ($saved)
        <x-ui.alert tone="success" class="mb-6">
            Link updated.
        </x-ui.alert>
    @endif

    <form wire:submit="save" class="space-y-5">
        <x-ui.input
            label="Destination URL *"
            name="destination_url"
            type="url"
            wire:model="destination_url"
            placeholder="https://example.com/very-long-url"
            required
            autocomplete="off"
            autocapitalize="off"
            spellcheck="false"
            inputmode="url"
            maxlength="2048"
        />

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
            label="Link password"
            name="password"
            type="password"
            wire:model="password"
            placeholder="Min. 8 characters — blank keeps current"
            hint="Visitors must enter this before redirecting."
            maxlength="72"
            autocomplete="new-password"
        />

        @if ($has_password)
            <div>
                <p class="text-xs text-neutral-500">This link is currently password-protected.</p>
                <button type="button" wire:click="removePassword" class="mt-1 cursor-pointer text-xs font-semibold text-red-600 hover:underline">Remove password</button>
            </div>
        @endif

        <details class="rounded-xl border border-neutral-200 dark:border-neutral-800" open>
            <summary class="cursor-pointer px-4 py-3 text-sm font-semibold">Social preview</summary>
            <div class="space-y-4 px-4 pb-4">
                <x-ui.input label="Preview title" name="og_title" type="text" wire:model="og_title" maxlength="120" />
                <x-ui.input label="Preview description" name="og_description" type="text" wire:model="og_description" maxlength="300" />
                <x-ui.input label="Preview image URL" name="og_image_url" type="url" wire:model="og_image_url" hint="https image only." maxlength="2048" />
            </div>
        </details>

        <x-ui.input
            label="Expiration Date (optional)"
            name="expires_at"
            type="datetime-local"
            wire:model="expires_at"
            hint="Leave blank for no expiry. Saving an unchanged past date is fine; new dates must be in the future."
        />

        <label class="flex cursor-pointer items-center gap-2.5 text-sm font-medium text-neutral-700 dark:text-neutral-300">
            <input type="checkbox" wire:model="is_active" class="h-4 w-4 shrink-0 rounded accent-neutral-900 dark:accent-white">
            Link is active
            <span class="font-normal text-neutral-500 dark:text-neutral-500">(inactive links show the not-found page)</span>
        </label>

        <div class="flex gap-3 pt-1">
            <x-ui.button type="submit" variant="primary">Save Changes</x-ui.button>
            <x-ui.button href="{{ route('dashboard.links.show', $link->id) }}">Cancel</x-ui.button>
        </div>
    </form>
</x-ui.card>
