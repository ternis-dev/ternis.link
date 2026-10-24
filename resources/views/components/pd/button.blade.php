@props([
    'variant' => 'secondary', // primary|secondary|danger|ghost
    'size' => 'md', // sm|md|lg
    'href' => null,
    'type' => 'button',
    'icon' => null,
])

@php
$base = 'inline-flex cursor-pointer items-center justify-center gap-2 font-semibold transition-all duration-150 disabled:cursor-not-allowed disabled:opacity-50 select-none';

$sizes = [
    'xs' => 'px-2.5 py-1 text-xs rounded-lg',
    'sm' => 'px-3.5 py-1.5 text-xs rounded-xl shadow-xs',
    'md' => 'px-5 py-2.5 text-sm rounded-xl shadow-xs',
    'lg' => 'px-6 py-3 text-base rounded-2xl shadow-sm',
];

$variants = [
    'primary' => 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white shadow-emerald-600/25 hover:shadow-emerald-600/35 active:scale-[0.98]',
    'secondary' => 'border border-slate-200/90 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm text-slate-800 dark:text-slate-200 hover:border-emerald-500/50 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-slate-50 dark:hover:bg-slate-800/80 active:scale-[0.98]',
    'danger' => 'border border-rose-200 dark:border-rose-900/50 bg-rose-50/50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-300 hover:bg-rose-100/80 dark:hover:bg-rose-900/40 active:scale-[0.98]',
    'ghost' => 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100/80 dark:hover:bg-slate-800/60 active:scale-[0.98]',
];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "$base {$sizes[$size]} {$variants[$variant]}"]) }}>
        @if ($icon) {!! $icon !!} @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "$base {$sizes[$size]} {$variants[$variant]}"]) }}>
        @if ($icon) {!! $icon !!} @endif
        {{ $slot }}
    </button>
@endif
