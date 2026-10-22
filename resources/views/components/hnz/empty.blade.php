@props(['title' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-neutral-300 bg-neutral-50/60 px-6 py-12 text-center dark:border-white/10 dark:bg-white/[0.02]']) }}>
    @if ($title)
        <p class="text-sm font-semibold text-neutral-700 dark:text-neutral-200">{{ $title }}</p>
    @endif
    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ $slot }}</p>
</div>
