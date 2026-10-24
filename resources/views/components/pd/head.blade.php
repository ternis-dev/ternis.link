@props(['title' => null, 'subtitle' => null, 'backHref' => null, 'backLabel' => 'Back'])

<div class="mb-8">
    @if ($backHref)
        <a href="{{ $backHref }}" class="group mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
            <svg class="h-3.5 w-3.5 transition-transform group-hover:-translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            {{ $backLabel }}
        </a>
    @endif
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            @if ($title)
                <h1 class="font-display text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl dark:text-white">{{ $title }}</h1>
            @endif
            @if ($subtitle)
                <p class="mt-1.5 max-w-2xl text-sm text-slate-500 dark:text-slate-400 leading-relaxed">{!! $subtitle !!}</p>
            @endif
        </div>
        @if (trim($actions ?? '') !== '')
            <div class="flex shrink-0 flex-wrap items-center gap-2.5">{{ $actions }}</div>
        @endif
    </div>
</div>
