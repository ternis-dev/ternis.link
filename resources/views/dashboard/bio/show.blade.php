<x-layouts.dashboard title="Bio Page — {{ $page->title }}">
    <div class="mb-6">
        <a href="{{ route('dashboard.bio') }}" class="text-sm text-neutral-500 hover:underline">&larr; Back to Bio Pages</a>
        <h1 class="mt-2 text-xl font-semibold">{{ $page->title }}</h1>
        <p class="mt-1 font-mono text-sm text-neutral-500">{{ $page->domain?->hostname }}{{ $page->parent_id ? '/' . $page->slug : '' }}</p>
    </div>

    <livewire:bio.page-analytics :page="$page" />
</x-layouts.dashboard>
