@props(['tone' => 'neutral']) {{-- neutral|solid --}}

@php
$tones = [
    'neutral' => 'border-neutral-300 text-neutral-600 dark:border-neutral-700 dark:text-neutral-400',
    'solid' => 'border-neutral-900 bg-neutral-900 text-white dark:border-white dark:bg-white dark:text-neutral-900',
];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold tracking-wide uppercase {$tones[$tone]}"]) }}>{{ $slot }}</span>
