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

        <x-ui.input
            label="UTM source"
            name="utm_source"
            type="text"
            wire:model="utm_source"
            placeholder="newsletter"
            maxlength="100"
        />

        <x-ui.input
            label="UTM medium"
            name="utm_medium"
            type="text"
            wire:model="utm_medium"
            placeholder="email"
            maxlength="100"
        />

        <x-ui.input
            label="UTM campaign"
            name="utm_campaign"
            type="text"
            wire:model="utm_campaign"
            placeholder="spring-launch"
            hint="Appended at redirect time. Parameters your destination already sets are left alone."
            maxlength="100"
        />

        <details class="group rounded-xl border border-neutral-200 bg-neutral-50/40 transition-colors hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900/30 dark:hover:border-neutral-700" open>
            <summary class="flex cursor-pointer items-center justify-between px-4 py-3 text-sm font-semibold text-neutral-800 select-none list-none [&::-webkit-details-marker]:hidden dark:text-neutral-200">
                <span class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                    Social preview
                </span>
                <svg class="h-4 w-4 text-neutral-400 transition-transform duration-200 group-open:rotate-180 dark:text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="space-y-4 border-t border-neutral-200/60 px-4 pt-3 pb-4 dark:border-neutral-800/60">
                <x-ui.input label="Preview title" name="og_title" type="text" wire:model="og_title" maxlength="120" />
                <x-ui.input label="Preview description" name="og_description" type="text" wire:model="og_description" maxlength="300" />
                <x-ui.input label="Preview image URL" name="og_image_url" type="url" wire:model="og_image_url" hint="https image only." maxlength="2048" />
            </div>
        </details>

        <details class="group rounded-xl border border-neutral-200 bg-neutral-50/40 transition-colors hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900/30 dark:hover:border-neutral-700" open>
            <summary class="flex cursor-pointer items-center justify-between px-4 py-3 text-sm font-semibold text-neutral-800 select-none list-none [&::-webkit-details-marker]:hidden dark:text-neutral-200">
                <span class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    User tracking &amp; parameters (optional)
                </span>
                <svg class="h-4 w-4 text-neutral-400 transition-transform duration-200 group-open:rotate-180 dark:text-neutral-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="space-y-4 border-t border-neutral-200/60 px-4 pt-3 pb-4 dark:border-neutral-800/60">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" wire:model="user_tracking_enabled" class="mt-1 rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900 dark:border-neutral-700 dark:bg-neutral-800">
                    <div class="text-sm">
                        <span class="font-medium text-neutral-800 dark:text-neutral-200">Enable user tracking</span>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Captures user/subscriber identifiers passed in the URL (e.g. <code>?uid=</code>, <code>?email=</code>) following privacy best practices (raw emails are automatically pseudonymized with SHA-256). Respects Do Not Track.</p>
                    </div>
                </label>
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
