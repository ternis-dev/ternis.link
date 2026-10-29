<x-layouts.dashboard title="Link Analytics — {{ $link->slug }}">
    @php
        $query = isset($fromApiKey) && $fromApiKey ? ['from_api_key' => $fromApiKey] : [];
    @endphp
    <x-ui.page-header
        :title="($link->domain->hostname ?? 'href.nz').'/'.$link->slug"
        :backHref="$backHref ?? route('dashboard.links')"
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
                        <span class="inline-flex items-center rounded-full border border-neutral-300 px-2 py-0.5 text-[11px] font-medium text-neutral-600 dark:border-neutral-700 dark:text-neutral-400">{{ $tag }}</span>
                    @endforeach
                </span>
            @endif
            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                @if ($link->bot_protection)
                    <x-ui.badge tone="solid">🛡️ Bot Protection Active</x-ui.badge>
                @endif
                @if ($link->allow_bots)
                    <x-ui.badge tone="solid">🤖 Bots & Crawlers Allowed</x-ui.badge>
                @endif
                @if ($link->password_hash)
                    <x-ui.badge tone="solid">🔒 Password Protected</x-ui.badge>
                @endif
                @if (in_array('quarantine', $link->tags ?? [], true))
                    <x-ui.badge tone="danger">🚨 Quarantined by AI</x-ui.badge>
                @endif
            </div>
        </x-slot:subtitle>
        <x-slot:actions>
            <x-ui.button href="{{ route('dashboard.links.edit', array_merge([$link->id], $query)) }}" size="sm">Edit Link</x-ui.button>
            <x-ui.button size="sm" data-copy="{{ $link->short_url }}" title="Copy short link">Copy</x-ui.button>
            <form method="POST" action="{{ route('dashboard.links.duplicate', $link->id) }}" class="inline">
                @csrf
                <x-ui.button type="submit" size="sm">Duplicate</x-ui.button>
            </form>
            <x-ui.button href="https://{{ $link->domain->hostname ?? 'href.nz' }}/{{ $link->slug }}" size="sm" target="_blank">Visit Link ↗</x-ui.button>
            <x-ui.button href="{{ route('dashboard.links.qr', $link->id) }}" size="sm">Download QR</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if (in_array('quarantine', $link->tags ?? [], true))
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50/70 p-5 text-rose-900 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-200">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="font-display text-base font-bold text-rose-700 dark:text-rose-300">⚠️ Link Quarantined by AI Spam & Threat Detection</h3>
                    <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">
                        This destination was flagged as malicious/spam and deactivated to protect visitors.
                        If you believe this is a false positive, you can submit an appeal for manual review.
                    </p>
                </div>
                <x-ui.button href="{{ route('dashboard.appeals.create', ['link' => $link->id]) }}" variant="danger" size="sm" class="shrink-0">
                    Submit Spam Appeal
                </x-ui.button>
            </div>
        </div>
    @endif

    <x-ui.card title="QR Code" class="mb-6">
        <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center">
            <img src="{{ $qrSvg }}" alt="QR code for {{ ($link->domain->hostname ?? 'href.nz').'/'.$link->slug }}" class="h-40 w-40 rounded-lg border border-neutral-200 bg-white p-2 dark:border-neutral-700">
            <div>
                <p class="text-sm font-medium">Scan to open <code>https://{{ ($link->domain->hostname ?? 'href.nz').'/'.$link->slug }}</code></p>
                <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">PNG download, 600×600 — print-ready for flyers and stickers.</p>
                <div class="mt-3">
                    <x-ui.button href="{{ route('dashboard.links.qr', $link->id) }}" size="sm" variant="primary">Download PNG</x-ui.button>
                </div>
            </div>
        </div>
    </x-ui.card>

    <livewire:dashboard.link-analytics :link="$link" />
</x-layouts.dashboard>
