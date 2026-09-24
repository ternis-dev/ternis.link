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
            <div class="ui-chart-bars" role="img" aria-label="Daily clicks for the last {{ $period }} days">
                @foreach ($clicksByDay as $day)
                    <div
                        class="ui-chart-bar"
                        style="height: {{ max(3, round($day['count'] / $maxDailyClicks * 100)) }}%;"
                        title="{{ $day['label'] }}: {{ $day['count'] }} clicks"
                    ></div>
                @endforeach
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
            <div class="flex flex-col gap-3.5">
                @foreach ($topBrowsers as $b)
                    @php($share = $totalClicks > 0 ? round($b['count'] / $totalClicks * 100, 1) : 0)
                    <x-ui.bar-row :label="$b['browser']" :value="$b['count'].' · '.$share.'%'" :share="$share" />
                @endforeach
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
