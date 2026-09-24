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
