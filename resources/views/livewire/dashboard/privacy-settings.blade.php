<div class="mt-6 space-y-6">
    <x-ui.card title="Your data">
        <dl class="grid gap-2 text-sm sm:grid-cols-3">
            <div><dt class="text-neutral-500">Short links</dt><dd class="font-mono text-lg">{{ number_format($summary['links']) }}</dd></div>
            <div><dt class="text-neutral-500">Domains</dt><dd class="font-mono text-lg">{{ number_format($summary['domains']) }}</dd></div>
            <div><dt class="text-neutral-500">API keys</dt><dd class="font-mono text-lg">{{ number_format($summary['api_keys']) }}</dd></div>
        </dl>
        <p class="mt-3 text-xs text-neutral-500">
            Exports expire after {{ $retention['export_ttl_days'] ?? 7 }} days.
            Encrypted IPs are pruned after {{ $retention['guest_ip_retention_days'] ?? 30 }} days.
        </p>
    </x-ui.card>

    <x-ui.card title="Export my data">
        <p class="mb-3 text-sm text-neutral-500">A ZIP with your profile, links, domains, keys and activity — no secrets, no visitor data.</p>
        <x-ui.button wire:click="export" variant="secondary">Start export</x-ui.button>
        @error('export')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror

        @if ($exports->isNotEmpty())
            <ul class="mt-4 space-y-2 text-sm">
                @foreach ($exports as $export)
                    <li class="flex items-center justify-between gap-3">
                        <span>{{ $export->created_at?->format('M d, Y H:i') }} · {{ $export->status }}</span>
                        @if ($export->status === 'done')
                            <a href="{{ route('dashboard.settings.export-download', $export->id) }}" class="font-medium underline underline-offset-2">Download</a>
                        @else
                            <span class="text-xs text-neutral-500" wire:poll.5s>processing…</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>

    <x-ui.card title="Delete my account">
        @if ($deletionAt)
            <x-ui.alert tone="error" class="mb-4">
                Deletion scheduled — your account disappears on
                {{ $deletionAt->copy()->addDays($graceDays)->format('M d, Y') }}
                unless you cancel. Signing in again also cancels automatically.
            </x-ui.alert>
            <x-ui.button wire:click="cancelDeletion" variant="primary">Cancel deletion</x-ui.button>
        @else
            <p class="mb-3 text-sm text-neutral-500">
                Schedules full erasure with a {{ $graceDays }}-day grace period.
                Your links become anonymous guest links (stats stay, ownership and IPs go);
                keys, domains metadata and settings are removed.
            </p>
            <label class="mb-3 flex cursor-pointer items-start gap-2 text-sm">
                <input type="checkbox" wire:model="acknowledged" class="mt-0.5 h-4 w-4 rounded accent-neutral-900 dark:accent-white">
                I understand this cannot be undone after the grace period.
            </label>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.input label="Type DELETE-ME to confirm" name="confirmText" type="text" wire:model="confirmText" maxlength="20" class="max-w-xs" />
                <x-ui.button wire:click="scheduleDeletion" variant="danger" wire:confirm="Schedule deletion of your entire account?">Schedule deletion</x-ui.button>
            </div>
            @error('confirmText')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
        @endif
    </x-ui.card>
</div>
