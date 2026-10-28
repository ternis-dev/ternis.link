@props([
    'heatmap' => [],
    'grid' => null,
    'title' => null,
    'subtitle' => null,
    'color' => 'neutral',
    'palette' => null,
    'unit' => 'click',
    'wrapped' => true,
    'showSummary' => true,
    'compact' => false,
])

@php
$sourceHeatmap = ! empty($heatmap) ? $heatmap : ($grid ?? []);
$resolvedColor = $palette ?? $color;
$weekdayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$normalized = [];
$total = 0;
$maxCell = 0;
$activeSlots = 0;
$peakLabel = '—';
$rowTotals = array_fill(0, 7, 0);

for ($w = 0; $w < 7; $w++) {
    $normalized[$w] = [];
    for ($h = 0; $h < 24; $h++) {
        $val = (int) ($sourceHeatmap[$w][$h] ?? 0);
        $normalized[$w][$h] = $val;
        $total += $val;
        $rowTotals[$w] += $val;
        if ($val > 0) {
            $activeSlots++;
        }
        if ($val > $maxCell) {
            $maxCell = $val;
            $peakLabel = $weekdayLabels[$w].' '.str_pad((string) $h, 2, '0', STR_PAD_LEFT).':00';
        }
    }
}

$safeMax = max(1, $maxCell);

$swatch = match ($resolvedColor) {
    'amber' => [
        0 => 'bg-neutral-100 dark:bg-neutral-800/70',
        1 => 'bg-amber-200/80 dark:bg-amber-950/90 ring-1 ring-inset ring-amber-400/20',
        2 => 'bg-amber-300 dark:bg-amber-800/80',
        3 => 'bg-amber-400 dark:bg-amber-600',
        4 => 'bg-amber-500 dark:bg-amber-400',
        'accent' => 'text-amber-600 dark:text-amber-400',
    ],
    'blue' => [
        0 => 'bg-neutral-100 dark:bg-neutral-800/70',
        1 => 'bg-blue-200/80 dark:bg-blue-950/90 ring-1 ring-inset ring-blue-400/20',
        2 => 'bg-blue-300 dark:bg-blue-800/80',
        3 => 'bg-blue-500 dark:bg-blue-600',
        4 => 'bg-blue-600 dark:bg-blue-400',
        'accent' => 'text-blue-600 dark:text-blue-400',
    ],
    'emerald' => [
        0 => 'bg-neutral-100 dark:bg-neutral-800/70',
        1 => 'bg-emerald-200/80 dark:bg-emerald-950/90 ring-1 ring-inset ring-emerald-400/20',
        2 => 'bg-emerald-300 dark:bg-emerald-800/80',
        3 => 'bg-emerald-500 dark:bg-emerald-600',
        4 => 'bg-emerald-600 dark:bg-emerald-400',
        'accent' => 'text-emerald-600 dark:text-emerald-400',
    ],
    'red' => [
        0 => 'bg-neutral-100 dark:bg-neutral-800/70',
        1 => 'bg-red-200/80 dark:bg-red-950/90 ring-1 ring-inset ring-red-400/20',
        2 => 'bg-red-300 dark:bg-red-800/80',
        3 => 'bg-red-500 dark:bg-red-600',
        4 => 'bg-red-600 dark:bg-red-400',
        'accent' => 'text-red-600 dark:text-red-400',
    ],
    'purple' => [
        0 => 'bg-neutral-100 dark:bg-neutral-800/70',
        1 => 'bg-purple-200/80 dark:bg-purple-950/90 ring-1 ring-inset ring-purple-400/20',
        2 => 'bg-purple-300 dark:bg-purple-800/80',
        3 => 'bg-purple-500 dark:bg-purple-600',
        4 => 'bg-purple-600 dark:bg-purple-400',
        'accent' => 'text-purple-600 dark:text-purple-400',
    ],
    default => [
        0 => 'bg-neutral-100 dark:bg-neutral-800/70',
        1 => 'bg-neutral-300 dark:bg-neutral-700',
        2 => 'bg-neutral-400 dark:bg-neutral-500',
        3 => 'bg-neutral-700 dark:bg-neutral-300',
        4 => 'bg-neutral-900 dark:bg-neutral-50',
        'accent' => 'text-neutral-900 dark:text-white',
    ],
};

$levelFor = static function (int $count) use ($safeMax): int {
    if ($count <= 0) {
        return 0;
    }
    $ratio = $count / $safeMax;
    if ($ratio <= 0.25) {
        return 1;
    }
    if ($ratio <= 0.5) {
        return 2;
    }
    if ($ratio <= 0.75) {
        return 3;
    }

    return 4;
};

$cellHeightClass = $compact ? 'h-4 sm:h-5' : 'aspect-square';
@endphp

