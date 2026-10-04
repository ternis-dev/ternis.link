@props(['timeline' => [], 'title' => 'Daily Timeline', 'color' => 'amber'])

@php
$values = array_values($timeline);
$max = max(1, ...$values);
$total = array_sum($values);
$count = count($timeline);
$barColor = match ($color) {
    'emerald' => 'bg-emerald-500 hover:bg-emerald-600',
    'blue' => 'bg-blue-500 hover:bg-blue-600',
    'red' => 'bg-red-500 hover:bg-red-600',
    'purple' => 'bg-purple-500 hover:bg-purple-600',
    default => 'bg-amber-500 hover:bg-amber-600',
};
@endphp

<div class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="text-sm font-semibold text-neutral-900 dark:text-white">{{ $title }}</h3>
            <p class="text-xs text-neutral-500">Daily frequency across the period</p>
        </div>
        <div class="flex items-center gap-3 text-xs">
            <span class="font-medium text-neutral-600 dark:text-neutral-300">Total in period: <strong class="text-neutral-900 dark:text-white font-bold">{{ number_format($total) }}</strong></span>
            <span class="text-neutral-400 dark:text-neutral-600">&bull;</span>
            <span class="font-medium text-neutral-600 dark:text-neutral-300">Peak day: <strong class="text-neutral-900 dark:text-white font-bold">{{ number_format($max) }}</strong></span>
        </div>
    </div>

    @if ($total === 0)
        <div class="flex h-36 items-center justify-center rounded-xl bg-neutral-50 text-xs text-neutral-400 dark:bg-neutral-950/40">
            No events recorded in this time range.
        </div>
    @else
        <div class="relative pt-6">
            {{-- Horizontal guide lines --}}
            <div class="pointer-events-none absolute inset-x-0 top-0 flex flex-col justify-between h-40 opacity-15">
                <div class="border-b border-dashed border-neutral-500 w-full"></div>
                <div class="border-b border-dashed border-neutral-500 w-full"></div>
                <div class="border-b border-neutral-500 w-full"></div>
            </div>

            {{-- Bars Container --}}
            <div class="relative flex h-40 items-end gap-1 sm:gap-1.5 overflow-x-auto pb-1">
                @foreach ($timeline as $date => $val)
                    @php
                    $pct = max(4, round(($val / $max) * 100));
                    $humanDate = \Carbon\Carbon::parse($date)->format('M d');
                    @endphp
                    <div class="group relative flex-1 flex flex-col items-center justify-end h-full min-w-[8px]">
                        {{-- Tooltip --}}
                        <div class="pointer-events-none absolute -top-9 z-30 hidden rounded-md bg-neutral-900 px-2 py-1 text-[10px] font-semibold text-white shadow-lg group-hover:block whitespace-nowrap dark:bg-white dark:text-neutral-900">
                            {{ $humanDate }}: {{ number_format($val) }}
                        </div>
                        {{-- Bar --}}
                        <div
                            style="height: {{ $pct }}%;"
                            class="w-full rounded-t-sm transition-all duration-300 {{ $barColor }} cursor-pointer"
                        ></div>
                    </div>
                @endforeach
            </div>

            {{-- Timeline Dates row (Start, Middle, Today) --}}
            @if ($count > 1)
                @php
                $dates = array_keys($timeline);
                $firstDate = \Carbon\Carbon::parse($dates[0])->format('M d');
                $lastDate = \Carbon\Carbon::parse(end($dates))->format('M d');
                @endphp
                <div class="mt-2 flex items-center justify-between text-[11px] font-mono text-neutral-400">
                    <span>{{ $firstDate }}</span>
                    <span>Daily distribution</span>
                    <span>{{ $lastDate }}</span>
                </div>
            @endif
        </div>
    @endif
</div>
