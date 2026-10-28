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
    'showHourlySpark' => true,
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
$colTotals = array_fill(0, 24, 0);
$positiveValues = [];

for ($w = 0; $w < 7; $w++) {
    $normalized[$w] = [];
    for ($h = 0; $h < 24; $h++) {
        $val = (int) ($sourceHeatmap[$w][$h] ?? 0);
        $normalized[$w][$h] = $val;
        $total += $val;
        $rowTotals[$w] += $val;
        $colTotals[$h] += $val;
        if ($val > 0) {
            $activeSlots++;
            $positiveValues[$val] = true;
        }
        if ($val > $maxCell) {
            $maxCell = $val;
            $peakLabel = $weekdayLabels[$w].' '.str_pad((string) $h, 2, '0', STR_PAD_LEFT).':00';
        }
    }
}

$safeMax = max(1, $maxCell);
$safeMaxCol = max(1, max($colTotals));

$distinctPos = array_keys($positiveValues);
sort($distinctPos, SORT_NUMERIC);
$distinctCount = count($distinctPos);

$q1 = $distinctCount >= 4 ? $distinctPos[(int) max(0, floor($distinctCount * 0.25) - 1)] : null;
$q2 = $distinctCount >= 4 ? $distinctPos[(int) max(0, floor($distinctCount * 0.50) - 1)] : null;
$q3 = $distinctCount >= 4 ? $distinctPos[(int) max(0, floor($distinctCount * 0.75) - 1)] : null;

$swatch = match ($resolvedColor) {
    'amber' => [
        0 => 'bg-neutral-100/90 dark:bg-neutral-800/60 ring-1 ring-inset ring-black/[0.03] dark:ring-white/[0.04]',
        1 => 'bg-amber-200/80 dark:bg-amber-950/90 ring-1 ring-inset ring-amber-500/20',
        2 => 'bg-amber-300 dark:bg-amber-800/85',
        3 => 'bg-amber-400 dark:bg-amber-600',
        4 => 'bg-amber-500 dark:bg-amber-400',
        'accent' => 'text-amber-600 dark:text-amber-400',
        'dot' => 'bg-amber-500 dark:bg-amber-400',
        'bar' => 'bg-amber-500/80 dark:bg-amber-400/80',
    ],
    'blue' => [
        0 => 'bg-neutral-100/90 dark:bg-neutral-800/60 ring-1 ring-inset ring-black/[0.03] dark:ring-white/[0.04]',
        1 => 'bg-blue-200/80 dark:bg-blue-950/90 ring-1 ring-inset ring-blue-500/20',
        2 => 'bg-blue-300 dark:bg-blue-800/85',
        3 => 'bg-blue-500 dark:bg-blue-600',
        4 => 'bg-blue-600 dark:bg-blue-400',
        'accent' => 'text-blue-600 dark:text-blue-400',
        'dot' => 'bg-blue-500 dark:bg-blue-400',
        'bar' => 'bg-blue-500/80 dark:bg-blue-400/80',
    ],
    'cyan' => [
        0 => 'bg-neutral-100/90 dark:bg-neutral-800/60 ring-1 ring-inset ring-black/[0.03] dark:ring-white/[0.04]',
        1 => 'bg-cyan-200/80 dark:bg-cyan-950/90 ring-1 ring-inset ring-cyan-500/20',
        2 => 'bg-cyan-300 dark:bg-cyan-800/85',
        3 => 'bg-cyan-500 dark:bg-cyan-600',
        4 => 'bg-cyan-600 dark:bg-cyan-400',
        'accent' => 'text-cyan-600 dark:text-cyan-400',
        'dot' => 'bg-cyan-500 dark:bg-cyan-400',
        'bar' => 'bg-cyan-500/80 dark:bg-cyan-400/80',
    ],
    'indigo' => [
        0 => 'bg-neutral-100/90 dark:bg-neutral-800/60 ring-1 ring-inset ring-black/[0.03] dark:ring-white/[0.04]',
        1 => 'bg-indigo-200/80 dark:bg-indigo-950/90 ring-1 ring-inset ring-indigo-500/20',
        2 => 'bg-indigo-300 dark:bg-indigo-800/85',
        3 => 'bg-indigo-500 dark:bg-indigo-600',
        4 => 'bg-indigo-600 dark:bg-indigo-400',
        'accent' => 'text-indigo-600 dark:text-indigo-400',
        'dot' => 'bg-indigo-500 dark:bg-indigo-400',
        'bar' => 'bg-indigo-500/80 dark:bg-indigo-400/80',
    ],
    'emerald' => [
        0 => 'bg-neutral-100/90 dark:bg-neutral-800/60 ring-1 ring-inset ring-black/[0.03] dark:ring-white/[0.04]',
        1 => 'bg-emerald-200/80 dark:bg-emerald-950/90 ring-1 ring-inset ring-emerald-500/20',
        2 => 'bg-emerald-300 dark:bg-emerald-800/85',
        3 => 'bg-emerald-500 dark:bg-emerald-600',
        4 => 'bg-emerald-600 dark:bg-emerald-400',
        'accent' => 'text-emerald-600 dark:text-emerald-400',
        'dot' => 'bg-emerald-500 dark:bg-emerald-400',
        'bar' => 'bg-emerald-500/80 dark:bg-emerald-400/80',
    ],
    'red' => [
        0 => 'bg-neutral-100/90 dark:bg-neutral-800/60 ring-1 ring-inset ring-black/[0.03] dark:ring-white/[0.04]',
        1 => 'bg-red-200/80 dark:bg-red-950/90 ring-1 ring-inset ring-red-500/20',
        2 => 'bg-red-300 dark:bg-red-800/85',
        3 => 'bg-red-500 dark:bg-red-600',
        4 => 'bg-red-600 dark:bg-red-400',
        'accent' => 'text-red-600 dark:text-red-400',
        'dot' => 'bg-red-500 dark:bg-red-400',
        'bar' => 'bg-red-500/80 dark:bg-red-400/80',
    ],
    'purple' => [
        0 => 'bg-neutral-100/90 dark:bg-neutral-800/60 ring-1 ring-inset ring-black/[0.03] dark:ring-white/[0.04]',
        1 => 'bg-purple-200/80 dark:bg-purple-950/90 ring-1 ring-inset ring-purple-500/20',
        2 => 'bg-purple-300 dark:bg-purple-800/85',
        3 => 'bg-purple-500 dark:bg-purple-600',
        4 => 'bg-purple-600 dark:bg-purple-400',
        'accent' => 'text-purple-600 dark:text-purple-400',
        'dot' => 'bg-purple-500 dark:bg-purple-400',
        'bar' => 'bg-purple-500/80 dark:bg-purple-400/80',
    ],
    default => [
        0 => 'bg-neutral-100/90 dark:bg-neutral-800/60 ring-1 ring-inset ring-black/[0.03] dark:ring-white/[0.04]',
        1 => 'bg-neutral-300 dark:bg-neutral-700',
        2 => 'bg-neutral-400 dark:bg-neutral-500',
        3 => 'bg-neutral-700 dark:bg-neutral-300',
        4 => 'bg-neutral-900 dark:bg-neutral-50',
        'accent' => 'text-neutral-900 dark:text-white',
        'dot' => 'bg-neutral-700 dark:bg-neutral-200',
        'bar' => 'bg-neutral-700/80 dark:bg-neutral-300/80',
    ],
};

