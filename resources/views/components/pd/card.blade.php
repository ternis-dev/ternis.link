@props(['title' => null, 'subtitle' => null])

<section {{ $attributes->merge(['class' => 'nd-card relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800/80 dark:bg-[#111720] transition-all']) }}>
    @if ($title || $subtitle || isset($actions))
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4 dark:border-slate-800/60">
            <div>
                @if ($title)
                    <h2 class="font-display text-lg font-bold tracking-tight text-slate-900 dark:text-white">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
                @endif
            </div>
            @if (isset($actions))
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endif
        </div>
    @endif
    {{ $slot }}
</section>
