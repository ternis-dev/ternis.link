@props(['title' => null, 'subtitle' => null, 'elevated' => true, 'icon' => null])

<section {{ $attributes->merge(['class' => 'hnz-card relative overflow-hidden rounded-3xl border border-neutral-200/80 bg-white/80 p-6 shadow-sm shadow-neutral-900/5 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/40']) }}>
    @if ($title || $subtitle || isset($actions))
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-start gap-3 min-w-0">
                @if ($icon)
                    <div class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-400/10 dark:text-violet-300">
                        {!! $icon !!}
                    </div>
                @endif
                <div class="min-w-0">
                    @if ($title)
                        <h2 class="font-display text-lg font-bold tracking-tight text-neutral-900 dark:text-white">{{ $title }}</h2>
                    @endif
                    @if ($subtitle)
                        <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            @if (isset($actions))
                <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
            @endif
        </div>
    @endif
    {{ $slot }}
</section>