$levelFor = static function (int $count) use ($safeMax, $maxCell, $q1, $q2, $q3): int {
    if ($count <= 0) {
        return 0;
    }
    if ($q1 !== null && $q2 !== null && $q3 !== null) {
        if ($count <= $q1) {
            return 1;
        }
        if ($count <= $q2) {
            return 2;
        }
        if ($count <= $q3) {
            return 3;
        }

        return 4;
    }
    if ($count >= $maxCell) {
        return 4;
    }
    $ratio = $count / $safeMax;
    if ($ratio <= 0.33) {
        return 1;
    }
    if ($ratio <= 0.66) {
        return 2;
    }

    return 3;
};

$cellHeightClass = $compact ? 'h-3.5 sm:h-4' : 'h-5 sm:h-6';
@endphp

<div
    data-heatmap
    x-data="{ activeTip: null }"
    {{ $attributes->class([
        'rounded-2xl border border-neutral-200/90 bg-white p-5 sm:p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900' => $wrapped,
    ]) }}
>
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

            <div class="flex flex-wrap items-center gap-2.5 text-xs">
                @if ($showSummary)
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-lg border border-neutral-200/70 bg-neutral-50 px-2.5 py-1 font-medium text-neutral-600 dark:border-neutral-800 dark:bg-neutral-800/70 dark:text-neutral-300">
                            Total: <strong class="font-bold text-neutral-900 dark:text-white">{{ number_format($total) }}</strong>
                        </span>
                        <span class="rounded-lg border border-neutral-200/70 bg-neutral-50 px-2.5 py-1 font-medium text-neutral-600 dark:border-neutral-800 dark:bg-neutral-800/70 dark:text-neutral-300">
                            Peak: <strong class="font-bold {{ $swatch['accent'] }}">{{ $maxCell > 0 ? $peakLabel.' ('.number_format($maxCell).')' : '—' }}</strong>
                        </span>
                        <span class="hidden sm:inline-flex rounded-lg border border-neutral-200/70 bg-neutral-50 px-2.5 py-1 font-medium text-neutral-600 dark:border-neutral-800 dark:bg-neutral-800/70 dark:text-neutral-300">
                            Active slots: <strong class="font-bold text-neutral-900 dark:text-white">{{ $activeSlots }}/168</strong>
                        </span>
                    </div>
                @endif
                {{ $actions ?? '' }}
            </div>
        </div>
    @endif

    <div
        class="overflow-x-auto pb-1"
        role="region"
        aria-label="{{ $title ?? '7 by 24 activity heatmap' }}"
        @mouseover="activeTip = $event.target.closest('[data-tip]')?.dataset.tip ?? null"
        @focusin="activeTip = $event.target.closest('[data-tip]')?.dataset.tip ?? null"
        @mouseleave="activeTip = null"
        @focusout="activeTip = null"
    >
        <div class="min-w-[580px]">
            {{-- Hour header row --}}
            <div class="mb-1.5 grid grid-cols-[40px_repeat(24,minmax(0,1fr))_50px] items-center gap-1 text-center font-mono text-[10px] font-semibold text-neutral-400 dark:text-neutral-500">
                <span class="text-left text-[9px] tracking-wider uppercase">UTC</span>
                @for ($hour = 0; $hour < 24; $hour++)
                    <span>{{ $hour % 2 === 0 ? str_pad((string) $hour, 2, '0', STR_PAD_LEFT) : '' }}</span>
                @endfor
                <span class="pr-0.5 text-right">Sum</span>
            </div>

            {{-- Weekday rows --}}
            @foreach ($normalized as $weekday => $hours)
                @php
                    $rowPos = $weekday <= 1 ? ' tl-heat-cell--down' : '';
                @endphp
                <div class="mb-1 grid grid-cols-[40px_repeat(24,minmax(0,1fr))_50px] items-center gap-1">
                    <span class="font-mono text-[11px] font-semibold text-neutral-500 dark:text-neutral-400">{{ $weekdayLabels[$weekday] }}</span>
                    @foreach ($hours as $hour => $count)
                        @php
                            $lvl = $levelFor($count);
                            $hourLabel = str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00';
                            $unitLabel = $count === 1 ? $unit : $unit.'s';
                            $tipText = "{$weekdayLabels[$weekday]} {$hourLabel} UTC · ".number_format($count)." {$unitLabel}";
                            $colPos = $hour <= 1 ? ' tl-heat-cell--left' : ($hour >= 22 ? ' tl-heat-cell--right' : '');
                        @endphp
                        <span @if ($count > 0) tabindex="0" aria-label="{{ $tipText }}" @endif data-tip="{{ $tipText }}" class="tl-heat-cell{{ $rowPos }}{{ $colPos }} {{ $cellHeightClass }} rounded-[4px] {{ $swatch[$lvl] }}"></span>
                    @endforeach
                    <span class="pr-0.5 text-right font-mono text-[10px] font-medium text-neutral-500 dark:text-neutral-400">{{ number_format($rowTotals[$weekday]) }}</span>
                </div>
            @endforeach

            {{-- 24-hour aggregate spark-bars --}}
            @if ($showHourlySpark && ! $compact && $total > 0)
                <div class="mt-2 grid grid-cols-[40px_repeat(24,minmax(0,1fr))_50px] items-end gap-1 border-t border-neutral-100 pt-2 dark:border-neutral-800/80">
                    <span class="font-mono text-[9px] font-semibold tracking-wider text-neutral-400 uppercase dark:text-neutral-500">24h</span>
                    @for ($hour = 0; $hour < 24; $hour++)
                        @php
                            $colCount = $colTotals[$hour];
                            $pct = $colCount > 0 ? max(16, (int) round(($colCount / $safeMaxCol) * 100)) : 8;
                            $hrStr = str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00';
                            $hrUnit = $colCount === 1 ? $unit : $unit.'s';
                        @endphp
                        <span class="flex h-4 w-full items-end" data-tip="{{ $hrStr }} UTC (all days) · {{ number_format($colCount) }} {{ $hrUnit }}"><span class="block w-full rounded-t-[2px] {{ $colCount > 0 ? $swatch['bar'] : 'bg-neutral-200/70 dark:bg-neutral-800' }}" style="height:{{ $pct }}%"></span></span>
                    @endfor
                    <span class="pr-0.5 text-right font-mono text-[10px] font-semibold text-neutral-600 dark:text-neutral-300">{{ number_format($total) }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-[11px] text-neutral-400 dark:text-neutral-500">
        <div class="flex min-h-[18px] items-center gap-1.5">
            <template x-if="activeTip">
                <span class="inline-flex items-center gap-1.5 font-mono font-medium text-neutral-700 dark:text-neutral-200">
                    <span class="h-2 w-2 rounded-full {{ $swatch['dot'] }}"></span>
                    <span x-text="activeTip"></span>
                </span>
            </template>
            <template x-if="!activeTip">
                <span>UTC weekday &times; hour intensity &middot; hover a slot to inspect</span>
            </template>
        </div>
        <div class="flex items-center gap-1.5">
            <span>Less</span>
            @for ($lvl = 0; $lvl <= 4; $lvl++)
                <span class="h-2.5 w-2.5 rounded-[3px] {{ $swatch[$lvl] }}"></span>
            @endfor
            <span>More</span>
        </div>
    </div>
</div>
