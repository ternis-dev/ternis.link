@props(['tone' => 'info']) {{-- success|error|info --}}

@php
$icons = [
    'success' => '<circle cx="12" cy="12" r="9"/><path d="m8.2 12.3 2.5 2.5 5.1-5.8"/>',
    'error' => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5V13"/><circle cx="12" cy="16.4" r="1.3" fill="currentColor" stroke="none"/>',
    'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5"/><circle cx="12" cy="7.8" r="1.3" fill="currentColor" stroke="none"/>',
];

$tones = [
    'success' => 'border-neutral-900 dark:border-white',
    'error' => 'border-red-600 dark:border-red-400',
    'info' => 'border-neutral-300 dark:border-neutral-700',
];
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-xl border-2 {$tones[$tone]} bg-white px-4 py-3 text-sm dark:bg-neutral-900"]) }} role="{{ $tone === 'error' ? 'alert' : 'status' }}">
    <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$tone] ?? $icons['info'] !!}</svg>
    <div class="min-w-0 flex-1">{{ $slot }}</div>
</div>
