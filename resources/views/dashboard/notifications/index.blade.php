<x-layouts.dashboard title="Notifications — ternis.link">
    <x-ui.page-header
        title="Notifications"
        subtitle="Security events, moderation decisions and system alerts."
    >
        @if ($notifications->count() > 0)
            <x-slot:actions>
                <form method="POST" action="{{ route('dashboard.notifications.read-all') }}">
                    @csrf
                    <x-ui.button type="submit" size="sm" variant="secondary">Mark all as read</x-ui.button>
                </form>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <div class="space-y-3">
        @forelse ($notifications as $notification)
            <x-ui.card class="{{ $notification->read_at === null ? 'border-neutral-900 dark:border-white' : '' }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="flex items-center gap-2 text-sm font-semibold">
                            @if ($notification->read_at === null)
                                <span class="inline-block h-2 w-2 shrink-0 rounded-full bg-neutral-900 dark:bg-white" aria-label="Unread"></span>
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
                            <form method="POST" action="{{ route('dashboard.notifications.read', $notification->id) }}">
                                @csrf
                                <x-ui.button type="submit" size="sm" variant="secondary">{{ $notification->data['action_label'] ?? 'Open' }}</x-ui.button>
                            </form>
                        @elseif ($notification->read_at === null)
                            <form method="POST" action="{{ route('dashboard.notifications.read', $notification->id) }}">
                                @csrf
                                <x-ui.button type="submit" size="sm" variant="ghost">Mark as read</x-ui.button>
                            </form>
                        @endif
                    </div>
                </div>
            </x-ui.card>
        @empty
            <x-ui.card>
                <x-ui.empty-state>No notifications yet. Security events and moderation decisions will appear here.</x-ui.empty-state>
            </x-ui.card>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="mt-6">{{ $notifications->links() }}</div>
    @endif
</x-layouts.dashboard>
