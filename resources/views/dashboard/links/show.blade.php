<x-layouts.dashboard title="Link Analytics — {{ $link->slug }}">
    <x-ui.page-header
        :title="($link->domain->hostname ?? 'href.nz').'/'.$link->slug"
        :backHref="route('dashboard.links')"
        backLabel="Back to Links"
    >
        <x-slot:subtitle>
            Target: <a href="{{ $link->destination_url }}" target="_blank" rel="noopener noreferrer" class="underline underline-offset-2">{{ $link->destination_url }}</a>
            @if (auth()->user()?->isAdmin() && $link->relationLoaded('user'))
                <span class="mt-1 block text-xs">Owner: {{ $link->user?->email ?? 'Guest' }}
                @if ($link->user_id !== auth()->id())
                    (another user's link — admin view)
                @endif
                </span>
            @endif
        </x-slot:subtitle>
        <x-slot:actions>
            <x-ui.button href="{{ route('dashboard.links.edit', $link->id) }}" size="sm">Edit Link</x-ui.button>
            <x-ui.button href="https://{{ $link->domain->hostname ?? 'href.nz' }}/{{ $link->slug }}" size="sm" target="_blank">Visit Link ↗</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <livewire:dashboard.link-analytics :link="$link" />
</x-layouts.dashboard>
