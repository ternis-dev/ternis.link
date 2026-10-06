@props([
    'variant' => 'secondary', // primary|secondary|danger|ghost
    'size' => 'md', // sm|md|lg
    'href' => null,
    'type' => 'button',
])

@php
$base = 'inline-flex cursor-pointer items-center justify-center gap-2 rounded-full font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-50';

$sizes = [
    'sm' => 'px-3 py-1.5 text-xs',
    'md' => 'px-5 py-2.5 text-sm',
    'lg' => 'px-7 py-3 text-base',
];

$variants = [
    'primary' => 'bg-emerald-600 text-white hover:bg-emerald-500 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-emerald-950',
    'secondary' => 'border border-neutral-300 text-neutral-800 hover:border-emerald-600 hover:text-emerald-700 dark:border-neutral-700 dark:text-neutral-200 dark:hover:border-emerald-400 dark:hover:text-emerald-300',
    'danger' => 'border border-red-300 text-red-700 hover:bg-red-50 dark:border-red-400/30 dark:text-red-300 dark:hover:bg-red-400/10',
    'ghost' => 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white',
];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "$base {$sizes[$size]} {$variants[$variant]}"]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "$base {$sizes[$size]} {$variants[$variant]}"]) }}>{{ $slot }}</button>
@endif
