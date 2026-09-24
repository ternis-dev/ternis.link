@props(['name', 'title' => null])

<div
    x-data="{ open: false }"
    x-on:open-{{ $name }}.window="open = true"
    x-on:close-{{ $name }}.window="open = false"
>
    <div x-show="open" x-cloak class="fixed inset-0 z-50" x-on:keydown.escape.window="open = false">
        <div
            x-show="open"
            x-transition.opacity
            class="fixed inset-0 bg-neutral-950/50"
            x-on:click="open = false"
            aria-hidden="true"
        ></div>

        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    x-show="open"
                    x-transition
                    role="dialog"
                    aria-modal="true"
                    @if ($title) aria-label="{{ $title }}" @endif
                    class="relative w-full max-w-2xl rounded-xl border border-neutral-200 bg-white p-6 shadow-xl dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div class="mb-4 flex items-start justify-between gap-4">
                        @if ($title)
                            <h2 class="font-display text-lg font-semibold tracking-tight">{{ $title }}</h2>
                        @endif
                        <button
                            type="button"
                            x-on:click="open = false"
                            aria-label="Close dialog"
                            class="inline-flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-transparent text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-neutral-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-500 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                        </button>
                    </div>
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>
