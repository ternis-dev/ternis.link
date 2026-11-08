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
        <link rel="alternate" type="application/rss+xml" title="{{ $meta['title'] }} (RSS)" href="{{ url('/pages/'.$collection.'.xml') }}">
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
        @include('content.header', ['collection' => $collection, 'meta' => $meta, 'entry' => $entry, 'collections' => $collections ?? null])

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
