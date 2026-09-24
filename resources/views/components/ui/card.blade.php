@props(['title' => null])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900']) }}>
    @if ($title)
        <h2 class="mb-4 font-display text-lg font-semibold tracking-tight">{{ $title }}</h2>
    @endif
    {{ $slot }}
</section>
