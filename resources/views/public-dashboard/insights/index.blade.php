<x-layouts.public-dashboard title="Insights — {{ \App\Support\PublicDashboard::label() }}">
    <x-pd.head
        title="Insights"
        subtitle="When and where your links get clicked — the last {{ $days }} days."
    >
        <x-slot:actions>
            @foreach ([30, 90, 180, 365] as $option)
                <x-pd.button
                    href="{{ \App\Support\PublicDashboard::route('insights', ['days' => $option]) }}"
                    variant="{{ $days === $option ? 'primary' : 'secondary' }}"
                    size="sm"
                >{{ $option === 365 ? '1 year' : $option.' days' }}</x-pd.button>
            @endforeach
        </x-slot:actions>
    </x-pd.head>

    <div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <x-pd.stat :value="number_format($totalClicks)" label="Total clicks" accent="true" />
        <x-pd.stat :value="number_format(array_sum(array_map('array_sum', $heatmap)))" label="Clicks in window" />
        <x-pd.stat :value="number_format($bestTimes[0]['count'] ?? 0)" label="Busiest hour" />
        <x-pd.stat :value="number_format($expiryCalendar->sum(fn ($day) => $day['links']->count()))" label="Expiring soon" />
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <x-pd.card title="Best time to post" class="xl:col-span-2">
            <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">Clicks per weekday and hour — the darker the cell, the busier it is.</p>
            @php
                $maxCell = max(1, max(array_map(fn (array $row) => max($row), $heatmap)));
                $weekdayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            @endphp
            <div class="overflow-x-auto">
                <div class="min-w-[560px]">
                    <div class="mb-1 grid grid-cols-[44px_repeat(24,minmax(0,1fr))] gap-0.5 text-center text-[10px] font-bold text-neutral-400">
                        <span></span>
                        @for ($hour = 0; $hour < 24; $hour++)
                            <span>{{ $hour % 3 === 0 ? str_pad((string) $hour, 2, '0', STR_PAD_LEFT) : '' }}</span>
                        @endfor
                    </div>
                    @foreach ($heatmap as $weekday => $hours)
                        <div class="grid grid-cols-[44px_repeat(24,minmax(0,1fr))] gap-0.5">
                            <span class="flex items-center text-[11px] font-bold text-neutral-500 dark:text-neutral-400">{{ $weekdayLabels[$weekday] }}</span>
                            @foreach ($hours as $hour => $count)
                                @php($intensity = $count === 0 ? 0 : max(12, (int) round($count / $maxCell * 100)))
                                <span
                                    class="aspect-square rounded-[3px] {{ $intensity === 0 ? 'bg-neutral-100 dark:bg-white/5' : '' }}"
                                    @if ($intensity > 0) style="background-color: rgba(5, 150, 105, {{ $intensity / 100 }});" @endif
                                    title="{{ $weekdayLabels[$weekday] }} {{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}:00 — {{ number_format($count) }} clicks"
                                ></span>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </x-pd.card>

        <x-pd.card title="Busiest slots">
            @if ($bestTimes === [])
                <p class="text-sm text-neutral-500 dark:text-neutral-400">No clicks in this window yet.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($bestTimes as $slot)
                        <li class="flex items-center justify-between gap-3 rounded-lg bg-neutral-50 px-3.5 py-2.5 text-sm dark:bg-white/5">
                            <span class="font-semibold text-neutral-800 dark:text-neutral-100">{{ $slot['label'] }}</span>
                            <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ number_format($slot['count']) }} clicks</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-pd.card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-pd.card title="Expiring soon">
            <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">The next 60 days — extend or deactivate these before they go.</p>
            @if ($expiryCalendar->isEmpty())
                <p class="text-sm text-neutral-500 dark:text-neutral-400">Nothing expires in the next 60 days.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($expiryCalendar as $day)
                        <li>
                            <p class="mb-1.5 text-xs font-bold tracking-wide text-neutral-400 uppercase">{{ $day['label'] }}</p>
                            <ul class="space-y-1.5">
                                @foreach ($day['links'] as $link)
                                    <li class="flex items-center justify-between gap-3 rounded-lg bg-amber-50 px-3.5 py-2 text-sm dark:bg-amber-400/10">
                                        <a href="{{ \App\Support\PublicDashboard::route('links.edit', $link->id) }}" class="truncate font-semibold text-neutral-800 hover:underline dark:text-neutral-100">
                                            {{ ($link->domain?->hostname ?? 'href.nz').'/'.$link->slug }}
                                        </a>
                                        <span class="shrink-0 text-xs font-semibold text-amber-700 dark:text-amber-300">{{ $link->expires_at->diffForHumans() }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-pd.card>

        <div class="space-y-6">
            <x-pd.card title="Top referrers">
                @if ($topReferrers === [])
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">No referrer data in this window yet.</p>
                @else
                    <ul class="space-y-2.5">
                        @foreach ($topReferrers as $row)
                            <li>
                                <div class="mb-1 flex items-baseline justify-between gap-3 text-sm">
                                    <span class="truncate text-neutral-700 dark:text-neutral-200">{{ $row['referrer'] }}</span>
                                    <span class="shrink-0 text-xs text-neutral-500 dark:text-neutral-400">{{ number_format($row['count']) }} · {{ $row['share'] }}%</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-neutral-100 dark:bg-white/10" role="progressbar" aria-valuenow="{{ (int) $row['share'] }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ $row['share'] }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-pd.card>

            <x-pd.card title="Top countries">
                @if ($topCountries === [])
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">No geo data in this window yet.</p>
                @else
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($topCountries as $row)
                            <li><x-pd.chip>{{ $row['country'] }} · {{ number_format($row['count']) }}</x-pd.chip></li>
                        @endforeach
                    </ul>
                @endif
            </x-pd.card>
        </div>
    </div>
</x-layouts.public-dashboard>
