@props([
    'variant' => 'secondary', // primary|secondary|ghost|danger|subtle
    'size' => 'md', // sm|md|lg
    'href' => null,
    'type' => 'button',
    'icon' => null,
])

@php
    $base = 'inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-50 active:scale-[0.98]';

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-6 py-2.5 text-base',
    ];

    $variants = [
        'primary' => 'bg-gradient-to-br from-violet-600 via-indigo-600 to-blue-600 text-white shadow-sm shadow-violet-600/30 hover:from-violet-500 hover:to-blue-500 hover:shadow-md hover:shadow-violet-600/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600',
        'secondary' => 'border border-neutral-200 bg-white/80 text-neutral-800 shadow-sm shadow-neutral-900/5 hover:border-violet-300 hover:bg-neutral-50 hover:text-violet-700 hover:shadow-md hover:shadow-neutral-900/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600 dark:border-white/10 dark:bg-white/5 dark:text-neutral-100 dark:hover:border-violet-400/50 dark:hover:bg-white/10 dark:hover:text-violet-200',
        'subtle' => 'bg-violet-50 text-violet-700 hover:bg-violet-100 dark:bg-violet-400/10 dark:text-violet-200 dark:hover:bg-violet-400/20',
        'ghost' => 'text-neutral-500 hover:bg-neutral-100/80 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-white/5 dark:hover:text-white',
        'danger' => 'border border-rose-200 bg-white text-rose-700 shadow-sm hover:border-rose-300 hover:bg-rose-50 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20',
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
