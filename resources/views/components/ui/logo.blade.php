@props([
    'size' => 'md',
    'variant' => 'lockup', // lockup | symbol | stacked
    'prefix' => null,
    'href' => '/',
    'class' => '',
])

@php
$sizes = [
    'xs' => ['symbol' => 'h-4 w-4', 'text' => 'text-base', 'gap' => 'gap-1.5'],
    'sm' => ['symbol' => 'h-5 w-5', 'text' => 'text-lg', 'gap' => 'gap-2'],
    'md' => ['symbol' => 'h-6 w-6', 'text' => 'text-xl', 'gap' => 'gap-2.5'],
    'lg' => ['symbol' => 'h-8 w-8', 'text' => 'text-2xl', 'gap' => 'gap-3'],
    'xl' => ['symbol' => 'h-12 w-12', 'text' => 'text-4xl', 'gap' => 'gap-4'],
];
$cfg = $sizes[$size] ?? $sizes['md'];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "inline-flex items-center {$cfg['gap']} font-display font-bold tracking-tight text-neutral-900 transition-opacity hover:opacity-90 dark:text-white {$class}"]) }}>
@else
    <span {{ $attributes->merge(['class' => "inline-flex items-center {$cfg['gap']} font-display font-bold tracking-tight text-neutral-900 dark:text-white {$class}"]) }}>
@endif

    {{-- The TEL Nexus Symbol (Ternis Link) --}}
    <svg class="{{ $cfg['symbol'] }} shrink-0 text-neutral-950 dark:text-white" viewBox="0 0 256 256" fill="currentColor" role="img" aria-hidden="true">
        <path fill-rule="evenodd" d="M 58 46 L 198 46 A 20 20 0 0 1 218 66 A 20 20 0 0 1 198 86 L 128 86 L 128 118 L 158 118 A 16 16 0 0 1 174 134 A 16 16 0 0 1 158 150 L 128 150 L 128 172 L 186 172 A 20 20 0 0 1 206 192 A 20 20 0 0 1 186 212 L 108 212 A 20 20 0 0 1 88 192 L 88 86 L 58 86 A 20 20 0 0 1 38 66 A 20 20 0 0 1 58 46 Z M 58 58 A 8 8 0 1 0 58 74 A 8 8 0 1 0 58 58 Z M 198 58 A 8 8 0 1 0 198 74 A 8 8 0 1 0 198 58 Z M 158 127 A 7 7 0 1 0 158 141 A 7 7 0 1 0 158 127 Z M 186 184 A 8 8 0 1 0 186 200 A 8 8 0 1 0 186 184 Z" />
    </svg>

    @if ($variant !== 'symbol')
        <span class="{{ $cfg['text'] }} leading-none">@if ($prefix){{ $prefix }}<span class="text-neutral-400 dark:text-neutral-500">.ternis.link</span>@else{{ 'ternis' }}<span class="text-neutral-400 dark:text-neutral-500">.link</span>@endif</span>
    @endif

@if ($href)
    </a>
@else
    </span>
@endif
