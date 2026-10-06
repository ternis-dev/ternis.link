@props(['title' => null, 'subtitle' => null, 'backHref' => null, 'backLabel' => 'Back'])

<div class="mb-6">
    @if ($backHref)
        <a href="{{ $backHref }}" class="mb-2 inline-block text-sm font-semibold text-emerald-700 hover:underline dark:text-emerald-300">← {{ $backLabel }}</a>
    @endif
    @if ($title)
        <h1 class="font-display text-2xl font-bold tracking-tight">{{ $title }}</h1>
    @endif
    @if ($subtitle)
        <p class="mt-1 max-w-2xl text-sm text-neutral-500 dark:text-neutral-400">{!! $subtitle !!}</p>
    @endif
    @if (trim($actions ?? '') !== '')
        <div class="mt-4 flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>
