@props([
    'days' => [],
    'title' => null,
    'subtitle' => null,
    'color' => 'neutral',
    'palette' => null,
    'unit' => 'click',
    'wrapped' => true,
    'showSummary' => true,
])

@php
$resolvedColor = $palette ?? $color;
$entries = [];
$total = 0;
$maxDay = 0;
$activeDays = 0;
$peakDateLabel = '—';
$positiveValues = [];

foreach ($days as $dateStr => $val) {
    $count = (int) $val;
    $dt = \Carbon\Carbon::parse($dateStr);
    $entries[] = [
        'date' => $dt->format('Y-m-d'),
        'dayOfMonth' => (int) $dt->day,
        'label' => $dt->format('D, M j, Y'),
        'short' => $dt->format('M j'),
        'month' => $dt->format('M'),
        'weekday' => ((int) $dt->dayOfWeekIso) - 1, // 0 = Mon .. 6 = Sun
        'count' => $count,
    ];
    $total += $count;
    if ($count > 0) {
        $activeDays++;
        $positiveValues[$count] = true;
    }
    if ($count > $maxDay) {
        $maxDay = $count;
        $peakDateLabel = $dt->format('M j');
    }
}

$safeMax = max(1, $maxDay);
$dayCount = count($entries);
$avgPerDay = $dayCount > 0 ? round($total / $dayCount, 1) : 0;

$distinctPos = array_keys($positiveValues);
sort($distinctPos, SORT_NUMERIC);
$distinctCount = count($distinctPos);

$q1 = $distinctCount >= 4 ? $distinctPos[(int) max(0, floor($distinctCount * 0.25) - 1)] : null;
$q2 = $distinctCount >= 4 ? $distinctPos[(int) max(0, floor($distinctCount * 0.50) - 1)] : null;
$q3 = $distinctCount >= 4 ? $distinctPos[(int) max(0, floor($distinctCount * 0.75) - 1)] : null;

$weeks = [];
$weekMonths = [];
if (! empty($entries)) {
    $currentWeek = array_fill(0, 7, null);

    foreach ($entries as $idx => $entry) {
        $wd = $entry['weekday'];
        if ($wd === 0 && $idx > 0) {
            $weeks[] = $currentWeek;
            $currentWeek = array_fill(0, 7, null);
        }
        $currentWeek[$wd] = $entry;
    }
    $weeks[] = $currentWeek;

    $lastMonth = null;
    $lastLabeledIdx = -99;

    foreach ($weeks as $wIdx => $week) {
        $weekMonths[$wIdx] = '';
        $firstOfMonthCell = collect($week)->first(fn ($c) => $c !== null && $c['dayOfMonth'] === 1);
        $fallbackCell = $wIdx === 0 ? collect($week)->first(fn ($c) => $c !== null) : null;
        $candidate = $firstOfMonthCell ?? $fallbackCell;
        $m = $candidate['month'] ?? '';

        if ($m !== '' && $m !== $lastMonth) {
            if ($lastLabeledIdx === 0 && ($wIdx - $lastLabeledIdx) < 3) {
                $weekMonths[0] = '';
                $weekMonths[$wIdx] = $m;
                $lastLabeledIdx = $wIdx;
            } elseif (($wIdx - $lastLabeledIdx) >= 2) {
                $weekMonths[$wIdx] = $m;
                $lastLabeledIdx = $wIdx;
            }
            $lastMonth = $m;
        }
    }
}

$weekCount = count($weeks);

