<x-layouts.app :title="$entry['title'].' — '.$meta['title'].' — ternis.link'">
    <x-slot:head>
        <link rel="canonical" href="{{ url('/pages/'.$collection.'/'.$entry['canonical']) }}">
        @if ($entry['description'] !== '')
            <meta name="description" content="{{ $entry['description'] }}">
            <meta property="og:description" content="{{ $entry['description'] }}">
            <meta name="twitter:description" content="{{ $entry['description'] }}">
        @endif
        <meta property="og:title" content="{{ $entry['title'] }}">
        <meta property="og:type" content="article">
        <meta property="og:url" content="{{ url('/pages/'.$collection.'/'.$entry['canonical']) }}">
        <meta property="og:site_name" content="ternis.link">
        <meta property="article:published_time" content="{{ $entry['date'] }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $entry['title'] }}">
        <link rel="alternate" type="text/markdown" title="{{ $entry['title'] }} (Markdown)" href="{{ url('/pages/'.$collection.'/'.$entry['canonical'].'.md') }}">
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $entry['title'],
            'description' => $entry['description'],
            'datePublished' => $entry['date'],
            'dateModified' => $entry['date'],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => url('/pages/'.$collection.'/'.$entry['canonical'])],
            'url' => url('/pages/'.$collection.'/'.$entry['canonical']),
            'wordCount' => str_word_count(strip_tags($entry['body'])),
            'author' => ['@type' => 'Organization', 'name' => 'ternis-dev', 'url' => 'https://ternis.dev'],
            'publisher' => ['@type' => 'Organization', 'name' => 'ternis.link', 'url' => 'https://ternis.link'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    </x-slot:head>

    <div class="mx-auto max-w-4xl py-6" x-data="{ copied: false }">
        {{-- Navigation & Meta bar --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 pb-4 dark:border-neutral-800">
            <a href="{{ url('/pages/'.$collection) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white transition-colors">
                ← {{ $meta['title'] }}
            </a>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-neutral-300 px-3 py-1.5 text-xs font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-900 dark:hover:text-white"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    <span x-text="copied ? 'Copied URL!' : 'Share'"></span>
                </button>

                <a
                    href="{{ url('/pages/'.$collection.'/'.$entry['canonical'].'.md') }}"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-300 px-3 py-1.5 text-xs font-medium text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-900 dark:hover:text-white"
                    title="View original Markdown file"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Markdown
                </a>
            </div>
        </div>

        {{-- Article Header --}}
        <header class="pt-8 pb-6">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded bg-neutral-100 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">
                    {{ $meta['item'] }}
                </span>
                <span class="text-xs text-neutral-400 dark:text-neutral-500">·</span>
                <time datetime="{{ $entry['date'] }}" class="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                    {{ $entry['date'] }}
                </time>
                <span class="text-xs text-neutral-400 dark:text-neutral-500">·</span>
                <span class="text-xs text-neutral-500 dark:text-neutral-400">
                    {{ $readingTime }} min read
                </span>
            </div>

            <h1 class="font-display mt-3 text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl lg:text-5xl dark:text-white">
                {{ $entry['title'] }}
            </h1>

            @if ($entry['description'] !== '')
                <p class="mt-4 text-lg text-neutral-600 dark:text-neutral-300 leading-relaxed">
                    {{ $entry['description'] }}
                </p>
            @endif
        </header>

        {{-- Article Body --}}
        <article class="legal-prose border-t border-neutral-100 pt-8 dark:border-neutral-800">
            {!! $html !!}
        </article>

        {{-- Previous / Next Navigation --}}
        @if ($newer || $older)
            <nav aria-label="More {{ strtolower($meta['title']) }}" class="mt-14 grid grid-cols-1 gap-4 border-t border-neutral-200 pt-8 sm:grid-cols-2 dark:border-neutral-800">
                <div>
                    @if ($older)
                        <div class="rounded-xl border border-neutral-200/90 bg-white p-4 transition-all hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-900/50">
                            <p class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">← Older</p>
                            <a href="{{ url('/pages/'.$collection.'/'.$older['canonical']) }}" class="font-display mt-1 inline-block font-semibold text-neutral-900 hover:underline dark:text-white">
                                {{ $older['title'] }}
                            </a>
                        </div>
                    @endif
                </div>
                <div class="sm:text-right">
                    @if ($newer)
                        <div class="rounded-xl border border-neutral-200/90 bg-white p-4 transition-all hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-900/50">
                            <p class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Newer →</p>
                            <a href="{{ url('/pages/'.$collection.'/'.$newer['canonical']) }}" class="font-display mt-1 inline-block font-semibold text-neutral-900 hover:underline dark:text-white">
                                {{ $newer['title'] }}
                            </a>
                        </div>
                    @endif
                </div>
            </nav>
        @endif
    </div>
</x-layouts.app>
