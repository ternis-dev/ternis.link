<x-layouts.app :title="$title.' — ternis.link'">
    <div class="mx-auto max-w-2xl py-12">
        <nav aria-label="Legal" class="mb-6 flex flex-wrap gap-2 text-sm">
            @foreach ($pages as $slug => $label)
                @if ($slug === $current)
                    <span aria-current="page" class="rounded-full bg-neutral-900 px-3 py-1 font-medium text-white dark:bg-white dark:text-neutral-900">{{ $label }}</span>
                @else
                    <a href="{{ url('/legal/'.$slug) }}" class="rounded-full border border-neutral-300 px-3 py-1 text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-white">{{ $label }}</a>
                @endif
            @endforeach
        </nav>

        <article class="legal-prose">
            {!! $html !!}
        </article>
    </div>
</x-layouts.app>
