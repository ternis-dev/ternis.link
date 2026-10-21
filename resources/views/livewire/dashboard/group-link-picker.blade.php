<div>
    <input
        type="search"
        wire:model.live.debounce.300ms="search"
        placeholder="Search your links to add… (slug, URL, description)"
        class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-neutral-900 focus:ring-2 focus:ring-neutral-900/15 focus:outline-none dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:placeholder:text-neutral-600 dark:focus:border-white dark:focus:ring-white/15"
    />

    @if ($search !== '' && $results->isNotEmpty())
        <div class="mt-2 rounded-xl border border-neutral-200 divide-y divide-neutral-100 dark:border-neutral-800 dark:divide-neutral-800 overflow-hidden bg-white dark:bg-neutral-900">
            @foreach ($results as $link)
                <label class="flex cursor-pointer items-center gap-3 px-3 py-2.5 text-sm hover:bg-neutral-50 dark:hover:bg-neutral-800/60 transition-colors">
                    <input type="checkbox" wire:model="selected" value="{{ $link->id }}" class="h-4 w-4 cursor-pointer rounded border border-neutral-300 bg-white text-neutral-900 shadow-xs transition focus:ring-2 focus:ring-neutral-900/15 focus:ring-offset-0 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100 dark:checked:bg-white dark:checked:border-white accent-neutral-900 dark:accent-white" />
                    <span class="truncate font-mono text-neutral-900 dark:text-neutral-100">{{ $link->domain?->hostname }}/{{ $link->slug }}</span>
                    <span class="ml-auto shrink-0 text-xs text-neutral-400">{{ number_format($link->click_count) }} clicks</span>
                </label>
            @endforeach
            <div class="border-t border-neutral-200 p-2 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-950/40">
                <button
                    type="button"
                    wire:click="add"
                    class="w-full cursor-pointer rounded-lg bg-neutral-900 px-3 py-2 text-sm font-semibold text-white hover:bg-neutral-800 disabled:opacity-40 dark:bg-white dark:text-neutral-900 transition-colors"
                    @disabled(empty($selected))
                >Add {{ count($selected) }} selected link(s)</button>
            </div>
        </div>
    @elseif ($search !== '')
        <p class="mt-2 text-xs text-neutral-500">No matching links (already-added links are hidden).</p>
    @endif
</div>
