@props(['value' => null, 'label' => null, 'hint' => null, 'accent' => false])

<div {{ $attributes->merge(['class' => 'hnz-stat group relative overflow-hidden rounded-2xl border border-neutral-200/80 bg-white p-5 shadow-sm shadow-neutral-900/5 transition hover:shadow-md hover:shadow-neutral-900/10 dark:border-white/8 dark:bg-neutral-900 dark:shadow-black/30']) }}>
    @if ($accent)
        <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-violet-600 to-blue-500" aria-hidden="true"></span>
    @endif
    <p class="font-display text-3xl font-bold tracking-tight text-neutral-900 tabular-nums dark:text-white">{{ $value }}</p>
    <p class="mt-1.5 text-[11px] font-bold tracking-[0.1em] text-neutral-400 uppercase dark:text-neutral-500">{{ $label }}</p>
    @if ($hint)
        <p class="mt-2 text-xs text-neutral-500 dark:text-neutral-400">{{ $hint }}</p>
    @endif
</div>
