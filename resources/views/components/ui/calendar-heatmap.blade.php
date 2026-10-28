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

foreach ($days as $dateStr => $val) {
    $count = (int) $val;
    $dt = \Carbon\Carbon::parse($dateStr);
    $entries[] = [
        'date' => $dt->format('Y-m-d'),
        'label' => $dt->format('M j, Y'),
        'short' => $dt->format('M j'),
        'month' => $dt->format('M'),
        'weekday' => ((int) $dt->dayOfWeekIso) - 1, // 0 = Mon .. 6 = Sun
        'count' => $count,
    ];
    $total += $count;
    if ($count > 0) {
        $activeDays++;
    }
    if ($count > $maxDay) {
        $maxDay = $count;
        $peakDateLabel = $dt->format('M j');
    }
}

$safeMax = max(1, $maxDay);
$dayCount = count($entries);
$avgPerDay = $dayCount > 0 ? round($total / $dayCount, 1) : 0;

$weeks = [];
$weekMonths = [];
if (! empty($entries)) {
    $currentWeek = array_fill(0, 7, null);
    $lastMonth = null;

    foreach ($entries as $idx => $entry) {
        $wd = $entry['weekday'];
        if ($wd === 0 && $idx > 0) {
            $weeks[] = $currentWeek;
            $currentWeek = array_fill(0, 7, null);
        }
        $currentWeek[$wd] = $entry;
    }
    $weeks[] = $currentWeek;

    foreach ($weeks as $wIdx => $week) {
        $firstInWeek = collect($week)->first(fn ($c) => $c !== null);
        $m = $firstInWeek['month'] ?? '';
        if ($m !== '' && $m !== $lastMonth) {
            $weekMonths[$wIdx] = $m;
            $lastMonth = $m;
        } else {
            $weekMonths[$wIdx] = '';
        }
    }
}

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

$weekdayAxis = [0 => 'Mon', 1 => '', 2 => 'Wed', 3 => '', 4 => 'Fri', 5 => '', 6 => 'Sun'];
@endphp

<div data-calendar-heatmap {{ $attributes->class([
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
                            Peak day: <strong class="font-bold {{ $swatch['accent'] }}">{{ $maxDay > 0 ? $peakDateLabel.' ('.number_format($maxDay).')' : '—' }}</strong>
                        </span>
                        <span class="hidden sm:inline-flex rounded-lg bg-neutral-100 px-2.5 py-1 font-medium text-neutral-600 dark:bg-neutral-800/80 dark:text-neutral-300">
                            Active days: <strong class="font-bold text-neutral-900 dark:text-white">{{ $activeDays }}/{{ $dayCount }}</strong>
                        </span>
                        <span class="hidden md:inline-flex rounded-lg bg-neutral-100 px-2.5 py-1 font-medium text-neutral-600 dark:bg-neutral-800/80 dark:text-neutral-300">
                            Avg/day: <strong class="font-bold text-neutral-900 dark:text-white">{{ $avgPerDay }}</strong>
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
        <div class="overflow-x-auto pb-1" role="region" aria-label="{{ $title ?? 'Daily calendar activity heatmap' }}">
            <div class="inline-flex min-w-full gap-2">
                {{-- Weekday left labels --}}
                <div class="flex shrink-0 flex-col justify-between pt-5 pb-0.5 font-mono text-[10px] font-semibold text-neutral-400 dark:text-neutral-500">
                    @for ($wd = 0; $wd < 7; $wd++)
                        <span class="flex h-3.5 items-center leading-none">{{ $weekdayAxis[$wd] }}</span>
                    @endfor
                </div>

                {{-- Weeks grid --}}
                <div class="flex flex-1 justify-between gap-[3px]">
                    @foreach ($weeks as $wIdx => $week)
                        <div class="flex flex-1 flex-col items-center gap-[3px]">
                            <span class="h-4 w-full text-left font-mono text-[10px] font-semibold text-neutral-400 dark:text-neutral-500 leading-none">
                                {{ $weekMonths[$wIdx] ?? '' }}
                            </span>
                            @for ($wd = 0; $wd < 7; $wd++)
                                @if ($week[$wd] === null)
                                    <span class="h-3.5 w-full min-w-[11px] rounded-[3px] opacity-0"></span>
                                @else
                                    @php
                                        $cell = $week[$wd];
                                        $lvl = $levelFor($cell['count']);
                                        $unitLabel = $cell['count'] === 1 ? $unit : $unit.'s';
                                        $cellTitle = "{$cell['label']} — ".number_format($cell['count'])." {$unitLabel}";
                                    @endphp
                                    <span
                                        @if ($cell['count'] > 0) tabindex="0" @endif
                                        role="img"
                                        aria-label="{{ $cellTitle }}"
                                        title="{{ $cellTitle }}"
                                        data-tip="{{ $cell['label'] }} · {{ number_format($cell['count']) }} {{ $unitLabel }}"
                                        class="relative block h-3.5 w-full min-w-[11px] rounded-[3px] transition-transform duration-150 hover:scale-125 hover:z-20 focus-visible:scale-125 focus-visible:z-20 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-current hover:after:pointer-events-none hover:after:absolute hover:after:bottom-full hover:after:left-1/2 hover:after:z-30 hover:after:mb-1.5 hover:after:-translate-x-1/2 hover:after:whitespace-nowrap hover:after:rounded-md hover:after:bg-neutral-900 hover:after:px-2 hover:after:py-1 hover:after:text-[10px] hover:after:font-semibold hover:after:text-white hover:after:shadow-lg hover:after:content-[attr(data-tip)] dark:hover:after:bg-neutral-100 dark:hover:after:text-neutral-900 {{ $swatch[$lvl] }}"
                                    ></span>
                                @endif
                            @endfor
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-[11px] text-neutral-400 dark:text-neutral-500">
        <span>Daily contribution calendar ({{ $dayCount }} days)</span>
        <div class="flex items-center gap-1.5">
            <span>Less</span>
            @for ($lvl = 0; $lvl <= 4; $lvl++)
                <span class="h-2.5 w-2.5 rounded-[3px] {{ $swatch[$lvl] }}"></span>
            @endfor
            <span>More</span>
        </div>
    </div>
</div>