$swatch = match ($resolvedColor) {
    'amber' => [
        0 => 'bg-neutral-100/90 dark:bg-neutral-800/60 ring-1 ring-inset ring-black/[0.03] dark:ring-white/[0.04]',
        1 => 'bg-amber-200/80 dark:bg-amber-950/90 ring-1 ring-inset ring-amber-500/20',
        2 => 'bg-amber-300 dark:bg-amber-800/85',
        3 => 'bg-amber-400 dark:bg-amber-600',
        4 => 'bg-amber-500 dark:bg-amber-400',
        'accent' => 'text-amber-600 dark:text-amber-400',
        'dot' => 'bg-amber-500 dark:bg-amber-400',
    ],
    'blue' => [
        0 => 'bg-neutral-100/90 dark:bg-neutral-800/60 ring-1 ring-inset ring-black/[0.03] dark:ring-white/[0.04]',
        1 => 'bg-blue-200/80 dark:bg-blue-950/90 ring-1 ring-inset ring-blue-500/20',
        2 => 'bg-blue-300 dark:bg-blue-800/85',
        3 => 'bg-blue-500 dark:bg-blue-600',
        4 => 'bg-blue-600 dark:bg-blue-400',
        'accent' => 'text-blue-600 dark:text-blue-400',
        'dot' => 'bg-blue-500 dark:bg-blue-400',
    ],
    'cyan' => [
        0 => 'bg-neutral-100/90 dark:bg-neutral-800/60 ring-1 ring-inset ring-black/[0.03] dark:ring-white/[0.04]',
        1 => 'bg-cyan-200/80 dark:bg-cyan-950/90 ring-1 ring-inset ring-cyan-500/20',
        2 => 'bg-cyan-300 dark:bg-cyan-800/85',
        3 => 'bg-cyan-500 dark:bg-cyan-600',
        4 => 'bg-cyan-600 dark:bg-cyan-400',
        'accent' => 'text-cyan-600 dark:text-cyan-400',
        'dot' => 'bg-cyan-500 dark:bg-cyan-400',
    ],
    'indigo' => [
        0 => 'bg-neutral-100/90 dark:bg-neutral-800/60 ring-1 ring-inset ring-black/[0.03] dark:ring-white/[0.04]',
        1 => 'bg-indigo-200/80 dark:bg-indigo-950/90 ring-1 ring-inset ring-indigo-500/20',
        2 => 'bg-indigo-300 dark:bg-indigo-800/85',
        3 => 'bg-indigo-500 dark:bg-indigo-600',
        4 => 'bg-indigo-600 dark:bg-indigo-400',
        'accent' => 'text-indigo-600 dark:text-indigo-400',
        'dot' => 'bg-indigo-500 dark:bg-indigo-400',
    ],
    'emerald' => [
        0 => 'bg-neutral-100/90 dark:bg-neutral-800/60 ring-1 ring-inset ring-black/[0.03] dark:ring-white/[0.04]',
        1 => 'bg-emerald-200/80 dark:bg-emerald-950/90 ring-1 ring-inset ring-emerald-500/20',
        2 => 'bg-emerald-300 dark:bg-emerald-800/85',
        3 => 'bg-emerald-500 dark:bg-emerald-600',
        4 => 'bg-emerald-600 dark:bg-emerald-400',
        'accent' => 'text-emerald-600 dark:text-emerald-400',
        'dot' => 'bg-emerald-500 dark:bg-emerald-400',
    ],
    'red' => [
        0 => 'bg-neutral-100/90 dark:bg-neutral-800/60 ring-1 ring-inset ring-black/[0.03] dark:ring-white/[0.04]',
        1 => 'bg-red-200/80 dark:bg-red-950/90 ring-1 ring-inset ring-red-500/20',
        2 => 'bg-red-300 dark:bg-red-800/85',
        3 => 'bg-red-500 dark:bg-red-600',
        4 => 'bg-red-600 dark:bg-red-400',
        'accent' => 'text-red-600 dark:text-red-400',
        'dot' => 'bg-red-500 dark:bg-red-400',
    ],
    'purple' => [
        0 => 'bg-neutral-100/90 dark:bg-neutral-800/60 ring-1 ring-inset ring-black/[0.03] dark:ring-white/[0.04]',
        1 => 'bg-purple-200/80 dark:bg-purple-950/90 ring-1 ring-inset ring-purple-500/20',
        2 => 'bg-purple-300 dark:bg-purple-800/85',
        3 => 'bg-purple-500 dark:bg-purple-600',
        4 => 'bg-purple-600 dark:bg-purple-400',
        'accent' => 'text-purple-600 dark:text-purple-400',
        'dot' => 'bg-purple-500 dark:bg-purple-400',
    ],
    default => [
        0 => 'bg-neutral-100/90 dark:bg-neutral-800/60 ring-1 ring-inset ring-black/[0.03] dark:ring-white/[0.04]',
        1 => 'bg-neutral-300 dark:bg-neutral-700',
        2 => 'bg-neutral-400 dark:bg-neutral-500',
        3 => 'bg-neutral-700 dark:bg-neutral-300',
        4 => 'bg-neutral-900 dark:bg-neutral-50',
        'accent' => 'text-neutral-900 dark:text-white',
        'dot' => 'bg-neutral-700 dark:bg-neutral-200',
    ],
};

