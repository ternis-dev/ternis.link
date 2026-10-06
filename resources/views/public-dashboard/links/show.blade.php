<x-layouts.public-dashboard title="Link Analytics — {{ $link->slug }}">
    <x-pd.head
        :title="($link->domain->hostname ?? 'href.nz').'/'.$link->slug"
        :backHref="$backHref ?? route('public-dashboard.links')"
        :backLabel="$backLabel ?? 'Back to Links'"
    >
        <x-slot:subtitle>
            Target: <a href="{{ $link->destination_url }}" target="_blank" rel="noopener noreferrer" class="underline underline-offset-2">{{ $link->destination_url }}</a>
            @if ($link->description)
                <span class="mt-1 block text-xs">{{ $link->description }}</span>
            @endif
            @if (! empty($link->tags))
                <span class="mt-1.5 flex flex-wrap gap-1.5">
                    @foreach ($link->tags as $tag)
                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">{{ $tag }}</span>
                    @endforeach
                </span>
            @endif
        </x-slot:subtitle>
        <x-slot:actions>
            <x-pd.button href="{{ route('public-dashboard.links.edit', $link->id) }}" variant="secondary" size="sm">Edit link</x-pd.button>
            <form method="POST" action="{{ route('public-dashboard.links.duplicate', $link->id) }}" class="inline">
                @csrf
                <x-pd.button type="submit" variant="secondary" size="sm">Duplicate</x-pd.button>
            </form>
            <x-pd.button href="https://{{ $link->domain->hostname ?? 'href.nz' }}/{{ $link->slug }}" variant="secondary" size="sm" target="_blank">Visit link ↗</x-pd.button>
            <x-pd.button href="{{ route('public-dashboard.links.qr', $link->id) }}" variant="primary" size="sm">Download QR</x-pd.button>
        </x-slot:actions>
    </x-pd.head>

    <x-pd.card title="QR Code" class="mb-6">
        <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center">
            <img src="{{ $qrSvg }}" alt="QR code for {{ ($link->domain->hostname ?? 'href.nz').'/'.$link->slug }}" class="h-40 w-40 rounded-lg border border-neutral-200 bg-white p-2 dark:border-neutral-700">
            <div>
                <p class="text-sm font-medium">Scan to open <code class="rounded bg-emerald-50 px-1 py-0.5 text-xs text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">https://{{ ($link->domain->hostname ?? 'href.nz').'/'.$link->slug }}</code></p>
                <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">PNG download, 600×600 — print-ready for flyers and stickers.</p>
                <div class="mt-3 flex gap-2">
                    <x-pd.button href="{{ route('public-dashboard.links.qr', $link->id) }}" size="sm" variant="primary">Download PNG</x-pd.button>
                    <x-pd.button size="sm" variant="secondary" data-copy="{{ $link->short_url }}" title="Copy short link">Copy link</x-pd.button>
                </div>
            </div>
        </div>
    </x-pd.card>

    <livewire:dashboard.link-analytics :link="$link" theme="public" />
</x-layouts.public-dashboard>
