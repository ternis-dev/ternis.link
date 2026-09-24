{{-- Grayscale data table. Callers keep semantic thead/tbody markup. --}}
<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900']) }}>
    <table class="ui-table">{{ $slot }}</table>
</div>
