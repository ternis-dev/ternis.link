<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <x-ui.segmented :options="\App\Livewire\Dashboard\LinkAnalytics::PERIODS" :active="$period" action="setPeriod" suffix="d" label="Analytics period" />
        <x-ui.button href="{{ route('dashboard.links.export', $link->id) }}" size="sm">Export CSV</x-ui.button>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat :value="number_format($totalClicks)" :label="'Clicks · last '.$period.' days'" />
        <x-ui.stat :value="number_format($uniqueVisitors)" label="Unique Visitors" />
        <x-ui.stat :value="$averagePerDay" label="Avg. per day" />
        <x-ui.stat :value="$peakDay ? $peakDay['label'] : '—'" :label="'Peak day'.($peakDay ? ' ('.$peakDay['count'].')' : '')" />
    </div>

    <x-ui.card title="Clicks over time" class="mb-6">
        @if ($totalClicks === 0)
            <p class="text-sm text-neutral-500 dark:text-neutral-400">No clicks in the last {{ $period }} days yet. Share your link to see traffic here.</p>
        @else
            <div class="relative h-56" role="img" aria-label="Daily clicks for the last {{ $period }} days">
                <canvas
                    data-chart="clicks"
                    data-chart-labels='@json($clicksByDay->pluck("label")->values())'
                    data-chart-values='@json($clicksByDay->pluck("count")->values())'
                ></canvas>
            </div>
            <div class="mt-2 flex justify-between text-xs text-neutral-500 dark:text-neutral-500">
                <span>{{ $clicksByDay->first()['label'] }}</span>
                <span>{{ $clicksByDay->get((int) floor($clicksByDay->count() / 2))['label'] ?? '' }}</span>
                <span>{{ $clicksByDay->last()['label'] }}</span>
            </div>
        @endif
    </x-ui.card>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card title="Top Referrers">
            @if ($topReferrers->isEmpty())
                <p class="text-sm text-neutral-500 dark:text-neutral-400">No referrer data recorded yet.</p>
            @else
                <div class="flex flex-col gap-3.5">
                    @foreach ($topReferrers as $ref)
                        @php($share = $totalClicks > 0 ? round($ref->count / $totalClicks * 100, 1) : 0)
                        <x-ui.bar-row :label="$ref->referrer" :value="$ref->count.' · '.$share.'%'" :share="$share" />
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        <x-ui.card title="Top Countries">
            @if ($topCountries->isEmpty())
                <p class="text-sm text-neutral-500 dark:text-neutral-400">No geo data recorded yet.</p>
            @else
                <div class="flex flex-col gap-3.5">
                    @foreach ($topCountries as $c)
                        @php($share = $totalClicks > 0 ? round($c->count / $totalClicks * 100, 1) : 0)
                        <x-ui.bar-row :label="$c->country_code" :value="$c->count.' · '.$share.'%'" :share="$share" />
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card title="Browsers" class="mb-6">
        @if ($topBrowsers->isEmpty())
            <p class="text-sm text-neutral-500 dark:text-neutral-400">No browser data recorded yet.</p>
        @else
            <div class="flex flex-col items-center gap-6 sm:flex-row">
                <div class="relative h-48 w-full max-w-55 sm:w-1/2" role="img" aria-label="Clicks by browser">
                    <canvas
                        id="chart-browsers"
                        data-chart="browsers"
                        data-chart-labels='@json($topBrowsers->pluck("browser")->values())'
                        data-chart-values='@json($topBrowsers->pluck("count")->values())'
                    ></canvas>
                </div>
                <ul class="w-full flex-1 space-y-3" data-chart-legend="chart-browsers">
                    @foreach ($topBrowsers as $i => $b)
                        @php($share = $totalClicks > 0 ? round($b['count'] / $totalClicks * 100, 1) : 0)
                        <li class="flex items-center gap-2.5 text-sm">
                            <span data-swatch="{{ $i }}" class="h-3 w-3 shrink-0 rounded-sm bg-neutral-300 dark:bg-neutral-700" aria-hidden="true"></span>
                            <span class="min-w-0 flex-1 truncate">{{ $b['browser'] }}</span>
                            <span class="text-xs whitespace-nowrap text-neutral-500 dark:text-neutral-400">{{ $b['count'] }} · {{ $share }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="Recent Clicks">
        @if ($recentClicks->isEmpty())
            <p class="text-sm text-neutral-500 dark:text-neutral-400">No clicks recorded yet.</p>
        @else
            <x-ui.table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Referrer</th>
                        <th>User Agent</th>
                        <th>IP Hash</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentClicks as $click)
                        <tr>
                            <td class="text-xs whitespace-nowrap text-neutral-500">
                                {{ $click->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="max-w-[250px] truncate">
                                {{ $click->referrer ?? 'Direct / None' }}
                            </td>
                            <td class="max-w-[250px] truncate text-xs text-neutral-500">
                                {{ $click->user_agent ?? 'Unknown' }}
                            </td>
                            <td>
                                <code>{{ substr($click->ip_hash, 0, 10) }}…</code>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @endif
    </x-ui.card>
</div>
