@props(['tone' => 'info', 'title' => null])

@php
    $tones = [
        'info' => 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-400/20 dark:bg-sky-500/10 dark:text-sky-300',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-300',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-300',
        'danger' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-300',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'hnz-alert rounded-2xl border px-5 py-4 text-sm '.($tones[$tone] ?? $tones['info'])]) }}>
    @if ($title)
        <p class="font-bold">{{ $title }}</p>
    @endif
    <div class="{{ $title ? 'mt-1 text-xs opacity-90' : 'text-xs' }}">{{ $slot }}</div>
</div>
