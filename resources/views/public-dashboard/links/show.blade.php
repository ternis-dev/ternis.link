<x-layouts.public-dashboard title="Link Analytics — {{ $link->slug }}">
    <div class="mb-6">
        <a href="{{ route('public-dashboard.links') }}" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-300">← Back to Links</a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="font-display text-2xl font-bold tracking-tight">{{ ($link->domain->hostname ?? 'href.nz').'/'.$link->slug }}</h2>
            <span class="pd-domain-chip">{{ $link->domain->hostname ?? 'href.nz' }}</span>
        </div>
        <p class="mt-1 max-w-2xl truncate text-sm text-neutral-500 dark:text-neutral-400">
            Target: <a href="{{ $link->destination_url }}" target="_blank" rel="noopener noreferrer" class="underline underline-offset-2">{{ $link->destination_url }}</a>
        </p>
        @if ($link->description)
            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">{{ $link->description }}</p>
        @endif
        @if (! empty($link->tags))
            <p class="mt-1.5 flex flex-wrap gap-1.5">
                @foreach ($link->tags as $tag)
                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-400/10 dark:text-indigo-300">{{ $tag }}</span>
                @endforeach
            </p>
        @endif
        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ route('public-dashboard.links.edit', $link->id) }}" class="rounded-full border border-neutral-300 px-3 py-1.5 text-xs font-semibold transition hover:border-indigo-600 hover:text-indigo-600 dark:border-neutral-700 dark:hover:border-indigo-400 dark:hover:text-indigo-300">Edit link</a>
            <a href="https://{{ $link->domain->hostname ?? 'href.nz' }}/{{ $link->slug }}" target="_blank" class="rounded-full border border-neutral-300 px-3 py-1.5 text-xs font-semibold transition hover:border-indigo-600 hover:text-indigo-600 dark:border-neutral-700 dark:hover:border-indigo-400 dark:hover:text-indigo-300">Visit link ↗</a>
            <a href="{{ route('public-dashboard.links.qr', $link->id) }}" class="rounded-full bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-indigo-500">Download QR</a>
        </div>
    </div>

    <div class="pd-card mb-6 rounded-2xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex flex-col items-start gap-5 sm:flex-row sm:items-center">
            <div class="rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-500 p-1.5">
                <img src="{{ $qrSvg }}" alt="QR code for {{ ($link->domain->hostname ?? 'href.nz').'/'.$link->slug }}" class="h-40 w-40 rounded-xl bg-white p-2">
            </div>
            <div>
                <h3 class="font-display text-lg font-bold tracking-tight">QR Code</h3>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Scan to open <code class="rounded bg-indigo-50 px-1 py-0.5 text-xs text-indigo-700 dark:bg-indigo-400/10 dark:text-indigo-300">https://{{ ($link->domain->hostname ?? 'href.nz').'/'.$link->slug }}</code></p>
                <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">PNG download, 600×600 — print-ready for flyers and stickers.</p>
                <div class="mt-3">
                    <a href="{{ route('public-dashboard.links.qr', $link->id) }}" class="rounded-full bg-indigo-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-indigo-500">Download PNG</a>
                </div>
            </div>
        </div>
    </div>

    <livewire:dashboard.link-analytics :link="$link" theme="public" />
</x-layouts.public-dashboard>
