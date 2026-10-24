@props(['tone' => 'info'])

@php
$tones = [
    'info' => 'border-emerald-200 bg-emerald-50/70 text-emerald-900 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-200',
    'success' => 'border-teal-200 bg-teal-50/70 text-teal-900 dark:border-teal-900/60 dark:bg-teal-950/30 dark:text-teal-200',
    'warning' => 'border-amber-200 bg-amber-50/70 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200',
    'error' => 'border-rose-200 bg-rose-50/70 text-rose-900 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-200',
];
@endphp

<div {{ $attributes->merge(['class' => 'w-full rounded-xl border p-4 text-sm leading-relaxed ' . ($tones[$tone] ?? $tones['info'])]) }} role="alert">
    {{ $slot }}
</div>
