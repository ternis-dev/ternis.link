<div>
    <div class="mb-4 flex gap-2">
        @foreach ([7, 30, 90] as $days)
            <x-ui.button wire:click="setPeriod({{ $days }})" variant="{{ $period === $days ? 'primary' : 'ghost' }}">{{ $days }}d</x-ui.button>
        @endforeach
    </div>

    <div class="grid gap-4 sm:grid-cols-4">
        <x-ui.stat label="Views" :value="number_format($views)" />
        <x-ui.stat label="Taps" :value="number_format($taps)" />
        <x-ui.stat label="Visitors" :value="number_format($uniqueVisitors)" />
        <x-ui.stat label="CTR" :value="$ctr === null ? '—' : $ctr . '%'" />
    </div>

    <x-ui.card title="Taps per button" class="mt-6">
        @forelse ($byButton as $row)
            <div class="flex items-center justify-between gap-3 border-b border-neutral-100 py-2 text-sm last:border-0 dark:border-neutral-800">
                <span class="truncate">{{ $row['label'] }}</span>
                <span class="shrink-0 font-mono">{{ number_format($row['taps']) }} · {{ $row['share'] }}% · {{ $row['ctr'] === null ? '—' : $row['ctr'] . '%' }} CTR</span>
            </div>
        @empty
            <p class="text-sm text-neutral-500">No buttons yet.</p>
        @endforelse
    </x-ui.card>

    <x-ui.card title="Views & taps per day" class="mt-6">
        <div class="flex h-32 items-end gap-1" aria-hidden="true">
            @foreach ($byDay as $day)
                <div class="flex flex-1 flex-col justify-end gap-0.5" title="{{ $day['label'] }}: {{ $day['views'] }} views, {{ $day['taps'] }} taps">
                    <div class="rounded-sm bg-neutral-900 dark:bg-white" style="height: {{ max(2, $day['views'] / $maxDaily * 100) }}%"></div>
                    <div class="rounded-sm bg-neutral-300 dark:bg-neutral-600" style="height: {{ max(2, $day['taps'] / $maxDaily * 100) }}%"></div>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    @if ($bySubpage->isNotEmpty())
        <x-ui.card title="Sub-pages" class="mt-6">
            @foreach ($bySubpage as $sub)
                <div class="flex items-center justify-between gap-3 border-b border-neutral-100 py-2 text-sm last:border-0 dark:border-neutral-800">
                    <span class="truncate font-mono">/{{ $sub['slug'] }} <span class="font-sans text-neutral-500">{{ $sub['title'] }}</span></span>
                    <span class="shrink-0 font-mono">{{ number_format($sub['views']) }} views · {{ number_format($sub['taps']) }} taps</span>
                </div>
            @endforeach
        </x-ui.card>
    @endif

    <div class="mt-6 grid gap-6 md:grid-cols-2">
        <x-ui.card title="Top referrers">
            @forelse ($topReferrers as $row)
                <div class="flex items-center justify-between gap-3 border-b border-neutral-100 py-2 text-sm last:border-0 dark:border-neutral-800">
                    <span class="truncate font-mono">{{ $row->referrer }}</span>
                    <span class="shrink-0 font-mono">{{ number_format($row->count) }}</span>
                </div>
            @empty
                <p class="text-sm text-neutral-500">No referrer data yet.</p>
            @endforelse
        </x-ui.card>
        <x-ui.card title="Top countries">
            @forelse ($topCountries as $row)
                <div class="flex items-center justify-between gap-3 border-b border-neutral-100 py-2 text-sm last:border-0 dark:border-neutral-800">
                    <span class="truncate font-mono">{{ $row->country_code }}</span>
                    <span class="shrink-0 font-mono">{{ number_format($row->count) }}</span>
                </div>
            @empty
                <p class="text-sm text-neutral-500">No country data yet.</p>
            @endforelse
        </x-ui.card>
    </div>

    <div class="mt-6 grid gap-6 md:grid-cols-2">
        <x-ui.card title="Browsers">
            @forelse ($topBrowsers as $row)
                <div class="flex items-center justify-between gap-3 border-b border-neutral-100 py-2 text-sm last:border-0 dark:border-neutral-800">
                    <span class="truncate">{{ $row['browser'] }}</span>
                    <span class="shrink-0 font-mono">{{ number_format($row['count']) }}</span>
                </div>
            @empty
                <p class="text-sm text-neutral-500">No browser data yet.</p>
            @endforelse
        </x-ui.card>
        <x-ui.card title="Recent activity">
            @forelse ($recentEvents as $event)
                <div class="flex items-center justify-between gap-3 border-b border-neutral-100 py-2 text-sm last:border-0 dark:border-neutral-800">
                    <span class="truncate">{{ $event->kind === 'tap' ? '⤷' : '👁' }} {{ $event->button?->label ?? 'page view' }}</span>
                    <span class="shrink-0 font-mono text-xs text-neutral-500">{{ $event->created_at?->diffForHumans() }}</span>
                </div>
            @empty
                <p class="text-sm text-neutral-500">No activity yet.</p>
            @endforelse
        </x-ui.card>
    </div>
</div>
