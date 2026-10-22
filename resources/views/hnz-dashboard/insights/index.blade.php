<x-layouts.hnz-dashboard title="Insights — {{ \App\Support\PublicDashboard::label() }}">
    <x-hnz.head
        eyebrow="Insights"
        title="When and where your links get clicked"
        subtitle="A 7×24 map of the last {{ $days }} days, the links that are about to expire, and the traffic that comes your way."
    >
        <x-slot:actions>
            @foreach ([30, 90, 180, 365] as $option)
                <x-hnz.button
                    href="{{ \App\Support\PublicDashboard::route('insights', ['days' => $option]) }}"
                    variant="{{ $days === $option ? 'primary' : 'secondary' }}"
                    size="sm"
                >{{ $option === 365 ? '1 year' : $option.' days' }}</x-hnz.button>
            @endforeach
        </x-slot:actions>
    </x-hnz.head>

    <div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <x-hnz.stat :value="number_format($totalClicks)" label="Total clicks" accent="true" />
        <x-hnz.stat :value="number_format(array_sum(array_map('array_sum', $heatmap)))" label="Clicks in window" />
        <x-hnz.stat :value="number_format($bestTimes[0]['count'] ?? 0)" label="Busiest hour" hint="{{ $bestTimes[0]['label'] ?? '—' }}" />
        <x-hnz.stat :value="number_format($expiryCalendar->sum(fn ($day) => $day['links']->count()))" label="Expiring soon" />
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <x-hnz.card title="Best time to post" subtitle="Clicks per weekday and hour — the darker the cell, the busier it is." class="xl:col-span-2">
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
                                    @if ($intensity > 0) style="background-color: rgba(124, 58, 237, {{ $intensity / 100 }});" @endif
                                    title="{{ $weekdayLabels[$weekday] }} {{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}:00 — {{ number_format($count) }} clicks"
                                ></span>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </x-hnz.card>

        <x-hnz.card title="Busiest slots" subtitle="Your five strongest weekday/hour combinations.">
            @if ($bestTimes === [])
                <x-hnz.empty>No clicks in this window yet.</x-hnz.empty>
            @else
                <ul class="space-y-2.5">
                    @foreach ($bestTimes as $slot)
                        <li class="flex items-center justify-between gap-3 rounded-xl bg-neutral-50 px-3.5 py-2.5 dark:bg-white/[0.03]">
                            <span class="font-display text-sm font-bold text-neutral-800 dark:text-neutral-100">{{ $slot['label'] }}</span>
                            <span class="text-xs font-semibold text-neutral-500 dark:text-neutral-400">{{ number_format($slot['count']) }} clicks</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-hnz.card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-hnz.card title="Expiring soon" subtitle="The next 60 days — extend or deactivate these before they go.">
            @if ($expiryCalendar->isEmpty())
                <x-hnz.empty>Nothing expires in the next 60 days.</x-hnz.empty>
            @else
                <ul class="space-y-3">
                    @foreach ($expiryCalendar as $day)
                        <li>
                            <p class="mb-1.5 text-xs font-bold tracking-wide text-neutral-400 uppercase">{{ $day['label'] }}</p>
                            <ul class="space-y-1.5">
                                @foreach ($day['links'] as $link)
                                    <li class="flex items-center justify-between gap-3 rounded-xl bg-amber-50 px-3.5 py-2 text-sm dark:bg-amber-400/10">
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
        </x-hnz.card>

        <div class="space-y-6">
            <x-hnz.card title="Top referrers">
                @if ($topReferrers === [])
                    <x-hnz.empty>No referrer data in this window yet.</x-hnz.empty>
                @else
                    <ul class="space-y-2.5">
                        @foreach ($topReferrers as $row)
                            <li>
                                <div class="mb-1 flex items-baseline justify-between gap-3 text-sm">
                                    <span class="truncate text-neutral-700 dark:text-neutral-200">{{ $row['referrer'] }}</span>
                                    <span class="shrink-0 text-xs font-semibold text-neutral-500 dark:text-neutral-400">{{ number_format($row['count']) }} · {{ $row['share'] }}%</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-neutral-100 dark:bg-white/10" role="progressbar" aria-valuenow="{{ (int) $row['share'] }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="h-full rounded-full bg-gradient-to-r from-violet-600 to-blue-500" style="width: {{ $row['share'] }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-hnz.card>

            <x-hnz.card title="Top countries">
                @if ($topCountries === [])
                    <x-hnz.empty>No geo data in this window yet.</x-hnz.empty>
                @else
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($topCountries as $row)
                            <li class="hnz-chip inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold">
                                {{ $row['country'] }}
                                <span class="opacity-70">{{ number_format($row['count']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-hnz.card>
        </div>
    </div>
</x-layouts.hnz-dashboard>
