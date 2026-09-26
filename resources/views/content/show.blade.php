<x-layouts.app :title="$entry['title'].' — '.$meta['title'].' — ternis.link'">
    <x-slot:head>
        <link rel="alternate" type="text/markdown" title="{{ $entry['title'] }} (Markdown)" href="{{ url('/pages/'.$collection.'/'.$entry['slug'].'.md') }}">
    </x-slot:head>
    <div class="mx-auto max-w-2xl py-4">
        <p class="mb-4 text-sm">
            <a href="{{ url('/pages/'.$collection) }}" class="text-neutral-500 underline underline-offset-4 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">← {{ $meta['title'] }}</a>
        </p>
        <p class="text-xs tracking-wide text-neutral-500 uppercase dark:text-neutral-500">{{ $entry['date'] }}</p>
        <h1 class="font-display mt-1 text-3xl font-bold tracking-tight">{{ $entry['title'] }}</h1>

        <article class="legal-prose mt-6">
            {!! $html !!}
        </article>
    </div>
</x-layouts.app>
