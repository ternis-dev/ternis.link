<x-layouts.app title="Link Preview — href.nz">
    <div class="mx-auto flex max-w-xl flex-col items-center py-16 text-center">
        <span class="inline-flex h-12 w-12 items-center justify-center rounded-full border-2 border-neutral-900 dark:border-white" aria-hidden="true">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4.5 6v5c0 4.5 3 7.6 7.5 9 4.5-1.4 7.5-4.5 7.5-9V6Z"/><path d="m9.2 12 2 2 3.6-4.2"/></svg>
        </span>
        <h1 class="mt-4 text-xl font-semibold">Link preview</h1>
        <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Nothing opens automatically — look first, then decide.</p>

        @if ($mode === 'slug' && $link)
            <p class="mt-3 text-sm text-neutral-500 dark:text-neutral-400">
                Short link <code>https://{{ $link->domain->hostname }}/{{ $link->slug }}</code>
            </p>
        @endif

        <div class="mt-6 w-full rounded-xl border border-neutral-200 bg-white p-5 text-left dark:border-neutral-800 dark:bg-neutral-900">
            <p class="text-xs font-semibold tracking-widest text-neutral-500 uppercase dark:text-neutral-400">Goes to</p>
            <p class="mt-1 font-mono text-sm break-all">{{ $destination }}</p>

            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-neutral-500 dark:text-neutral-400">Host</dt>
                    <dd class="font-mono break-all">{{ $host !== '' ? $host : '—' }}</dd>
                </div>
                @if ($mode === 'slug' && $link)
                    <div class="flex justify-between gap-4">
                        <dt class="text-neutral-500 dark:text-neutral-400">Created</dt>
                        <dd>{{ $link->created_at?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-neutral-500 dark:text-neutral-400">Clicks so far</dt>
                        <dd>{{ number_format((int) $link->click_count) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4">
                    <dt class="text-neutral-500 dark:text-neutral-400">Safety check</dt>
                    <dd class="text-right font-medium {{ $junkReasons !== [] ? 'text-red-600 dark:text-red-400' : '' }}">
                        {{ $junkReasons !== [] ? 'Flagged — see below' : 'No known risk patterns' }}
                    </dd>
                </div>
            </dl>

            @if ($junkReasons !== [])
                <div class="mt-4 rounded-lg border-2 border-red-600 px-4 py-3 text-sm text-red-700 dark:border-red-400 dark:text-red-300" role="alert">
                    <strong>This destination matches automated-scan patterns.</strong>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($junkReasons as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-1">Only continue if you trust it.</p>
                </div>
            @endif

            <div class="mt-5 flex flex-wrap gap-2">
                <x-ui.button href="{{ $destination }}" variant="primary" target="_blank" rel="noopener">Visit link</x-ui.button>
                <x-ui.button
                    onclick="navigator.clipboard.writeText(@js($destination)).then(() => { this.textContent = 'Copied!'; setTimeout(() => this.textContent = 'Copy link', 2000); })"
                >Copy link</x-ui.button>
            </div>
        </div>

        <x-ui.button href="https://href.nz/" class="mt-6" variant="ghost">Back to href.nz</x-ui.button>
    </div>
</x-ui.layout>
