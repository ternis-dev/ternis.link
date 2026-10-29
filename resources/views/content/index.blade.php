<x-layouts.app :title="$meta['title'].' — ternis.link'">
    <x-slot:head>
        <link rel="canonical" href="{{ url('/pages/'.$collection) }}">
        <meta name="description" content="{{ $meta['subtitle'] }}">
        <meta property="og:title" content="{{ $meta['title'] }} — ternis.link">
        <meta property="og:description" content="{{ $meta['subtitle'] }}">
        <meta property="og:url" content="{{ url('/pages/'.$collection) }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="ternis.link">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $meta['title'] }} — ternis.link">
        <meta name="twitter:description" content="{{ $meta['subtitle'] }}">
        <link rel="alternate" type="text/markdown" title="{{ $meta['title'] }} (Markdown)" href="{{ url('/pages/'.$collection.'.md') }}">
    </x-slot:head>

    <div class="mx-auto max-w-5xl" x-data="{ search: '' }">
        {{-- Hero Header --}}
        <header class="py-8 text-center sm:py-12">
            <x-ui.badge tone="solid" class="mb-3">{{ $meta['item'] }} Collection</x-ui.badge>
            <h1 class="font-display text-4xl font-bold tracking-tight sm:text-5xl">{{ $meta['title'] }}</h1>
            <p class="mx-auto mt-3 max-w-xl text-base text-neutral-500 dark:text-neutral-400">{{ $meta['subtitle'] }}</p>

            {{-- Category switcher & Markdown twin --}}
            <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                @foreach ($collections as $key => $item)
                    <a
                        href="{{ url('/pages/'.$key) }}"
                        class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-semibold transition-all {{ $key === $collection ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900 shadow-sm' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800/80 dark:text-neutral-400 dark:hover:bg-neutral-700/80' }}"
                    >
                        {{ $item['title'] }}
                    </a>
                @endforeach
                <a
                    href="{{ url('/pages/'.$collection.'.md') }}"
                    class="inline-flex items-center gap-1 rounded-full border border-neutral-300 px-3 py-1.5 text-xs text-neutral-500 hover:text-neutral-900 hover:border-neutral-400 dark:border-neutral-700 dark:text-neutral-400 dark:hover:text-white transition-colors"
                    title="View as plain Markdown"
                >
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Markdown
                </a>
            </div>

            {{-- Instant Search Filter --}}
            <div class="mx-auto mt-6 max-w-md">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3.5 top-3 h-4 w-4 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input
                        type="text"
                        x-model="search"
                        placeholder="Search {{ strtolower($meta['title']) }}..."
                        class="w-full rounded-xl border border-neutral-200 bg-white py-2 pr-4 pl-10 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-neutral-500 focus:outline-none focus:ring-1 focus:ring-neutral-500 dark:border-neutral-800 dark:bg-neutral-900 dark:text-white dark:placeholder:text-neutral-500"
                    >
                    <button
                        x-show="search.length > 0"
                        x-cloak
                        @click="search = ''"
                        class="absolute right-3 top-2.5 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200"
                        aria-label="Clear search"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </div>
        </header>

        @if (count($entries) > 0)
            @php
                $featured = $entries[0];
                $rest = array_slice($entries, 1);
            @endphp

            {{-- Featured Hero Article (shown when not searching) --}}
            <div x-show="!search" class="mb-10">
                <article class="group relative overflow-hidden rounded-2xl border border-neutral-200 bg-white p-6 sm:p-8 transition-all hover:border-neutral-400 hover:shadow-lg dark:border-neutral-800 dark:bg-neutral-900/80 dark:hover:border-neutral-700">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.badge tone="solid">Latest {{ $meta['item'] }}</x-ui.badge>
                        <span class="text-xs text-neutral-400 dark:text-neutral-500">·</span>
                        <time datetime="{{ $featured['date'] }}" class="text-xs font-medium text-neutral-500 dark:text-neutral-400">{{ $featured['date'] }}</time>
                    </div>

                    <h2 class="font-display mt-3 text-2xl font-bold tracking-tight sm:text-3xl text-neutral-900 dark:text-white">
                        <a href="{{ url('/pages/'.$collection.'/'.$featured['canonical']) }}" class="group-hover:text-neutral-600 dark:group-hover:text-neutral-200 transition-colors">
                            {{ $featured['title'] }}
                        </a>
                    </h2>

                    @if ($featured['description'] !== '')
                        <p class="mt-3 text-base text-neutral-600 dark:text-neutral-300 leading-relaxed max-w-3xl">
                            {{ $featured['description'] }}
                        </p>
                    @endif

                    <div class="mt-6 flex items-center justify-between gap-4">
                        <a href="{{ url('/pages/'.$collection.'/'.$featured['canonical']) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-900 underline underline-offset-4 hover:text-black dark:text-white dark:hover:text-neutral-200">
                            Read {{ strtolower($meta['item']) }} <span aria-hidden="true">&rarr;</span>
                        </a>
                        <span class="text-xs text-neutral-400 dark:text-neutral-500 font-mono">{{ $featured['slug'] }}</span>
                    </div>
                </article>
            </div>

            {{-- Articles Grid --}}
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                @foreach ($entries as $index => $entry)
                    <article
                        x-show="!search || '{{ strtolower(addslashes($entry['title'].' '.$entry['description'].' '.$entry['slug'])) }}'.includes(search.toLowerCase())"
                        class="group flex flex-col justify-between rounded-2xl border border-neutral-200/90 bg-white p-6 transition-all hover:border-neutral-400 hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900/50 dark:hover:border-neutral-700"
                    >
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <time datetime="{{ $entry['date'] }}" class="text-xs font-semibold tracking-wider uppercase text-neutral-500 dark:text-neutral-400">
                                    {{ $entry['date'] }}
                                </time>
                                @if ($index === 0)
                                    <span class="rounded bg-neutral-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">New</span>
                                @endif
                            </div>

                            <h3 class="font-display mt-3 text-lg font-bold tracking-tight text-neutral-900 dark:text-white group-hover:text-neutral-600 dark:group-hover:text-neutral-200 transition-colors">
                                <a href="{{ url('/pages/'.$collection.'/'.$entry['canonical']) }}">
                                    {{ $entry['title'] }}
                                </a>
                            </h3>

                            @if ($entry['description'] !== '')
                                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400 line-clamp-3 leading-relaxed">
                                    {{ $entry['description'] }}
                                </p>
                            @endif
                        </div>

                        <div class="mt-5 flex items-center justify-between border-t border-neutral-100 pt-4 dark:border-neutral-800/80">
                            <a href="{{ url('/pages/'.$collection.'/'.$entry['canonical']) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-900 underline underline-offset-2 hover:text-black dark:text-white dark:hover:text-neutral-200">
                                Read more <span aria-hidden="true">&rarr;</span>
                            </a>
                            <span class="text-[11px] font-mono text-neutral-400 dark:text-neutral-500 truncate max-w-[150px]">{{ $entry['canonical'] }}</span>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Empty search results notice --}}
            <div x-show="search.length > 0" class="mt-6 text-center text-sm text-neutral-500 dark:text-neutral-400" style="display: none;">
                Filtering articles by "<span x-text="search" class="font-semibold text-neutral-900 dark:text-white"></span>"
            </div>
        @else
            <x-ui.empty-state>Nothing here yet.</x-ui.empty-state>
        @endif
    </div>
</x-layouts.app>
