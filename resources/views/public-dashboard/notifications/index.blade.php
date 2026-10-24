<x-layouts.public-dashboard title="Notifications — {{ \App\Support\PublicDashboard::label() }}">
    <x-pd.head
        title="Notifications"
        subtitle="Security alerts, moderation logs, and system notices regarding your public links."
    >
        @if ($notifications->count() > 0)
            <x-slot:actions>
                <form method="POST" action="{{ \App\Support\PublicDashboard::route('notifications.read-all') }}">
                    @csrf
                    <x-pd.button type="submit" variant="secondary" size="sm">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 7 17l-5-5"/><path d="m22 10-7.5 7.5L13 16"/></svg>
                        Mark all as read
                    </x-pd.button>
                </form>
            </x-slot:actions>
        @endif
    </x-pd.head>

    <div class="space-y-3.5">
        @forelse ($notifications as $notification)
            <x-pd.card class="{{ $notification->read_at === null ? 'ring-1 ring-emerald-500/40 border-emerald-500/30' : '' }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2.5">
                            @if ($notification->read_at === null)
                                <span class="relative flex h-2 w-2">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                                </span>
                            @endif
                            <p class="text-sm font-bold text-slate-900 dark:text-white">
                                {{ $notification->data['title'] ?? class_basename($notification->type) }}
                            </p>
                        </div>
                        @foreach ((array) ($notification->data['lines'] ?? []) as $line)
                            <p class="mt-1.5 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">{{ $line }}</p>
                        @endforeach
                        <p class="mt-2 font-mono text-[11px] text-slate-400 dark:text-slate-500">{{ $notification->created_at?->diffForHumans() }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        @if (! empty($notification->data['action_url']))
                            <form method="POST" action="{{ \App\Support\PublicDashboard::route('notifications.read', $notification->id) }}">
                                @csrf
                                <x-pd.button type="submit" variant="secondary" size="sm">{{ $notification->data['action_label'] ?? 'Open' }}</x-pd.button>
                            </form>
                        @elseif ($notification->read_at === null)
                            <form method="POST" action="{{ \App\Support\PublicDashboard::route('notifications.read', $notification->id) }}">
                                @csrf
                                <x-pd.button type="submit" variant="ghost" size="sm">Mark as read</x-pd.button>
                            </form>
                        @endif
                    </div>
                </div>
            </x-pd.card>
        @empty
            <x-pd.empty
                title="All caught up"
                subtitle="No notifications or alerts. Moderation decisions and security events will show up here."
            />
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="mt-8">{{ $notifications->links() }}</div>
    @endif
</x-layouts.public-dashboard>
