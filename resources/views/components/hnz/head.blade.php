@props(['title' => null, 'subtitle' => null, 'backHref' => null, 'backLabel' => 'Back', 'eyebrow' => null])

<div class="mb-8">
    @if ($eyebrow)
        <p class="mb-2 text-[11px] font-bold tracking-[0.18em] text-violet-600 uppercase dark:text-violet-400">{{ $eyebrow }}</p>
    @endif

    @if ($backHref)
        <a href="{{ $backHref }}" class="group mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-violet-600 transition hover:gap-2.5 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300">
            <span class="transition-transform group-hover:-translate-x-0.5" aria-hidden="true">&larr;</span>{{ $backLabel }}
        </a>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            @if ($title)
                <h1 class="font-display text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl dark:text-white">{{ $title }}</h1>
            @endif

            @if ($subtitle)
                <p class="mt-1.5 max-w-2xl text-sm text-neutral-500 dark:text-neutral-400">{!! $subtitle !!}</p>
            @endif
        </div>

        @if (isset($actions) && trim($actions ?? '') !== '')
            <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
        @endif
    </div>
</div>
