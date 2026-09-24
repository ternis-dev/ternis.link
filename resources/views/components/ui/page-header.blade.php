@props(['title' => null, 'subtitle' => null, 'backHref' => null, 'backLabel' => 'Back'])

<div class="mb-8 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        @if ($backHref)
            <a href="{{ $backHref }}" class="mb-2 inline-block text-sm text-neutral-500 hover:text-neutral-900 dark:text-neutral-500 dark:hover:text-white">← {{ $backLabel }}</a>
        @endif
        @if ($title)
            <h1 class="font-display text-2xl font-bold tracking-tight">{{ $title }}</h1>
        @endif
        @if ($subtitle)
            <p class="mt-1 max-w-2xl text-sm text-neutral-500 dark:text-neutral-400">{!! $subtitle !!}</p>
        @endif
    </div>
    @if (trim($actions ?? '') !== '')
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>
