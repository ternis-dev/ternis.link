<div>
    <div class="mb-4 flex gap-2">
        @foreach ([7, 30, 90] as $days)
            <x-ui.button wire:click="setPeriod({{ $days }})" variant="{{ $period === $days ? 'primary' : 'ghost' }}">{{ $days }}d</x-ui.button>
        @endforeach
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.stat label="Views" :value="number_format($views)" />
        <x-ui.stat label="Taps" :value="number_format($taps)" />
        <x-ui.stat label="CTR" :value="$ctr === null ? '—' : $ctr . '%'" />
    </div>

    <x-ui.card title="Taps per button" class="mt-6">
        @forelse ($byButton as $row)
            <div class="flex items-center justify-between gap-3 border-b border-neutral-100 py-2 text-sm last:border-0 dark:border-neutral-800">
                <span class="truncate">{{ $row['label'] }}</span>
                <span class="shrink-0 font-mono">{{ number_format($row['taps']) }} · {{ $row['share'] }}%</span>
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
</div>
