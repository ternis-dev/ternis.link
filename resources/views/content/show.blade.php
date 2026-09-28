<x-layouts.app :title="$entry['title'].' — '.$meta['title'].' — ternis.link'">
    <x-slot:head>
        <link rel="alternate" type="text/markdown" title="{{ $entry['title'] }} (Markdown)" href="{{ url('/pages/'.$collection.'/'.$entry['canonical'].'.md') }}">
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $entry['title'],
            'description' => $entry['description'],
            'datePublished' => $entry['date'],
            'author' => ['@type' => 'Organization', 'name' => 'ternis-dev', 'url' => 'https://ternis.dev'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    </x-slot:head>
    <div class="mx-auto max-w-2xl py-4">
        <p class="mb-4 text-sm">
            <a href="{{ url('/pages/'.$collection) }}" class="text-neutral-500 underline underline-offset-4 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">← {{ $meta['title'] }}</a>
        </p>
        <p class="text-xs tracking-wide text-neutral-500 uppercase dark:text-neutral-500">{{ $entry['date'] }} · {{ $readingTime }} min read</p>
        <h1 class="font-display mt-1 text-3xl font-bold tracking-tight">{{ $entry['title'] }}</h1>
        @if ($entry['description'] !== '')
            <p class="mt-3 text-lg text-neutral-500 dark:text-neutral-400">{{ $entry['description'] }}</p>
        @endif

        <article class="legal-prose mt-6">
            {!! $html !!}
        </article>

        @if ($newer || $older)
            <nav aria-label="More {{ strtolower($meta['title']) }}" class="mt-10 grid grid-cols-1 gap-4 border-t border-neutral-200 pt-6 sm:grid-cols-2 dark:border-neutral-800">
                <div>
                    @if ($older)
                        <p class="text-xs tracking-wide text-neutral-500 uppercase dark:text-neutral-500">Older</p>
                        <a href="{{ url('/pages/'.$collection.'/'.$older['canonical']) }}" class="font-display mt-1 inline-block font-semibold underline-offset-4 hover:underline">← {{ $older['title'] }}</a>
                    @endif
                </div>
                <div class="sm:text-right">
                    @if ($newer)
                        <p class="text-xs tracking-wide text-neutral-500 uppercase dark:text-neutral-500">Newer</p>
                        <a href="{{ url('/pages/'.$collection.'/'.$newer['canonical']) }}" class="font-display mt-1 inline-block font-semibold underline-offset-4 hover:underline">{{ $newer['title'] }} →</a>
                    @endif
                </div>
            </nav>
        @endif
    </div>
</x-layouts.app>
