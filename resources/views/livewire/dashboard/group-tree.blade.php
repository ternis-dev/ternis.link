<div>
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Search groups…"
            class="w-full max-w-sm rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
        />
    </div>

    @if ($groups->isEmpty())
        <x-ui.card>
            <x-ui.empty-state>No groups yet — group links into campaigns, clients, or topics. Stats combine across the whole group, including subgroups.</x-ui.empty-state>
            <div class="mt-3 text-center">
                <x-ui.button href="{{ route('dashboard.groups.create') }}" variant="primary" size="sm">+ New Group</x-ui.button>
            </div>
        </x-ui.card>
    @else
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($groups as $group)
                <x-ui.card>
                    <div class="flex items-start gap-3">
                        @if ($group->color)
                            <span class="mt-1 h-3 w-3 shrink-0 rounded-full" style="background: {{ $group->color }}"></span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('dashboard.groups.show', $group->id) }}" class="truncate text-base font-semibold hover:underline">{{ $group->name }}</a>
                            @if ($group->description)
                                <p class="mt-0.5 line-clamp-2 text-sm text-neutral-500">{{ $group->description }}</p>
                            @endif
                            <p class="mt-1 text-xs text-neutral-500">
                                {{ number_format($group->computed_links) }} link(s) · {{ number_format($group->computed_clicks) }} click(s)
                                @if ($group->children->isNotEmpty())
                                    · {{ $group->children->count() }} subgroup(s)
                                @endif
                            </p>
                        </div>
                    </div>

                    @if ($group->children->isNotEmpty())
                        <div class="mt-3 space-y-1 border-t border-neutral-100 pt-2 dark:border-neutral-800">
                            @foreach ($group->children as $child)
                                <a href="{{ route('dashboard.groups.show', $child->id) }}" class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                    <span class="flex items-center gap-2 truncate">
                                        @if ($child->color)
                                            <span class="h-2 w-2 shrink-0 rounded-full" style="background: {{ $child->color }}"></span>
                                        @endif
                                        <span class="truncate">{{ $child->name }}</span>
                                    </span>
                                    <span class="shrink-0 text-xs text-neutral-400">{{ number_format($child->computed_clicks) }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-3 flex items-center gap-2">
                        <x-ui.button href="{{ route('dashboard.groups.show', $group->id) }}" size="sm" variant="secondary">Open</x-ui.button>
                        <x-ui.button href="{{ route('dashboard.groups.create', ['parent_id' => $group->id]) }}" size="sm" variant="secondary">+ Subgroup</x-ui.button>
                        <button
                            type="button"
                            wire:click="delete('{{ $group->id }}')"
                            wire:confirm="Delete group &quot;{{ $group->name }}&quot;? Links stay, subgroups move up."
                            class="ml-auto cursor-pointer text-xs text-red-600 hover:underline dark:text-red-400"
                        >Delete</button>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif
</div>
