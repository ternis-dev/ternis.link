    <x-ui.page-header
        :title="$group->name"
        :backHref="$group->parent ? route($groupRoutePrefix.'groups.show', $group->parent->id) : route($groupRoutePrefix.'groups')"
        :backLabel="$group->parent ? 'Back to '.$group->parent->name : 'Back to Groups'"
    >
        <x-slot:actions>
            <x-ui.button href="{{ route($groupRoutePrefix.'groups.create', ['parent_id' => $group->id]) }}" size="sm" variant="secondary">+ Subgroup</x-ui.button>
            <x-ui.button href="{{ route($groupRoutePrefix.'groups.edit', $group->id) }}" size="sm" variant="secondary">Edit</x-ui.button>
        </x-slot:actions>
        <x-slot:subtitle>
            @foreach ($breadcrumb as $crumb)
                @if (! $loop->first)<span class="text-neutral-300"> / </span>@endif
                @if ($loop->last)<strong>{{ $crumb->name }}</strong>
                @else<a href="{{ route($groupRoutePrefix.'groups.show', $crumb->id) }}" class="underline underline-offset-2">{{ $crumb->name }}</a>@endif
            @endforeach
            @if ($group->description)<span class="mt-1 block">{{ $group->description }}</span>@endif
            <span class="mt-1 block text-xs">{{ count($linkIds) }} link(s) across this group and subgroups</span>
        </x-slot:subtitle>
    </x-ui.page-header>

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <div class="inline-flex overflow-hidden rounded-lg border border-neutral-300 dark:border-neutral-700" role="group" aria-label="Analytics period">
            @foreach ([7, 30, 90] as $days)
                <a
                    href="{{ route($groupRoutePrefix.'groups.show', [$group->id, 'period' => $days]) }}"
                    @class([
                        'px-3.5 py-1.5 text-sm font-semibold transition-colors',
                        'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' => $period === $days,
                        'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white' => $period !== $days,
                    ])
                >{{ $days }}d</a>
            @endforeach
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat :value="number_format($totalClicks)" :label="'Clicks · last '.$period.' days'" />
        <x-ui.stat :value="number_format($uniqueVisitors)" label="Unique Visitors" />
        <x-ui.stat :value="number_format(count($linkIds))" label="Links (incl. subgroups)" />
        <x-ui.stat :value="number_format($childStats->count())" label="Subgroups" />
    </div>

    <x-ui.card title="Clicks over time" class="mb-6">
        @if ($totalClicks === 0)
            <p class="text-sm text-neutral-500">No clicks in the last {{ $period }} days yet.</p>
        @else
            <div class="relative h-56" role="img" aria-label="Daily clicks for the last {{ $period }} days">
                <canvas
                    data-chart="clicks"
                    data-chart-type="bar"
                    data-chart-labels='@json($clicksByDay->pluck("label")->values())'
                    data-chart-values='@json($clicksByDay->pluck("count")->values())'
                ></canvas>
            </div>
        @endif
    </x-ui.card>

    @if ($childStats->isNotEmpty())
        <x-ui.card title="Subgroups" class="mb-6">
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($childStats as $row)
                    <a href="{{ route($groupRoutePrefix.'groups.show', $row['group']->id) }}" class="flex items-center justify-between rounded-lg border border-neutral-200 px-3 py-2.5 hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-800">
                        <span class="flex items-center gap-2 truncate font-medium">
                            @if ($row['group']->color)<span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $row['group']->color }}"></span>@endif
                            <span class="truncate">{{ $row['group']->name }}</span>
                        </span>
                        <span class="shrink-0 text-xs text-neutral-500">{{ number_format($row['links']) }} links · {{ number_format($row['clicks']) }} clicks</span>
                    </a>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card title="Top Links in Group">
            @if ($topLinks->isEmpty())
                <p class="text-sm text-neutral-500">No links in this group yet — add some below.</p>
            @else
                <div class="flex flex-col gap-2">
                    @foreach ($topLinks as $link)
                        <a href="{{ route($groupRoutePrefix.'links.show', $link->id) }}" class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm hover:bg-neutral-50 dark:hover:bg-neutral-800">
                            <span class="truncate font-mono">{{ $link->domain?->hostname }}/{{ $link->slug }}</span>
                            <span class="shrink-0 text-xs text-neutral-500">{{ number_format($link->click_count) }} clicks</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        <x-ui.card title="Top Countries">
            @if ($topCountries->isEmpty())
                <p class="text-sm text-neutral-500">No geo data recorded yet.</p>
            @else
                <div class="flex flex-col gap-3.5">
                    @foreach ($topCountries as $c)
                        @php($share = $totalClicks > 0 ? round($c->count / $totalClicks * 100, 1) : 0)
                        <x-ui.bar-row :label="$c->country_code ?? 'Unknown'" :value="$c->count.' · '.$share.'%'" :share="$share" />
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card title="Top Referrers" class="mb-6">
        @if ($topReferrers->isEmpty())
            <p class="text-sm text-neutral-500">No referrer data recorded yet.</p>
        @else
            <div class="flex flex-col gap-3.5">
                @foreach ($topReferrers as $ref)
                    @php($share = $totalClicks > 0 ? round($ref->count / $totalClicks * 100, 1) : 0)
                    <x-ui.bar-row :label="$ref->referrer" :value="$ref->count.' · '.$share.'%'" :share="$share" />
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="Links in this group" class="mb-6">
        <livewire:dashboard.group-link-picker :group-id="$group->id" :scope="$groupScope" :key="'picker-'.$group->id" />

        @if ($links->isEmpty())
            <p class="mt-4 text-sm text-neutral-500">No links directly in this group yet. Use the search above to add some.</p>
        @else
            <div class="mt-4 flex flex-col gap-2">
                @foreach ($links as $link)
                    <div class="flex items-center justify-between gap-2 rounded-lg border border-neutral-100 px-3 py-2 text-sm dark:border-neutral-800">
                        <a href="{{ route($groupRoutePrefix.'links.show', $link->id) }}" class="truncate font-mono hover:underline">{{ $link->domain?->hostname }}/{{ $link->slug }}</a>
                        <span class="flex shrink-0 items-center gap-3">
                            <span class="text-xs text-neutral-500">{{ number_format($link->click_count) }}</span>
                            <form method="POST" action="{{ route($groupRoutePrefix.'groups.links.remove', [$group->id, $link->id]) }}" onsubmit="return confirm('Remove this link from the group?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="cursor-pointer text-xs text-red-600 hover:underline dark:text-red-400">Remove</button>
                            </form>
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">{{ $links->links() }}</div>
        @endif
    </x-ui.card>