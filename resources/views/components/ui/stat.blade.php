@props(['value' => null, 'label' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'flex items-center gap-4 rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900']) }}>
    <div class="shrink-0 font-display text-3xl font-bold tracking-tight tabular-nums">{{ $value }}</div>
    <div class="min-w-0 border-l border-neutral-200 pl-4 dark:border-neutral-700">
        <div class="text-sm font-medium text-neutral-600 dark:text-neutral-300">{{ $label }}</div>
        @if ($description)<p class="mt-1 text-xs leading-relaxed text-neutral-500 dark:text-neutral-400">{{ $description }}</p>@endif
    </div>
</div>
