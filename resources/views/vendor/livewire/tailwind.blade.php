@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';

$pageName = $paginator->getPageName();
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination" class="ui-pagination mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                Showing
                <span class="font-semibold text-neutral-700 dark:text-neutral-300">{{ $paginator->firstItem() }}</span>
                to
                <span class="font-semibold text-neutral-700 dark:text-neutral-300">{{ $paginator->lastItem() }}</span>
                of
                <span class="font-semibold text-neutral-700 dark:text-neutral-300">{{ $paginator->total() }}</span>
                results
            </p>

            <div class="flex flex-wrap items-center gap-1">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="Previous page" class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg border border-neutral-200 text-neutral-300 dark:border-neutral-800 dark:text-neutral-700" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 6-6 6 6 6"/></svg>
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $pageName }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $pageName == 'page' ? '' : '.' . $pageName }}" aria-label="Previous page" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-neutral-300 text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m14.5 6-6 6 6 6"/></svg>
                    </button>
                @endif

                {{-- Page numbers --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-hidden="true" class="inline-flex h-8 min-w-8 items-center justify-center px-1 text-sm text-neutral-400 dark:text-neutral-600">…</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <span wire:key="paginator-{{ $pageName }}-page{{ $page }}">
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-neutral-900 bg-neutral-900 px-2 text-sm font-semibold text-white dark:border-white dark:bg-white dark:text-neutral-900">{{ $page }}</span>
                                @else
                                    <button type="button" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" aria-label="Go to page {{ $page }}" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-neutral-300 px-2 text-sm text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-500 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">
                                        {{ $page }}
                                    </button>
                                @endif
                            </span>
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $pageName }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $pageName == 'page' ? '' : '.' . $pageName }}" aria-label="Next page" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-neutral-300 text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9.5 6 6 6-6 6"/></svg>
                    </button>
                @else
                    <span aria-disabled="true" aria-label="Next page" class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg border border-neutral-200 text-neutral-300 dark:border-neutral-800 dark:text-neutral-700" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9.5 6 6 6-6 6"/></svg>
                    </span>
                @endif
            </div>
        </nav>
    @endif
</div>
