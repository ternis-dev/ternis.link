@props(['state' => 'neutral']) {{-- active|verified|pending|expired|disabled|system --}}

@php
$labels = [
    'active' => 'Active',
    'verified' => 'Verified',
    'pending' => 'Pending DNS',
    'expired' => 'Expired',
    'disabled' => 'Disabled',
    'system' => 'System',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-xs font-semibold whitespace-nowrap']) }}>
    @if (in_array($state, ['active', 'verified'], true))
        <span aria-hidden="true" class="h-1.5 w-1.5 rounded-full bg-neutral-900 dark:bg-white"></span>
        <span>{{ $labels[$state] }}</span>
    @elseif ($state === 'pending')
        <span aria-hidden="true" class="h-1.5 w-1.5 rounded-full border border-neutral-500"></span>
        <span class="text-neutral-600 dark:text-neutral-400">{{ $labels[$state] }}</span>
    @else
        <span class="font-medium text-neutral-500 dark:text-neutral-500">{{ $labels[$state] ?? $state }}</span>
    @endif
</span>
