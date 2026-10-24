@props(['title' => 'No items found', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200/90 py-12 px-4 text-center dark:border-slate-800']) }}>
    <div class="mb-3.5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200/60 dark:bg-emerald-950/40 dark:text-emerald-400 dark:ring-emerald-800/40">
        {{ $icon ?? '' }}
        @empty($icon)
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 14.14 14.14"/></svg>
        @endempty
    </div>
    <h3 class="font-display text-base font-bold text-slate-900 dark:text-white">{{ $title }}</h3>
    @if ($subtitle)
        <p class="mt-1 max-w-sm text-xs text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
    @endif
    @if (isset($actions))
        <div class="mt-5 flex flex-wrap items-center justify-center gap-2">{{ $actions }}</div>
    @endif
</div>
