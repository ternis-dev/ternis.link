<div {{ $attributes->merge(['class' => 'rounded-xl border border-dashed border-neutral-300 p-8 text-center dark:border-neutral-700']) }}>
    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ $slot }}</p>
</div>
