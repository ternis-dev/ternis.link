<x-layouts.hnz-dashboard title="Link Analytics — {{ $link->slug }}">
    <x-hnz.head
        :title="($link->domain->hostname ?? 'href.nz').'/'.$link->slug"
        :backHref="$backHref ?? \App\Support\PublicDashboard::route('links')"
        :backLabel="$backLabel ?? 'Back to Links'"
    >
        <x-slot:subtitle>
            Destination: <a href="{{ $link->destination_url }}" target="_blank" rel="noopener noreferrer" class="font-medium text-violet-600 hover:underline dark:text-violet-400">{{ $link->destination_url }}</a>
            @if ($link->description)
                <span class="mt-1 block text-xs text-neutral-500 dark:text-neutral-400">{{ $link->description }}</span>
            @endif
            @if (! empty($link->tags))
                <span class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($link->tags as $tag)
                        <span class="inline-flex items-center rounded-lg bg-violet-50 px-2 py-0.5 text-[11px] font-semibold text-violet-700 dark:bg-violet-400/10 dark:text-violet-300">{{ $tag }}</span>
                    @endforeach
                </span>
            @endif
        </x-slot:subtitle>
        <x-slot:actions>
            <x-hnz.button href="{{ \App\Support\PublicDashboard::route('links.edit', $link->id) }}" variant="secondary" size="sm">Edit link</x-hnz.button>
            <form method="POST" action="{{ \App\Support\PublicDashboard::route('links.duplicate', $link->id) }}" class="inline">
                @csrf
                <x-hnz.button type="submit" variant="secondary" size="sm">Duplicate</x-hnz.button>
            </form>
            <x-hnz.button href="https://{{ $link->domain->hostname ?? 'href.nz' }}/{{ $link->slug }}" variant="secondary" size="sm" target="_blank">Visit link</x-hnz.button>
            <x-hnz.button href="{{ \App\Support\PublicDashboard::route('links.qr', $link->id) }}" variant="primary" size="sm">Download QR</x-hnz.button>
        </x-slot:actions>
    </x-hnz.head>

    <x-hnz.card title="QR Code" subtitle="Print-ready vector code for physical media and packaging." class="mb-6">
        <div class="flex flex-col items-start gap-6 sm:flex-row sm:items-center">
            <div class="relative rounded-2xl border border-neutral-200/80 bg-white p-3 shadow-md dark:border-white/10 dark:bg-white">
                <img src="{{ $qrSvg }}" alt="QR code for {{ ($link->domain->hostname ?? 'href.nz').'/'.$link->slug }}" class="h-40 w-40">
            </div>
            <div class="space-y-2">
                <p class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                    Scan to open <code class="rounded-lg bg-violet-50 px-2 py-1 text-xs font-mono font-bold text-violet-700 dark:bg-violet-400/15 dark:text-violet-300">https://{{ ($link->domain->hostname ?? 'href.nz').'/'.$link->slug }}</code>
                </p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Crisp high-resolution 600×600 PNG download — optimized for stickers, flyers, and business cards.</p>
                <div class="pt-2 flex flex-wrap gap-2.5">
                    <x-hnz.button href="{{ \App\Support\PublicDashboard::route('links.qr', $link->id) }}" size="sm" variant="primary">Download PNG</x-hnz.button>
                    <x-hnz.button size="sm" variant="secondary" data-copy="{{ $link->short_url }}" title="Copy short link">Copy link</x-hnz.button>
                </div>
            </div>
        </div>
    </x-hnz.card>

    <livewire:dashboard.link-analytics :link="$link" theme="public" />
</x-layouts.hnz-dashboard>
