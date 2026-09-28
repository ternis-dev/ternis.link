<x-layouts.app :title="$meta['title'].' — ternis.link'">
    <x-slot:head>
        <link rel="alternate" type="text/markdown" title="{{ $meta['title'] }} (Markdown)" href="{{ url('/pages/'.$collection.'.md') }}">
    </x-slot:head>
    <x-ui.page-header
        :title="$meta['title']"
        :subtitle="$meta['subtitle']"
    >
        <x-slot:actions>
            @foreach ($collections as $key => $item)
                @if ($key !== $collection)
                    <x-ui.button href="{{ url('/pages/'.$key) }}" size="sm">{{ $item['title'] }}</x-ui.button>
                @endif
            @endforeach
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mx-auto max-w-2xl">
        @forelse ($entries as $entry)
            <article class="mb-6 border-b border-neutral-200 pb-6 last:border-0 dark:border-neutral-800">
                <p class="text-xs tracking-wide text-neutral-500 uppercase dark:text-neutral-500">{{ $entry['date'] }}</p>
                <h2 class="font-display mt-1 text-xl font-semibold tracking-tight">
                    <a href="{{ url('/pages/'.$collection.'/'.$entry['canonical']) }}" class="underline-offset-4 hover:underline">{{ $entry['title'] }}</a>
                </h2>
                @if ($entry['description'] !== '')
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ $entry['description'] }}</p>
                @endif
            </article>
        @empty
            <x-ui.empty-state>Nothing here yet.</x-ui.empty-state>
        @endforelse
    </div>
</x-layouts.app>
