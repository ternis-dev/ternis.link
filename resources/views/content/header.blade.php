@props(['collection', 'meta', 'entry' => null, 'collections' => null])

@php
    $collections = $collections ?? \App\Support\ContentCollection::COLLECTIONS;
    $isArticle = $entry !== null;
@endphp

<nav aria-label="Content header navigation" class="mb-6 rounded-2xl border border-neutral-200/80 bg-white/70 p-3 backdrop-blur-md dark:border-neutral-800/80 dark:bg-neutral-900/70 sm:p-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        {{-- Breadcrumb trail & Back link --}}
        <div class="flex flex-wrap items-center gap-2 text-xs text-neutral-500 dark:text-neutral-400">
            <a href="{{ url('/pages/'.$collection) }}" class="inline-flex items-center gap-1 font-semibold text-neutral-700 hover:text-neutral-900 dark:text-neutral-300 dark:hover:text-white transition-colors">
                ← {{ $meta['title'] }}
            </a>
            <span class="text-neutral-300 dark:text-neutral-700">|</span>
            <a href="{{ url('/') }}" class="font-medium hover:text-neutral-900 dark:hover:text-white transition-colors">ternis.link</a>
            @if ($isArticle)
                <svg class="h-3 w-3 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                <span class="max-w-[200px] truncate font-semibold text-neutral-900 dark:text-white sm:max-w-[320px]">
                    {{ $entry['title'] }}
                </span>
            @endif
        </div>

        {{-- Right actions: Collection switcher & feeds --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Collection tabs --}}
            <div class="inline-flex rounded-lg border border-neutral-200 bg-neutral-100/80 p-0.5 dark:border-neutral-800 dark:bg-neutral-800/60">
                @foreach ($collections as $key => $item)
                    <a
                        href="{{ url('/pages/'.$key) }}"
                        class="rounded-md px-2.5 py-1 text-xs font-semibold transition-all {{ $key === $collection ? 'bg-white text-neutral-900 shadow-xs dark:bg-neutral-700 dark:text-white' : 'text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white' }}"
                    >
                        {{ $item['title'] }}
                    </a>
                @endforeach
            </div>

            {{-- Feed & export buttons --}}
            <div class="flex items-center gap-1.5">
                @if ($isArticle)
                    <button
                        type="button"
                        @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)"
                        class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-neutral-300 bg-white px-2.5 py-1 text-xs font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700"
                        title="Copy article link"
                    >
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                        <span x-text="copied ? 'Copied' : 'Share'"></span>
                    </button>
                    <a
                        href="{{ url('/pages/'.$collection.'/'.$entry['canonical'].'.md') }}"
                        class="inline-flex items-center gap-1 rounded-lg border border-neutral-300 bg-white px-2.5 py-1 text-xs font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700"
                        title="View plain Markdown source"
                    >
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        Markdown
                    </a>
                @else
                    <a
                        href="{{ url('/pages/'.$collection.'.md') }}"
                        class="inline-flex items-center gap-1 rounded-lg border border-neutral-300 bg-white px-2.5 py-1 text-xs font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700"
                        title="View collection as Markdown"
                    >
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        Markdown
                    </a>
                @endif

                <a
                    href="{{ url('/pages/'.$collection.'.xml') }}"
                    class="inline-flex items-center gap-1 rounded-lg border border-neutral-300 bg-white px-2.5 py-1 text-xs font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700"
                    title="RSS Feed"
                >
                    <svg class="h-3 w-3 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/></svg>
                    RSS
                </a>
            </div>
        </div>
    </div>
</nav>
