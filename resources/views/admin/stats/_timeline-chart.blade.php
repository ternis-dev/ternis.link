@props(['timeline' => [], 'title' => 'Daily Timeline', 'color' => 'amber', 'unit' => 'event'])

@php
$values = array_values($timeline);
$dates = array_keys($timeline);
$max = max(1, ...$values);
$total = array_sum($values);
$count = count($timeline);
$avg = $count > 0 ? round($total / $count, 1) : 0;

$barColor = match ($color) {
    'emerald' => 'bg-emerald-500 hover:bg-emerald-600',
    'blue' => 'bg-blue-500 hover:bg-blue-600',
    'red' => 'bg-red-500 hover:bg-red-600',
    'purple' => 'bg-purple-500 hover:bg-purple-600',
    default => 'bg-amber-500 hover:bg-amber-600',
};

$strokeHex = match ($color) {
    'emerald' => '#10b981',
    'blue' => '#3b82f6',
    'red' => '#ef4444',
    'purple' => '#8b5cf6',
    default => '#f59e0b',
};

$gradientId = 'grad-' . $color . '-' . md5($title . $count);

// SVG coordinate calculations for line graph
$labels = array_map(fn ($d) => \Carbon\Carbon::parse($d)->format('M d'), $dates);
$points = [];
$width = 960;
$height = 140;
$paddingY = 16;
$usableHeight = $height - ($paddingY * 2);

foreach ($values as $i => $val) {
    $x = $count > 1 ? round(($i / ($count - 1)) * $width, 1) : round($width / 2, 1);
    $y = round($height - $paddingY - (($val / $max) * $usableHeight), 1);
    $points[] = ['x' => $x, 'y' => $y, 'val' => $val, 'date' => $labels[$i]];
}

$pathD = '';
foreach ($points as $idx => $pt) {
    if ($idx === 0) {
        $pathD .= "M {$pt['x']},{$pt['y']}";
    } else {
        $prev = $points[$idx - 1];
        $cpX1 = round($prev['x'] + ($pt['x'] - $prev['x']) / 3, 1);
        $cpX2 = round($prev['x'] + 2 * ($pt['x'] - $prev['x']) / 3, 1);
        $pathD .= " C {$cpX1},{$prev['y']} {$cpX2},{$pt['y']} {$pt['x']},{$pt['y']}";
    }
}
$lastPt = !empty($points) ? end($points) : ['x' => $width, 'y' => $height];
$firstPt = !empty($points) ? $points[0] : ['x' => 0, 'y' => $height];
$areaD = $pathD . " L {$lastPt['x']}," . ($height) . " L {$firstPt['x']}," . ($height) . " Z";
@endphp

<div x-data="{ mode: 'graph' }" class="rounded-2xl border border-neutral-200/90 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-sm font-semibold text-neutral-900 dark:text-white">{{ $title }}</h3>
            <p class="text-xs text-neutral-500">Telemetry trend graph across the period</p>
        </div>
        <div class="flex flex-wrap items-center gap-4 text-xs">
            <div class="flex items-center gap-3">
                <span class="font-medium text-neutral-600 dark:text-neutral-300">Total: <strong class="text-neutral-900 dark:text-white font-bold">{{ number_format($total) }}</strong></span>
                <span class="text-neutral-400 dark:text-neutral-600">&bull;</span>
                <span class="font-medium text-neutral-600 dark:text-neutral-300">Peak: <strong class="text-neutral-900 dark:text-white font-bold">{{ number_format($max) }}</strong></span>
                <span class="text-neutral-400 dark:text-neutral-600">&bull;</span>
                <span class="font-medium text-neutral-600 dark:text-neutral-300">Avg/day: <strong class="text-neutral-900 dark:text-white font-bold">{{ $avg }}</strong></span>
            </div>

            {{-- Graph vs Bars View Toggle (Default: Graph) --}}
            <div class="inline-flex items-center rounded-lg border border-neutral-200 bg-neutral-50 p-0.5 text-[11px] dark:border-neutral-800 dark:bg-neutral-950">
                <button
                    type="button"
                    @click="mode = 'graph'"
                    :class="mode === 'graph' ? 'bg-white text-neutral-900 shadow-xs font-bold dark:bg-neutral-800 dark:text-white' : 'text-neutral-500 hover:text-neutral-900 dark:hover:text-white'"
                    class="rounded-md px-2.5 py-1 transition-all cursor-pointer"
                >
                    Graph
                </button>
                <button
                    type="button"
                    @click="mode = 'bars'"
                    :class="mode === 'bars' ? 'bg-white text-neutral-900 shadow-xs font-bold dark:bg-neutral-800 dark:text-white' : 'text-neutral-500 hover:text-neutral-900 dark:hover:text-white'"
                    class="rounded-md px-2.5 py-1 transition-all cursor-pointer"
                >
                    Bars
                </button>
            </div>
        </div>
    </div>

    @if ($total === 0)
        <div class="flex h-40 items-center justify-center rounded-xl bg-neutral-50 text-xs text-neutral-400 dark:bg-neutral-950/40">
            No events recorded in this time range.
        </div>
    @else
        {{-- Mode: Line Graph View (Default) --}}
        <div x-show="mode === 'graph'" class="relative pt-4">
            <div class="relative h-44 w-full">
                <canvas
                    data-chart="clicks"
                    data-chart-type="line"
                    data-chart-color="{{ $color }}"
                    data-chart-unit="{{ $unit }}"
                    data-chart-labels='@json($labels)'
                    data-chart-values='@json($values)'
                    class="relative z-10 h-full w-full"
                ></canvas>

                {{-- Immediate SVG Area Fallback / Backdrop Graph --}}
                <div class="pointer-events-none absolute inset-0 z-0">
                    <svg viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" class="h-full w-full opacity-60 dark:opacity-40">
                        <defs>
                            <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="{{ $strokeHex }}" stop-opacity="0.35" />
                                <stop offset="100%" stop-color="{{ $strokeHex }}" stop-opacity="0.0" />
                            </linearGradient>
                        </defs>
                        @if ($pathD !== '')
                            <path d="{{ $areaD }}" fill="url(#{{ $gradientId }})" />
                            <path d="{{ $pathD }}" fill="none" stroke="{{ $strokeHex }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                        @endif
                    </svg>
                </div>
            </div>

            @if ($count > 1)
                @php
                $firstDate = \Carbon\Carbon::parse($dates[0])->format('M d');
                $midDate = \Carbon\Carbon::parse($dates[(int) floor($count / 2)])->format('M d');
                $lastDate = \Carbon\Carbon::parse(end($dates))->format('M d');
                @endphp
                <div class="mt-2 flex items-center justify-between text-[11px] font-mono text-neutral-400">
                    <span>{{ $firstDate }}</span>
                    <span>{{ $midDate }}</span>
                    <span>{{ $lastDate }}</span>
                </div>
            @endif
        </div>

        {{-- Mode: Bar Chart View (Secondary Option) --}}
        <div x-show="mode === 'bars'" x-cloak class="relative pt-6">
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
                    <div class="group relative flex-1 flex flex-col items-center justify-end h-full min-w-[8px]" @if ($val > 0) tabindex="0" aria-label="{{ $humanDate }}: {{ number_format($val) }} {{ $unit }}s" @endif>
                        {{-- Tooltip --}}
                        <div class="pointer-events-none absolute -top-9 z-30 hidden rounded-md bg-neutral-900 px-2 py-1 text-[10px] font-semibold text-white shadow-lg group-hover:block group-focus-within:block whitespace-nowrap dark:bg-white dark:text-neutral-900">
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

            @if ($count > 1)
                @php
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
