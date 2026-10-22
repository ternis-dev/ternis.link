@props([
    'variant' => 'secondary', // primary|secondary|ghost|danger|subtle
    'size' => 'md', // sm|md|lg
    'href' => null,
    'type' => 'button',
    'icon' => null,
])

@php
    /*
     * my.href.nz "elevated" theme — deliberately different from the
     * flat emerald my.ternis.link: soft shadows, rounded-xl instead of
     * pills, and a violet-to-blue accent.
     */
    $base = 'inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl font-semibold transition-all duration-150 disabled:cursor-not-allowed disabled:opacity-50';

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-6 py-2.5 text-base',
    ];

    $variants = [
        'primary' => 'bg-gradient-to-br from-violet-600 to-blue-600 text-white shadow-sm shadow-violet-600/25 hover:from-violet-500 hover:to-blue-500 hover:shadow-md hover:shadow-violet-600/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600',
        'secondary' => 'border border-neutral-200 bg-white text-neutral-800 shadow-sm shadow-neutral-900/5 hover:border-violet-300 hover:text-violet-700 hover:shadow-md hover:shadow-neutral-900/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600 dark:border-white/10 dark:bg-white/5 dark:text-neutral-100 dark:hover:border-violet-400/50 dark:hover:text-violet-200',
        'subtle' => 'bg-violet-50 text-violet-700 hover:bg-violet-100 dark:bg-violet-400/10 dark:text-violet-200 dark:hover:bg-violet-400/20',
        'ghost' => 'text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-white/5 dark:hover:text-white',
        'danger' => 'border border-red-200 bg-white text-red-700 shadow-sm shadow-neutral-900/5 hover:border-red-300 hover:bg-red-50 dark:border-red-400/20 dark:bg-transparent dark:text-red-300 dark:hover:bg-red-400/10',
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "$base {$sizes[$size]} {$variants[$variant]}"]) }}>
        @if ($icon)<span class="shrink-0">{!! $icon !!}</span>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "$base {$sizes[$size]} {$variants[$variant]}"]) }}>
        @if ($icon)<span class="shrink-0">{!! $icon !!}</span>@endif
        {{ $slot }}
    </button>
@endif
