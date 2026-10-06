<x-layouts.dashboard title="Bio Page — {{ $page->title }}">
    <x-ui.page-header
        :title="$page->title"
        :backHref="route('dashboard.bio')"
        backLabel="Back to Bio Pages"
    >
        <x-slot:subtitle>
            <span class="font-mono">{{ $page->domain?->hostname }}{{ $page->parent_id ? '/' . $page->slug : '' }}</span>
        </x-slot:subtitle>
        <x-slot:actions>
            <x-ui.button href="{{ route('dashboard.bio', ['edit' => $page->id]) }}" variant="secondary">Open in page-builder</x-ui.button>
            @if (Route::has('dashboard.bio.build'))
                <x-ui.button href="{{ route('dashboard.bio.build', $page->id) }}" variant="primary">Visual builder</x-ui.button>
            @endif
            <x-ui.button href="{{ route('dashboard.bio.qr', $page->id) }}" variant="ghost">QR code</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <livewire:bio.page-analytics :page="$page" />
</x-layouts.dashboard>