$levelFor = static function (int $count) use ($safeMax, $maxDay, $q1, $q2, $q3): int {
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
    if ($count >= $maxDay) {
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

$weekdayAxis = [0 => 'Mon', 1 => '', 2 => 'Wed', 3 => '', 4 => 'Fri', 5 => '', 6 => 'Sun'];
@endphp

<div
    data-calendar-heatmap
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
                            Peak day: <strong class="font-bold {{ $swatch['accent'] }}">{{ $maxDay > 0 ? $peakDateLabel.' ('.number_format($maxDay).')' : '—' }}</strong>
                        </span>
                        <span class="hidden sm:inline-flex rounded-lg border border-neutral-200/70 bg-neutral-50 px-2.5 py-1 font-medium text-neutral-600 dark:border-neutral-800 dark:bg-neutral-800/70 dark:text-neutral-300">
                            Active days: <strong class="font-bold text-neutral-900 dark:text-white">{{ $activeDays }}/{{ $dayCount }}</strong>
                        </span>
                        <span class="hidden md:inline-flex rounded-lg border border-neutral-200/70 bg-neutral-50 px-2.5 py-1 font-medium text-neutral-600 dark:border-neutral-800 dark:bg-neutral-800/70 dark:text-neutral-300">
                            Avg/day: <strong class="font-bold text-neutral-900 dark:text-white">{{ number_format($avgPerDay, $avgPerDay == (int) $avgPerDay ? 0 : 1) }}</strong>
                        </span>
                    </div>
                @endif
                {{ $actions ?? '' }}
            </div>
        </div>
    @endif

    @if (empty($weeks))
        <div class="flex h-28 items-center justify-center rounded-xl bg-neutral-50 text-xs text-neutral-400 dark:bg-neutral-950/40">
            No daily activity available.
        </div>
    @else
        <div
            class="overflow-x-auto pb-1"
            role="region"
            aria-label="{{ $title ?? 'Daily calendar activity heatmap' }}"
            @mouseover="activeTip = $event.target.closest('[data-tip]')?.dataset.tip ?? null"
            @focusin="activeTip = $event.target.closest('[data-tip]')?.dataset.tip ?? null"
            @mouseleave="activeTip = null"
            @focusout="activeTip = null"
        >
            <div style="min-width:{{ max(320, $weekCount * 14 + 38) }}px">
                {{-- Month header row --}}
                <div class="mb-1.5 flex items-center gap-2">
                    <span class="w-7 shrink-0"></span>
                    <div
                        class="grid flex-1 gap-[3px] font-mono text-[10px] font-semibold text-neutral-400 dark:text-neutral-500"
                        style="grid-template-columns:repeat({{ $weekCount }},minmax(11px,1fr))"
                    >
                        @foreach ($weeks as $wIdx => $week)
                            <span class="overflow-visible whitespace-nowrap leading-none">{{ $weekMonths[$wIdx] ?? '' }}</span>
                        @endforeach
                    </div>
                </div>

                {{-- Weekday rows across all weeks --}}
                <div class="space-y-[3px]">
                    @for ($wd = 0; $wd < 7; $wd++)
                        @php
                            $rowPos = $wd <= 1 ? ' tl-heat-cell--down' : '';
                        @endphp
                        <div class="flex items-center gap-2">
                            <span class="w-7 shrink-0 font-mono text-[10px] font-semibold leading-none text-neutral-400 dark:text-neutral-500">{{ $weekdayAxis[$wd] }}</span>
                            <div
                                class="grid flex-1 gap-[3px]"
                                style="grid-template-columns:repeat({{ $weekCount }},minmax(11px,1fr))"
                            >
                                @foreach ($weeks as $wIdx => $week)
                                    @if ($week[$wd] === null)
                                        <span class="pointer-events-none block h-3.5 sm:h-4 w-full opacity-0"></span>
                                    @else
                                        @php
                                            $cell = $week[$wd];
                                            $lvl = $levelFor($cell['count']);
                                            $unitLabel = $cell['count'] === 1 ? $unit : $unit.'s';
                                            $tipText = "{$cell['label']} · ".number_format($cell['count'])." {$unitLabel}";
                                            $colPos = $wIdx <= 3 ? ' tl-heat-cell--left' : ($wIdx >= ($weekCount - 4) ? ' tl-heat-cell--right' : '');
                                        @endphp
                                        <span @if ($cell['count'] > 0) tabindex="0" aria-label="{{ $tipText }}" @endif data-tip="{{ $tipText }}" class="tl-heat-cell{{ $rowPos }}{{ $colPos }} h-3.5 sm:h-4 rounded-[3.5px] {{ $swatch[$lvl] }}"></span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    @endif

    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-[11px] text-neutral-400 dark:text-neutral-500">
        <div class="flex min-h-[18px] items-center gap-1.5">
            <template x-if="activeTip">
                <span class="inline-flex items-center gap-1.5 font-mono font-medium text-neutral-700 dark:text-neutral-200">
                    <span class="h-2 w-2 rounded-full {{ $swatch['dot'] }}"></span>
                    <span x-text="activeTip"></span>
                </span>
            </template>
            <template x-if="!activeTip">
                <span>Daily contribution calendar ({{ $dayCount }} days) &middot; hover a day to inspect</span>
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
