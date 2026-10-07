<div>
    @if ($saved)
        <x-ui.alert tone="success" class="mb-6 max-w-2xl">
            Settings saved. Navigation layout applies immediately; theme applies on this device now and everywhere on next visit.
        </x-ui.alert>
    @endif

    <form wire:submit="save" class="max-w-2xl space-y-6">
        <x-ui.card title="Navigation Layout">
            <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">Choose how the dashboard navigation is arranged.</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label @class(['cursor-pointer rounded-lg border-2 p-4 transition-colors', $nav_layout === 'side' ? 'border-neutral-900 dark:border-white' : 'border-neutral-200 hover:border-neutral-400 dark:border-neutral-800 dark:hover:border-neutral-600'])>
                    <span class="flex items-center gap-2 font-medium">
                        <input type="radio" wire:model.live="nav_layout" value="side" class="accent-neutral-900 dark:accent-white">
                        Side navigation
                    </span>
                    <span class="mt-2 flex gap-2" aria-hidden="true">
                        <span class="h-12 w-8 rounded bg-neutral-900 dark:bg-white"></span>
                        <span class="h-12 flex-1 rounded bg-neutral-100 dark:bg-neutral-800"></span>
                    </span>
                </label>
                <label @class(['cursor-pointer rounded-lg border-2 p-4 transition-colors', $nav_layout === 'top' ? 'border-neutral-900 dark:border-white' : 'border-neutral-200 hover:border-neutral-400 dark:border-neutral-800 dark:hover:border-neutral-600'])>
                    <span class="flex items-center gap-2 font-medium">
                        <input type="radio" wire:model.live="nav_layout" value="top" class="accent-neutral-900 dark:accent-white">
                        Top navigation
                    </span>
                    <span class="mt-2 block" aria-hidden="true">
                        <span class="mb-2 block h-5 rounded bg-neutral-900 dark:bg-white"></span>
                        <span class="block h-12 rounded bg-neutral-100 dark:bg-neutral-800"></span>
                    </span>
                </label>
            </div>
            @error('nav_layout') <p class="mt-2 text-xs font-medium" role="alert">{{ $message }}</p> @enderror
        </x-ui.card>

        <x-ui.card title="Color Theme">
            <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">System follows your device setting. The header toggle always overrides for this browser.</p>
            <div class="max-w-xs">
                <x-ui.select label="Theme" name="theme" wire:model.live="theme">
                    <option value="system">System</option>
                    <option value="light">Light</option>
                    <option value="dark">Dark</option>
                </x-ui.select>
            </div>
            @error('theme') <p class="mt-2 text-xs font-medium" role="alert">{{ $message }}</p> @enderror
        </x-ui.card>

        <x-ui.card title="Domain Preferences">
            <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">Choose your default domain and customize the order domains appear when creating links.</p>

            <div class="mb-6 max-w-xs">
                <x-ui.select label="Default Domain" name="default_domain_id" wire:model.live="default_domain_id">
                    <option value="">First domain in list</option>
                    @foreach ($availableDomains as $domain)
                        <option value="{{ $domain->id }}">{{ $domain->hostname }}</option>
                    @endforeach
                </x-ui.select>
                <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Pre-selected when creating new links or importing CSVs.</p>
                @error('default_domain_id') <p class="mt-2 text-xs font-medium text-red-600 dark:text-red-400" role="alert">{{ $message }}</p> @enderror
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-sm font-medium text-neutral-900 dark:text-white">Domain Order</span>
                    <button type="button" wire:click="resetDomainOrder" class="cursor-pointer text-xs font-semibold text-neutral-500 underline underline-offset-2 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">Reset to alphabetical</button>
                </div>
                <p class="mb-3 text-xs text-neutral-500 dark:text-neutral-400">Use arrows to adjust the order domains appear in dropdowns.</p>

                <div class="space-y-1.5 rounded-lg border border-neutral-200 p-2 dark:border-neutral-800">
                    @foreach ($orderedDomains as $index => $domain)
                        <div class="flex items-center justify-between rounded-md bg-neutral-50 px-3 py-2 text-sm dark:bg-neutral-800/50">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs text-neutral-400 dark:text-neutral-500">{{ $index + 1 }}.</span>
                                <span class="font-medium text-neutral-900 dark:text-white">{{ $domain->hostname }}</span>
                                @if ($domain->id === $default_domain_id)
                                    <span class="rounded bg-neutral-200 px-1.5 py-0.5 text-[10px] font-semibold text-neutral-700 dark:bg-neutral-700 dark:text-neutral-300">Default</span>
                                @endif
                                @if ($domain->user_id)
                                    <span class="rounded bg-indigo-100 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">Custom</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    wire:click="moveDomain('{{ $domain->id }}', 'up')"
                                    @disabled($loop->first)
                                    class="cursor-pointer rounded p-1 text-neutral-400 hover:bg-neutral-200 hover:text-neutral-900 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:bg-neutral-700 dark:hover:text-white"
                                    aria-label="Move {{ $domain->hostname }} up"
                                >
                                    ↑
                                </button>
                                <button
                                    type="button"
                                    wire:click="moveDomain('{{ $domain->id }}', 'down')"
                                    @disabled($loop->last)
                                    class="cursor-pointer rounded p-1 text-neutral-400 hover:bg-neutral-200 hover:text-neutral-900 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:bg-neutral-700 dark:hover:text-white"
                                    aria-label="Move {{ $domain->hostname }} down"
                                >
                                    ↓
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('domain_order') <p class="mt-2 text-xs font-medium text-red-600 dark:text-red-400" role="alert">{{ $message }}</p> @enderror
            </div>
        </x-ui.card>

        <x-ui.card title="Email Notifications">
            <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">Important events always appear in your in-app inbox. These toggles control whether you also get an email.</p>
            <div class="space-y-3">
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" wire:model.live="notify_security_email" value="1" class="mt-1 accent-neutral-900 dark:accent-white">
                    <span>
                        <span class="block text-sm font-medium">Security events</span>
                        <span class="block text-xs text-neutral-500 dark:text-neutral-400">API keys, domains, role or plan changes on your account.</span>
                    </span>
                </label>
                @if (auth()->user()->isAdmin())
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" wire:model.live="notify_admin_security_email" value="1" class="mt-1 accent-neutral-900 dark:accent-white">
                        <span>
                            <span class="block text-sm font-medium">Admin security notices</span>
                            <span class="block text-xs text-neutral-500 dark:text-neutral-400">Privilege and plan changes performed by other admins.</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" wire:model.live="notify_server_error_email" value="1" class="mt-1 accent-neutral-900 dark:accent-white">
                        <span>
                            <span class="block text-sm font-medium">Server error alerts</span>
                            <span class="block text-xs text-neutral-500 dark:text-neutral-400">One email per error type every 30 minutes when 5xx responses are rendered.</span>
                        </span>
                    </label>
                @endif
            </div>
        </x-ui.card>

        <x-ui.button type="submit" variant="primary">Save Settings</x-ui.button>
    </form>
</div>
