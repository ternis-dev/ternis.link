@props(['title', 'subtitle', 'section', 'params' => []])

@php
$sections = [
    'overview' => ['label' => 'Overview', 'icon' => '<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>'],
    'errors' => ['label' => 'Errors', 'icon' => '<circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>'],
    'activities' => ['label' => 'Audit Activities', 'icon' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>'],
    'links' => ['label' => 'Links', 'icon' => '<path d="M10 13a5 5 0 0 0 7.54.54l2.1-2.1a5 5 0 0 0-7.07-7.07l-1.06 1.06M14 11a5 5 0 0 0-7.54-.54l-2.1 2.1a5 5 0 0 0 7.07 7.07l1.06-1.06"/>'],
    'clicks' => ['label' => 'Link Clicks', 'icon' => '<path d="m3 3 7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/><path d="m13 13 6 6"/>'],
    'ratio' => ['label' => 'Link/Clicks Ratio', 'icon' => '<path d="M16 16v1a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v1"/><path d="M18 8h4a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-4"/><circle cx="8" cy="12" r="2"/>'],
];

$currentDays = $params['days'] ?? '30';
$timeRanges = [
    '7' => '7 Days',
    '14' => '14 Days',
    '30' => '30 Days',
    '90' => '90 Days',
    'all' => 'All Time',
];
@endphp

<div class="mb-6 space-y-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{!! $title !!}</h1>
            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{!! $subtitle !!}</p>
        </div>

        {{-- Time window selector --}}
        <div class="flex items-center gap-1 rounded-xl border border-neutral-200 bg-white p-1 text-xs shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            @foreach ($timeRanges as $val => $lbl)
                @php
                $rangeParams = array_merge($params, ['days' => $val]);
                $isActive = (string) $currentDays === (string) $val;
                @endphp
                <a
                    href="{{ route('admin.stats.show', ['section' => $section] + $rangeParams) }}"
                    @class([
                        'rounded-lg px-2.5 py-1 font-semibold transition',
                        'bg-amber-500 text-white shadow-sm' => $isActive,
                        'text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white' => ! $isActive,
                    ])
                >
                    {{ $lbl }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Section Sub-navigation Tabs --}}
    <div class="flex gap-1.5 overflow-x-auto border-b border-neutral-200 pb-3 dark:border-neutral-800 no-scrollbar">
        @foreach ($sections as $key => $meta)
            @php
            $tabParams = array_merge($params, ['section' => $key]);
            $isCurrentSection = $section === $key;
            @endphp
            <a
                href="{{ route('admin.stats.show', $tabParams) }}"
                @class([
                    'inline-flex shrink-0 items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-semibold transition',
                    'bg-neutral-900 text-white shadow-sm dark:bg-white dark:text-neutral-900' => $isCurrentSection,
                    'border border-neutral-200 bg-white text-neutral-600 hover:border-neutral-300 hover:text-neutral-900 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-400 dark:hover:border-neutral-700 dark:hover:text-white' => ! $isCurrentSection,
                ])
            >
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $meta['icon'] !!}</svg>
                <span>{{ $meta['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>
