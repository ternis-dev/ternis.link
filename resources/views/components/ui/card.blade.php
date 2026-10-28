@props(['title' => null, 'description' => null])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900']) }}>
    @if ($title || $description || isset($actions))
        <div class="mb-4 flex flex-wrap items-start justify-between gap-2">
            <div>
                @if ($title)
                    <h2 class="font-display text-lg font-semibold tracking-tight">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">{{ $description }}</p>
                @endif
            </div>
            {{ $actions ?? '' }}
        </div>
    @endif
    {{ $slot }}
</section>
