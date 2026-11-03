@props(['value' => null, 'label' => null, 'hint' => null, 'accent' => false, 'trend' => null, 'icon' => null])

<div {{ $attributes->merge(['class' => 'hnz-stat group relative overflow-hidden rounded-3xl border border-neutral-200/80 bg-white/80 p-5 shadow-sm shadow-neutral-900/5 backdrop-blur-xl transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:shadow-violet-500/10 dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/40']) }}>
    @if ($accent)
        <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-violet-600 via-indigo-600 to-sky-500" aria-hidden="true"></span>
    @endif
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-[11px] font-bold tracking-[0.14em] text-neutral-400 uppercase dark:text-neutral-500">{{ $label }}</p>
            <p class="mt-2 font-display text-3xl font-bold tracking-tight text-neutral-900 tabular-nums dark:text-white">{{ $value }}</p>
        </div>
        @if ($trend)
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m18 15-6-6-6 6"/></svg>
                {{ $trend }}
            </span>
        @elseif ($icon)
            <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-400/10 dark:text-violet-300">
                {!! $icon !!}
            </div>
        @endif
    </div>
    @if ($hint)
        <p class="mt-2 text-xs text-neutral-500 dark:text-neutral-400">{{ $hint }}</p>
    @endif
</div>