<div data-heatmap {{ $attributes->class([
    'rounded-2xl border border-neutral-200/90 bg-white p-5 sm:p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900' => $wrapped,
]) }}>
    @if ($title || $subtitle || $showSummary || isset($actions))
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            @if ($title || $subtitle)
                <div>
                    @if ($title)
                        <h3 class="font-display text-base font-semibold tracking-tight text-neutral-900 dark:text-white">{{ $title }}</h3>
                    @endif
                    @if ($subtitle)
                        <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">{{ $subtitle }}</p>
                    @endif
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-3 text-xs">
                @if ($showSummary)
                    <div class="flex flex-wrap items-center gap-2.5 text-xs">
                        <span class="rounded-lg bg-neutral-100 px-2.5 py-1 font-medium text-neutral-600 dark:bg-neutral-800/80 dark:text-neutral-300">
                            Total: <strong class="font-bold text-neutral-900 dark:text-white">{{ number_format($total) }}</strong>
                        </span>
                        <span class="rounded-lg bg-neutral-100 px-2.5 py-1 font-medium text-neutral-600 dark:bg-neutral-800/80 dark:text-neutral-300">
                            Peak: <strong class="font-bold {{ $swatch['accent'] }}">{{ $maxCell > 0 ? $peakLabel.' ('.number_format($maxCell).')' : '—' }}</strong>
                        </span>
                        <span class="hidden sm:inline-flex rounded-lg bg-neutral-100 px-2.5 py-1 font-medium text-neutral-600 dark:bg-neutral-800/80 dark:text-neutral-300">
                            Active slots: <strong class="font-bold text-neutral-900 dark:text-white">{{ $activeSlots }}/168</strong>
                        </span>
                    </div>
                @endif
                {{ $actions ?? '' }}
            </div>
        </div>
    @endif

    <div class="overflow-x-auto pb-1" role="region" aria-label="{{ $title ?? '7 by 24 activity heatmap' }}">
        <div class="min-w-[580px]">
            {{-- Hour header row --}}
            <div class="mb-1.5 grid grid-cols-[42px_repeat(24,minmax(0,1fr))_48px] items-center gap-1 text-center font-mono text-[10px] font-semibold text-neutral-400 dark:text-neutral-500">
                <span></span>
                @for ($hour = 0; $hour < 24; $hour++)
                    <span>{{ $hour % 3 === 0 ? str_pad((string) $hour, 2, '0', STR_PAD_LEFT) : '' }}</span>
                @endfor
                <span class="text-right pr-0.5">Sum</span>
            </div>

            {{-- Weekday rows --}}
            @foreach ($normalized as $weekday => $hours)
                <div class="mb-1 grid grid-cols-[42px_repeat(24,minmax(0,1fr))_48px] items-center gap-1">
                    <span class="font-mono text-[11px] font-semibold text-neutral-500 dark:text-neutral-400">{{ $weekdayLabels[$weekday] }}</span>
                    @foreach ($hours as $hour => $count)
                        @php
                            $lvl = $levelFor($count);
                            $hourLabel = str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00';
                            $unitLabel = $count === 1 ? $unit : $unit.'s';
                            $cellTitle = "{$weekdayLabels[$weekday]} {$hourLabel} UTC — ".number_format($count)." {$unitLabel}";
                        @endphp
                        <span
                            @if ($count > 0) tabindex="0" @endif
                            role="img"
                            aria-label="{{ $cellTitle }}"
                            title="{{ $cellTitle }}"
                            data-tip="{{ $weekdayLabels[$weekday] }} {{ $hourLabel }} · {{ number_format($count) }} {{ $unitLabel }}"
                            class="relative block {{ $cellHeightClass }} w-full rounded-[4px] transition-transform duration-150 hover:scale-125 hover:z-20 focus-visible:scale-125 focus-visible:z-20 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-current hover:after:pointer-events-none hover:after:absolute hover:after:bottom-full hover:after:left-1/2 hover:after:z-30 hover:after:mb-1.5 hover:after:-translate-x-1/2 hover:after:whitespace-nowrap hover:after:rounded-md hover:after:bg-neutral-900 hover:after:px-2 hover:after:py-1 hover:after:text-[10px] hover:after:font-semibold hover:after:text-white hover:after:shadow-lg hover:after:content-[attr(data-tip)] dark:hover:after:bg-neutral-100 dark:hover:after:text-neutral-900 {{ $swatch[$lvl] }}"
                        ></span>
                    @endforeach
                    <span class="pr-0.5 text-right font-mono text-[10px] font-medium text-neutral-400 dark:text-neutral-500">
                        {{ number_format($rowTotals[$weekday]) }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-[11px] text-neutral-400 dark:text-neutral-500">
        <span>UTC weekday &times; hour intensity</span>
        <div class="flex items-center gap-1.5">
            <span>Less</span>
            @for ($lvl = 0; $lvl <= 4; $lvl++)
                <span class="h-2.5 w-2.5 rounded-[3px] {{ $swatch[$lvl] }}"></span>
            @endfor
            <span>More</span>
        </div>
    </div>
</div>
