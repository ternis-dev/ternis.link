{{-- my.href.nz data table: elevated wrapper, same semantics as x-ui.table. --}}
<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-2xl border border-neutral-200/80 bg-white shadow-sm shadow-neutral-900/5 dark:border-white/8 dark:bg-neutral-900 dark:shadow-black/30']) }}>
    <table class="hnz-table w-full text-left text-sm">{{ $slot }}</table>
</div>
