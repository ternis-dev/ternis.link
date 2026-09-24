@props(['tone' => 'info']) {{-- success|error|info --}}

@php
$tones = [
    'success' => ['border-neutral-900 dark:border-white', '✓'],
    'error' => ['border-neutral-900 dark:border-white', '!'],
    'info' => ['border-neutral-300 dark:border-neutral-700', 'i'],
];
[$border, $glyph] = $tones[$tone];
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-lg border-2 {$border} bg-white px-4 py-3 text-sm dark:bg-neutral-900"]) }} role="{{ $tone === 'error' ? 'alert' : 'status' }}">
    <span aria-hidden="true" class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-neutral-900 text-[11px] font-bold text-white dark:bg-white dark:text-neutral-900">{{ $glyph }}</span>
    <div class="min-w-0">{{ $slot }}</div>
</div>
