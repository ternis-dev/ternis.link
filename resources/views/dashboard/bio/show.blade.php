<x-layouts.dashboard title="Bio Page — {{ $page->title }}">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <a href="{{ route('dashboard.bio') }}" class="text-sm text-neutral-500 hover:underline">&larr; Back to Bio Pages</a>
            <h1 class="mt-2 text-xl font-semibold">{{ $page->title }}</h1>
            <p class="mt-1 font-mono text-sm text-neutral-500">{{ $page->domain?->hostname }}{{ $page->parent_id ? '/' . $page->slug : '' }}</p>
        </div>
        <x-ui.button href="{{ route('dashboard.bio', ['edit' => $page->id]) }}" variant="secondary">Open in page-builder</x-ui.button>
        @if (Route::has('dashboard.bio.build'))
            <x-ui.button href="{{ route('dashboard.bio.build', $page->id) }}" variant="primary">Visual builder</x-ui.button>
        @endif
        <x-ui.button href="{{ route('dashboard.bio.qr', $page->id) }}" variant="ghost">QR code</x-ui.button>
    </div>

    <livewire:bio.page-analytics :page="$page" />
</x-layouts.dashboard>
