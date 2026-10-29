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
            <x-hnz.card class="{{ $notification->read_at === null ? 'border-emerald-500' : '' }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="flex items-center gap-2 text-sm font-semibold">
                            @if ($notification->read_at === null)
                                <span class="inline-block h-2 w-2 shrink-0 rounded-full bg-emerald-500" aria-label="Unread"></span>
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
                <p class="text-sm text-neutral-500 dark:text-neutral-400">No notifications yet. Security events and moderation decisions will appear here.</p>
            </x-hnz.card>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="mt-6">{{ $notifications->links() }}</div>
    @endif
</x-layouts.hnz-dashboard>
