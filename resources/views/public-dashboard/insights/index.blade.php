<x-layouts.public-dashboard title="Insights — {{ \App\Support\PublicDashboard::label() }}">
    <x-pd.head
        title="Traffic & Insights"
        subtitle="Detailed behavioral metrics, diurnal heatmaps, and geographic distribution across your public links."
    >
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-1.5 rounded-xl border border-slate-200/90 bg-white/80 p-1 shadow-xs dark:border-slate-800 dark:bg-slate-900/80 backdrop-blur-xs">
                @foreach ([30, 90, 180, 365] as $option)
                    <a
                        href="{{ \App\Support\PublicDashboard::route('insights', ['days' => $option]) }}"
                        @class([
                            'rounded-lg px-3 py-1.5 text-xs font-bold transition-all',
                            'bg-emerald-600 text-white shadow-xs' => $days === $option,
                            'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' => $days !== $option,
                        ])
                    >{{ $option === 365 ? '1 year' : $option.' days' }}</a>
                @endforeach
            </div>
        </x-slot:actions>
    </x-pd.head>

    <div class="mb-8 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <x-pd.stat :value="number_format($totalClicks)" label="Total clicks" accent="true" />
        <x-pd.stat :value="number_format(array_sum(array_map('array_sum', $heatmap)))" label="Clicks in window" />
        <x-pd.stat :value="number_format($bestTimes[0]['count'] ?? 0)" label="Busiest hour" />
        <x-pd.stat :value="number_format($expiryCalendar->sum(fn ($day) => $day['links']->count()))" label="Expiring soon" />
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <x-pd.card title="Best time to post" subtitle="Clicks per weekday and hour — darker emerald cells represent higher click velocity." class="xl:col-span-2">
            @php
                $maxCell = max(1, max(array_map(fn (array $row) => max($row), $heatmap)));
                $weekdayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            @endphp
            <div class="overflow-x-auto pt-2">
                <div class="min-w-[560px]">
                    <div class="mb-1.5 grid grid-cols-[44px_repeat(24,minmax(0,1fr))] gap-1 text-center font-mono text-[10px] font-bold text-slate-400 dark:text-slate-500">
                        <span></span>
                        @for ($hour = 0; $hour < 24; $hour++)
                            <span>{{ $hour % 3 === 0 ? str_pad((string) $hour, 2, '0', STR_PAD_LEFT) : '' }}</span>
                        @endfor
                    </div>
                    @foreach ($heatmap as $weekday => $hours)
                        <div class="grid grid-cols-[44px_repeat(24,minmax(0,1fr))] gap-1 py-0.5">
                            <span class="flex items-center font-mono text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ $weekdayLabels[$weekday] }}</span>
                            @foreach ($hours as $hour => $count)
                                @php($intensity = $count === 0 ? 0 : max(14, (int) round($count / $maxCell * 100)))
                                <span
                                    class="aspect-square rounded-md transition-transform hover:scale-110 {{ $intensity === 0 ? 'bg-slate-100 dark:bg-slate-800/50' : '' }}"
                                    @if ($intensity > 0) style="background-color: rgba(16, 185, 129, {{ $intensity / 100 }});" @endif
                                    title="{{ $weekdayLabels[$weekday] }} {{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}:00 — {{ number_format($count) }} clicks"
                                ></span>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </x-pd.card>

        <x-pd.card title="Busiest slots" subtitle="Peak traffic hours during the selected window.">
            @if ($bestTimes === [])
                <p class="text-xs text-slate-500 dark:text-slate-400">No clicks recorded in this window yet.</p>
            @else
                <ul class="space-y-2.5 pt-1">
                    @foreach ($bestTimes as $slot)
                        <li class="flex items-center justify-between gap-3 rounded-xl bg-slate-50/80 p-3 ring-1 ring-slate-200/60 dark:bg-slate-900/60 dark:ring-slate-800/60">
                            <span class="font-semibold text-slate-800 dark:text-slate-200 text-sm">{{ $slot['label'] }}</span>
                            <span class="font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($slot['count']) }} clicks</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-pd.card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-pd.card title="Expiring soon" subtitle="Upcoming link expirations over the next 60 days.">
            @if ($expiryCalendar->isEmpty())
                <p class="text-xs text-slate-500 dark:text-slate-400">No links expire in the next 60 days.</p>
            @else
                <ul class="space-y-3 pt-1">
                    @foreach ($expiryCalendar as $day)
                        <li>
                            <p class="mb-1.5 text-[11px] font-bold tracking-wider text-slate-400 uppercase">{{ $day['label'] }}</p>
                            <ul class="space-y-1.5">
                                @foreach ($day['links'] as $link)
                                    <li class="flex items-center justify-between gap-3 rounded-xl bg-amber-50/70 p-3 text-sm ring-1 ring-amber-200/60 dark:bg-amber-950/20 dark:ring-amber-900/40">
                                        <a href="{{ \App\Support\PublicDashboard::route('links.edit', $link->id) }}" class="truncate font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                                            {{ ($link->domain?->hostname ?? 'href.nz').'/'.$link->slug }}
                                        </a>
                                        <span class="shrink-0 text-xs font-semibold text-amber-700 dark:text-amber-400">{{ $link->expires_at->diffForHumans() }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-pd.card>

        <div class="space-y-6">
            <x-pd.card title="Top referrers" subtitle="Source origins driving traffic to your short links.">
                @if ($topReferrers === [])
                    <p class="text-xs text-slate-500 dark:text-slate-400">No referrer data available in this window.</p>
                @else
                    <ul class="space-y-3 pt-1">
                        @foreach ($topReferrers as $row)
                            <li>
                                <div class="mb-1.5 flex items-baseline justify-between gap-3 text-xs">
                                    <span class="truncate font-medium text-slate-700 dark:text-slate-200">{{ $row['referrer'] }}</span>
                                    <span class="shrink-0 font-mono text-[11px] text-slate-500 dark:text-slate-400"><strong class="text-slate-900 dark:text-white">{{ number_format($row['count']) }}</strong> ({{ $row['share'] }}%)</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800" role="progressbar" aria-valuenow="{{ (int) $row['share'] }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500" style="width: {{ $row['share'] }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-pd.card>

            <x-pd.card title="Top countries" subtitle="Geographic distribution of your audience.">
                @if ($topCountries === [])
                    <p class="text-xs text-slate-500 dark:text-slate-400">No geo-ip telemetry recorded in this window yet.</p>
                @else
                    <ul class="flex flex-wrap gap-2 pt-1">
                        @foreach ($topCountries as $row)
                            <li><x-pd.chip>{{ $row['country'] }} · {{ number_format($row['count']) }}</x-pd.chip></li>
                        @endforeach
                    </ul>
                @endif
            </x-pd.card>
        </div>
    </div>
</x-layouts.public-dashboard>
