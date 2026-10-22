@props(['title' => null, 'subtitle' => null, 'backHref' => null, 'backLabel' => 'Back', 'eyebrow' => null])

<div class="mb-7">
    @if ($eyebrow)
        <p class="mb-2 text-[11px] font-bold tracking-[0.16em] text-violet-600 uppercase dark:text-violet-300">{{ $eyebrow }}</p>
    @endif

    @if ($backHref)
        <a href="{{ $backHref }}" class="mb-3 inline-flex items-center gap-1.5 text-sm font-semibold text-violet-700 transition hover:gap-2.5 hover:text-violet-800 dark:text-violet-300 dark:hover:text-violet-200">
            <span aria-hidden="true">&larr;</span>{{ $backLabel }}
        </a>
    @endif

    @if ($title)
        <h1 class="font-display text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl dark:text-white">{{ $title }}</h1>
    @endif

    @if ($subtitle)
        <p class="mt-2 max-w-2xl text-sm text-neutral-500 dark:text-neutral-400">{!! $subtitle !!}</p>
    @endif

    @if (trim($actions ?? '') !== '')
        <div class="mt-5 flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>
