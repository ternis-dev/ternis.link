@props(['text' => '', 'label' => 'Copy', 'size' => 'sm'])

<button
    type="button"
    x-data="{
        copied: false,
        copy() {
            navigator.clipboard.writeText('{{ $text }}').then(() => {
                this.copied = true;
                $dispatch('hnz-toast', { message: 'Copied link to clipboard!' });
                setTimeout(() => this.copied = false, 2000);
            });
        }
    }"
    @click="copy()"
    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-neutral-200 bg-white px-2.5 py-1 text-xs font-semibold text-neutral-700 transition hover:border-violet-300 hover:text-violet-700 active:scale-95 dark:border-white/10 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:border-violet-400"
    :class="copied ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300' : ''"
    title="Copy to clipboard"
>
    <svg x-show="!copied" class="h-3.5 w-3.5 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
    <svg x-show="copied" x-cloak class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    <span x-text="copied ? 'Copied!' : '{{ $label }}'"></span>
</button>
