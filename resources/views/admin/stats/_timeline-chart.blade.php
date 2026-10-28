@props([
    'timeline' => [],
    'datasets' => null,
    'labels' => null,
    'title' => 'Daily Timeline',
    'subtitle' => null,
    'color' => 'amber',
    'unit' => 'event',
    'defaultType' => 'line',
    'stacked' => false,
    'heightClass' => 'h-56',
])

@php
$formatKey = static function (string $key): string {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $key) === 1) {
        return \Carbon\Carbon::parse($key)->format('M j');
    }

    return $key;
};

$isMulti = is_array($datasets) && count($datasets) > 0;

if ($isMulti) {
    $rawKeys = is_array($labels) ? $labels : array_keys($timeline);
    $chartLabels = array_map(fn ($k) => $formatKey((string) $k), $rawKeys);
    $primaryValues = array_values($datasets[0]['values'] ?? []);
    $allSums = [];
    foreach ($datasets as $ds) {
        foreach (array_values($ds['values'] ?? []) as $idx => $v) {
            $allSums[$idx] = ($allSums[$idx] ?? 0) + (int) $v;
        }
    }
    $values = $allSums !== [] ? $allSums : [0];
    $total = array_sum($values);
    $count = count($chartLabels);
} else {
    $values = array_map('intval', array_values($timeline));
    $dates = array_keys($timeline);
    $chartLabels = array_map(fn ($d) => $formatKey((string) $d), $dates);
    $total = array_sum($values);
    $count = count($timeline);
}

$max = $values !== [] ? max($values) : 0;
$peakIdx = $max > 0 ? array_search($max, $values, true) : false;
$peakAt = ($peakIdx !== false && isset($chartLabels[$peakIdx])) ? $chartLabels[$peakIdx] : null;
$avg = $count > 0 ? round($total / $count, 1) : 0;
$isHourly = $count === 24 && isset($chartLabels[0]) && str_ends_with((string) $chartLabels[0], ':00');
$avgLabel = $isHourly ? 'Avg/hr' : 'Avg/day';
$resolvedSubtitle = $subtitle ?? ($isHourly ? '24-hour distribution curve (UTC)' : 'Telemetry trend across the selected window');
@endphp

<div data-chart-card class="rounded-2xl border border-neutral-200/90 bg-white p-5 sm:p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="font-display text-base font-semibold tracking-tight text-neutral-900 dark:text-white">{{ $title }}</h3>
            <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">{{ $resolvedSubtitle }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3 text-xs">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-lg bg-neutral-100 px-2.5 py-1 font-medium text-neutral-600 dark:bg-neutral-800/80 dark:text-neutral-300">
                    Total: <strong class="font-bold text-neutral-900 dark:text-white">{{ number_format($total) }}</strong>
                </span>
                <span class="rounded-lg bg-neutral-100 px-2.5 py-1 font-medium text-neutral-600 dark:bg-neutral-800/80 dark:text-neutral-300">
                    Peak: <strong class="font-bold text-neutral-900 dark:text-white">{{ number_format($max) }}</strong>@if ($peakAt)<span class="text-neutral-400 dark:text-neutral-500"> ({{ $peakAt }})</span>@endif
                </span>
                <span class="hidden sm:inline-flex rounded-lg bg-neutral-100 px-2.5 py-1 font-medium text-neutral-600 dark:bg-neutral-800/80 dark:text-neutral-300">
                    {{ $avgLabel }}: <strong class="font-bold text-neutral-900 dark:text-white">{{ $avg }}</strong>
                </span>
            </div>

            {{-- Graph (Line) vs Bars Switcher --}}
            <div class="inline-flex items-center rounded-lg border border-neutral-200 bg-neutral-50 p-0.5 text-[11px] dark:border-neutral-800 dark:bg-neutral-950" role="group" aria-label="Chart display mode">
                <button
                    type="button"
                    data-chart-switch="line"
                    data-active="{{ $defaultType === 'line' ? 'true' : 'false' }}"
                    aria-pressed="{{ $defaultType === 'line' ? 'true' : 'false' }}"
                    class="cursor-pointer rounded-md px-2.5 py-1 transition-all data-[active=true]:bg-white data-[active=true]:font-bold data-[active=true]:text-neutral-900 data-[active=true]:shadow-xs dark:data-[active=true]:bg-neutral-800 dark:data-[active=true]:text-white data-[active=false]:text-neutral-500 data-[active=false]:hover:text-neutral-900 dark:data-[active=false]:hover:text-white"
                >
                    Graph
                </button>
                <button
                    type="button"
                    data-chart-switch="bar"
                    data-active="{{ $defaultType === 'bar' ? 'true' : 'false' }}"
                    aria-pressed="{{ $defaultType === 'bar' ? 'true' : 'false' }}"
                    class="cursor-pointer rounded-md px-2.5 py-1 transition-all data-[active=true]:bg-white data-[active=true]:font-bold data-[active=true]:text-neutral-900 data-[active=true]:shadow-xs dark:data-[active=true]:bg-neutral-800 dark:data-[active=true]:text-white data-[active=false]:text-neutral-500 data-[active=false]:hover:text-neutral-900 dark:data-[active=false]:hover:text-white"
                >
                    Bars
                </button>
            </div>
        </div>
    </div>

    @if ($total === 0)
        <div class="flex h-44 items-center justify-center rounded-xl bg-neutral-50 text-xs text-neutral-400 dark:bg-neutral-950/40">
            No {{ $unit }}s recorded in this time range.
        </div>
    @else
        <div class="relative {{ $heightClass }} w-full" role="img" aria-label="{{ $title }}">
            @if ($isMulti)
                <canvas
                    data-chart="multi"
                    data-chart-type="{{ $defaultType }}"
                    data-chart-unit="{{ $unit }}"
                    @if ($stacked) data-chart-stacked="true" @endif
                    data-chart-labels='@json($chartLabels)'
                    data-chart-datasets='@json($datasets)'
                    class="h-full w-full"
                ></canvas>
            @else
                <canvas
                    data-chart="clicks"
                    data-chart-type="{{ $defaultType }}"
                    data-chart-color="{{ $color }}"
                    data-chart-unit="{{ $unit }}"
                    data-chart-labels='@json($chartLabels)'
                    data-chart-values='@json($values)'
                    class="h-full w-full"
                ></canvas>
            @endif
        </div>
    @endif
</div>
