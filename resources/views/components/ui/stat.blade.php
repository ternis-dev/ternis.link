@props(['value' => null, 'label' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900']) }}>
    <div class="font-display text-3xl font-bold tracking-tight">{{ $value }}</div>
    <div class="mt-1 text-xs font-semibold tracking-widest text-neutral-500 uppercase dark:text-neutral-400">{{ $label }}</div>
</div>
