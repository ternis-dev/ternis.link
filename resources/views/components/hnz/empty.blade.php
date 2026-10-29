@props(['title' => null])

<div {{ $attributes->merge(['class' => 'rounded-3xl border border-dashed border-neutral-300/80 bg-neutral-50/50 px-6 py-12 text-center backdrop-blur-sm dark:border-white/10 dark:bg-white/[0.02]']) }}>
    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 dark:bg-violet-400/10 dark:text-violet-400">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M10 13a5 5 0 0 0 7.54.54l2.1-2.1a5 5 0 0 0-7.07-7.07l-1.06 1.06M14 11a5 5 0 0 0-7.54-.54l-2.1 2.1a5 5 0 0 0 7.07 7.07l1.06-1.06"/></svg>
    </div>
    @if ($title)
        <p class="text-base font-bold text-neutral-800 dark:text-neutral-200">{{ $title }}</p>
    @endif
    <p class="mx-auto mt-1 max-w-sm text-xs text-neutral-500 dark:text-neutral-400">{{ $slot }}</p>
</div>
