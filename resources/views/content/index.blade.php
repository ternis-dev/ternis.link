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
        @forelse ($entries as $index => $entry)
            @if ($index === 0)
                <a href="{{ url('/pages/'.$collection.'/'.$entry['canonical']) }}" class="mb-6 block rounded-xl border border-neutral-200 bg-white p-6 transition-colors hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-600">
                    <p class="flex items-center gap-2 text-xs font-semibold tracking-wide uppercase">
                        <span class="rounded-full bg-neutral-900 px-2 py-0.5 text-white dark:bg-white dark:text-neutral-900">Latest</span>
                        <span class="text-neutral-500 dark:text-neutral-500">{{ $entry['date'] }}</span>
                    </p>
                    <p class="font-display mt-2 text-2xl font-bold tracking-tight">{{ $entry['title'] }}</p>
                    @if ($entry['description'] !== '')
                        <p class="mt-2 text-neutral-500 dark:text-neutral-400">{{ $entry['description'] }}</p>
                    @endif
                    <p class="mt-3 text-sm font-semibold">Read →</p>
                </a>
            @else
                <article class="mb-4 rounded-xl border border-neutral-200 bg-white p-5 transition-colors hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-600">
                    <p class="text-xs tracking-wide text-neutral-500 uppercase dark:text-neutral-500">{{ $entry['date'] }}</p>
                    <h2 class="font-display mt-1 text-xl font-semibold tracking-tight">
                        <a href="{{ url('/pages/'.$collection.'/'.$entry['canonical']) }}" class="underline-offset-4 hover:underline">{{ $entry['title'] }}</a>
                    </h2>
                    @if ($entry['description'] !== '')
                        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ $entry['description'] }}</p>
                    @endif
                </article>
            @endif
        @empty
            <x-ui.empty-state>Nothing here yet.</x-ui.empty-state>
        @endforelse
    </div>
</x-layouts.app>
