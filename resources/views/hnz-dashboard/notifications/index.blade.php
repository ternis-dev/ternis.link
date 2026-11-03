<x-layouts.hnz-dashboard title="Notifications — {{ \App\Support\PublicDashboard::label() }}">
    <x-hnz.head
        title="Notifications"
        subtitle="Security events, moderation decisions and system alerts about your public links."
    >
        @if ($notifications->count() > 0)
            <x-slot:actions>
                <form method="POST" action="{{ \App\Support\PublicDashboard::route('notifications.read-all') }}">
                    @csrf
                    <x-hnz.button type="submit" variant="secondary" size="sm">Mark all as read</x-hnz.button>
                </form>
            </x-slot:actions>
        @endif
    </x-hnz.head>

    <div class="space-y-3">
        @forelse ($notifications as $notification)
            <x-hnz.card class="{{ $notification->read_at === null ? 'border-violet-500/50 bg-violet-50/20 dark:border-violet-400/40 dark:bg-violet-400/5' : '' }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="flex items-center gap-2 text-sm font-semibold">
                            @if ($notification->read_at === null)
                                <span class="inline-block h-2 w-2 shrink-0 rounded-full bg-violet-600 animate-pulse" aria-label="Unread"></span>
                            @endif
                            {{ $notification->data['title'] ?? class_basename($notification->type) }}
                        </p>
                        @foreach ((array) ($notification->data['lines'] ?? []) as $line)
                            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ $line }}</p>
                        @endforeach
                        <p class="mt-2 text-xs text-neutral-400 dark:text-neutral-500">{{ $notification->created_at?->diffForHumans() }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        @if (! empty($notification->data['action_url']))
                            <form method="POST" action="{{ \App\Support\PublicDashboard::route('notifications.read', $notification->id) }}">
                                @csrf
                                <x-hnz.button type="submit" variant="secondary" size="sm">{{ $notification->data['action_label'] ?? 'Open' }}</x-hnz.button>
                            </form>
                        @elseif ($notification->read_at === null)
                            <form method="POST" action="{{ \App\Support\PublicDashboard::route('notifications.read', $notification->id) }}">
                                @csrf
                                <x-hnz.button type="submit" variant="ghost" size="sm">Mark as read</x-hnz.button>
                            </form>
                        @endif
                    </div>
                </div>
            </x-hnz.card>
        @empty
            <x-hnz.card>
                <div class="py-6 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 dark:bg-violet-400/10 dark:text-violet-300">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">No notifications yet</p>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Security events and moderation decisions will appear here.</p>
                </div>
            </x-hnz.card>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="mt-6">{{ $notifications->links() }}</div>
    @endif
</x-layouts.hnz-dashboard>
