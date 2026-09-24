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
            @error('nav_layout') <p class="mt-2 text-xs font-medium text-red-600 dark:text-red-400" role="alert">{{ $message }}</p> @enderror
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
            @error('theme') <p class="mt-2 text-xs font-medium text-red-600 dark:text-red-400" role="alert">{{ $message }}</p> @enderror
        </x-ui.card>

        <x-ui.button type="submit" variant="primary">Save Settings</x-ui.button>
    </form>
</div>
