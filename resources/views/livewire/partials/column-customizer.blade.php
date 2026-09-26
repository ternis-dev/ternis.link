{{--
    Column customizer (Cloudflare-style): toggle visibility + reorder.
    Expects: $availableColumns (key => label), $hiddenColumns, $columnOrder.
    Must be rendered inside a Livewire component using WithTableColumns.
--}}
<div x-data="{ open: false }" @click.away="open = false" class="relative">
    <x-ui.button type="button" size="sm" variant="ghost" @click="open = !open" aria-label="Customize columns">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/><circle cx="9" cy="6" r="2" fill="currentColor" stroke="none"/><circle cx="15" cy="12" r="2" fill="currentColor" stroke="none"/><circle cx="7" cy="18" r="2" fill="currentColor" stroke="none"/></svg>
        Columns
    </x-ui.button>

    <div
        x-show="open"
        x-transition
        class="absolute right-0 z-30 mt-2 w-64 rounded-xl border border-neutral-200 bg-white p-2 shadow-lg dark:border-neutral-700 dark:bg-neutral-900"
        style="display: none;"
    >
        <p class="px-2 pt-1 pb-2 text-xs font-semibold tracking-wide text-neutral-500 uppercase dark:text-neutral-400">Visible columns</p>
        @foreach ($columnOrder as $key)
            @if (isset($availableColumns[$key]))
                <div class="flex items-center gap-1 rounded-lg px-1 py-1 hover:bg-neutral-100 dark:hover:bg-neutral-800">
                    <button
                        type="button"
                        wire:click="toggleColumn('{{ $key }}')"
                        class="flex flex-1 cursor-pointer items-center gap-2 px-1 py-1 text-left text-sm"
                        title="{{ in_array($key, $hiddenColumns, true) ? 'Show' : 'Hide' }} {{ $availableColumns[$key] }}"
                    >
                        <span class="inline-flex h-4 w-4 items-center justify-center rounded border {{ in_array($key, $hiddenColumns, true) ? 'border-neutral-300 bg-transparent dark:border-neutral-600' : 'border-neutral-900 bg-neutral-900 text-white dark:border-white dark:bg-white dark:text-neutral-900' }}">
                            @if (! in_array($key, $hiddenColumns, true))
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            @endif
                        </span>
                        <span class="{{ in_array($key, $hiddenColumns, true) ? 'text-neutral-400 dark:text-neutral-500' : 'text-neutral-900 dark:text-white' }}">{{ $availableColumns[$key] }}</span>
                    </button>
                    <button type="button" wire:click="moveColumn('{{ $key }}', 'up')" class="cursor-pointer rounded p-1 text-neutral-400 hover:bg-neutral-200 hover:text-neutral-900 dark:hover:bg-neutral-700 dark:hover:text-white" aria-label="Move {{ $availableColumns[$key] }} up">↑</button>
                    <button type="button" wire:click="moveColumn('{{ $key }}', 'down')" class="cursor-pointer rounded p-1 text-neutral-400 hover:bg-neutral-200 hover:text-neutral-900 dark:hover:bg-neutral-700 dark:hover:text-white" aria-label="Move {{ $availableColumns[$key] }} down">↓</button>
                </div>
            @endif
        @endforeach
        <div class="mt-1 border-t border-neutral-200 pt-2 pb-1 text-right dark:border-neutral-700">
            <button type="button" wire:click="resetColumns" class="cursor-pointer px-2 text-xs font-semibold text-neutral-500 underline underline-offset-2 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">Reset to defaults</button>
        </div>
    </div>
</div>
