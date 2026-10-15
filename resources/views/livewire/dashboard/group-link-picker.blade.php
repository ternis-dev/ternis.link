<div>
    <input
        type="search"
        wire:model.live.debounce.300ms="search"
        placeholder="Search your links to add… (slug, URL, description)"
        class="w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
    />

    @if ($search !== '' && $results->isNotEmpty())
        <div class="mt-2 rounded-xl border border-neutral-200 dark:border-neutral-800">
            @foreach ($results as $link)
                <label class="flex cursor-pointer items-center gap-2 px-3 py-2 text-sm hover:bg-neutral-50 dark:hover:bg-neutral-800">
                    <input type="checkbox" wire:model="selected" value="{{ $link->id }}" class="h-4 w-4 rounded" />
                    <span class="truncate font-mono">{{ $link->domain?->hostname }}/{{ $link->slug }}</span>
                    <span class="ml-auto shrink-0 text-xs text-neutral-400">{{ number_format($link->click_count) }}</span>
                </label>
            @endforeach
            <div class="border-t border-neutral-200 p-2 dark:border-neutral-800">
                <button
                    type="button"
                    wire:click="add"
                    class="w-full cursor-pointer rounded-lg bg-neutral-900 px-3 py-2 text-sm font-semibold text-white hover:bg-neutral-800 disabled:opacity-40 dark:bg-white dark:text-neutral-900"
                    @disabled(empty($selected))
                >Add {{ count($selected) }} selected link(s)</button>
            </div>
        </div>
    @elseif ($search !== '')
        <p class="mt-2 text-xs text-neutral-500">No matching links (already-added links are hidden).</p>
    @endif
</div>
