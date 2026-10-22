@props(['title' => null, 'subtitle' => null, 'elevated' => true])

<section {{ $attributes->merge(['class' => 'hnz-card rounded-2xl border border-neutral-200/80 bg-white p-6 shadow-sm shadow-neutral-900/5 dark:border-white/8 dark:bg-neutral-900 dark:shadow-black/30']) }}>
    @if ($title || $subtitle || isset($actions))
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="font-display text-lg font-bold tracking-tight text-neutral-900 dark:text-white">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ $subtitle }}</p>
                @endif
            </div>
            @if (isset($actions))
                <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
            @endif
        </div>
    @endif
    {{ $slot }}
</section>
