<x-layouts.public-dashboard title="Link Analytics — {{ $link->slug }}">
    <x-pd.head
        :title="($link->domain->hostname ?? 'href.nz').'/'.$link->slug"
        :backHref="$backHref ?? \App\Support\PublicDashboard::route('links')"
        :backLabel="$backLabel ?? 'Back to Links'"
    >
        <x-slot:subtitle>
            <div class="flex flex-col gap-1.5">
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    Destination: <a href="{{ $link->destination_url }}" target="_blank" rel="noopener noreferrer" class="font-mono font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-400 underline underline-offset-2 break-all">{{ $link->destination_url }}</a>
                </div>
                @if ($link->description)
                    <p class="text-xs text-slate-600 dark:text-slate-300 font-medium">{{ $link->description }}</p>
                @endif
                @if (! empty($link->tags))
                    <div class="flex flex-wrap gap-1.5 pt-0.5">
                        @foreach ($link->tags as $tag)
                            <span class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-500/20">{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-slot:subtitle>
        <x-slot:actions>
            <x-pd.button href="{{ \App\Support\PublicDashboard::route('links.edit', $link->id) }}" variant="secondary" size="sm">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                Edit link
            </x-pd.button>
            <form method="POST" action="{{ \App\Support\PublicDashboard::route('links.duplicate', $link->id) }}" class="inline">
                @csrf
                <x-pd.button type="submit" variant="secondary" size="sm">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    Duplicate
                </x-pd.button>
            </form>
            <x-pd.button href="https://{{ $link->domain->hostname ?? 'href.nz' }}/{{ $link->slug }}" variant="secondary" size="sm" target="_blank">
                Visit link ↗
            </x-pd.button>
            <x-pd.button href="{{ \App\Support\PublicDashboard::route('links.qr', $link->id) }}" variant="primary" size="sm">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect width="6" height="6" x="3" y="3" rx="1"/><rect width="6" height="6" x="15" y="3" rx="1"/><rect width="6" height="6" x="3" y="15" rx="1"/><path d="M15 15h6v6h-6z"/></svg>
                Download QR
            </x-pd.button>
        </x-slot:actions>
    </x-pd.head>

    <x-pd.card title="QR Code" class="mb-8">
        <div class="flex flex-col items-start gap-6 sm:flex-row sm:items-center">
            <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <img src="{{ $qrSvg }}" alt="QR code for {{ ($link->domain->hostname ?? 'href.nz').'/'.$link->slug }}" class="h-36 w-36 rounded-xl">
            </div>
            <div class="space-y-2">
                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                    Scan to open <code class="rounded-md bg-emerald-500/10 px-2 py-1 font-mono text-xs font-bold text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-500/20">https://{{ ($link->domain->hostname ?? 'href.nz').'/'.$link->slug }}</code>
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400">High-resolution PNG download (600×600) — print-ready for packaging, flyers, and merchandise.</p>
                <div class="flex flex-wrap items-center gap-2 pt-2">
                    <x-pd.button href="{{ \App\Support\PublicDashboard::route('links.qr', $link->id) }}" size="sm" variant="primary">Download PNG</x-pd.button>
                    <x-pd.button size="sm" variant="secondary" data-copy="{{ $link->short_url }}" title="Copy short link">Copy link</x-pd.button>
                </div>
            </div>
        </div>
    </x-pd.card>

    <livewire:dashboard.link-analytics :link="$link" theme="public" />
</x-layouts.public-dashboard>
