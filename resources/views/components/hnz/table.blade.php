<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-3xl border border-neutral-200/80 bg-white/80 shadow-sm shadow-neutral-900/5 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/40']) }}>
    <table class="hnz-table w-full text-left text-sm">{{ $slot }}</table>
</div>
