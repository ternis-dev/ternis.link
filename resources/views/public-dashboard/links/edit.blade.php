<x-layouts.public-dashboard title="Edit Link — my.ternis.link">
    <div class="mb-6">
        <a href="{{ $backHref ?? route('public-dashboard.links') }}" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-300">← {{ $backLabel ?? 'Back to Links' }}</a>
        <h2 class="mt-2 font-display text-2xl font-bold tracking-tight">Edit Short Link</h2>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Change where it points, when it expires, or switch it off.</p>
    </div>

    @if (session('info'))
        <div class="mb-6 max-w-2xl rounded-2xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900 dark:border-indigo-400/20 dark:bg-indigo-400/10 dark:text-indigo-200" role="status">{{ session('info') }}</div>
    @endif

    <div class="pd-card mb-6 max-w-2xl rounded-2xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900 sm:p-8">
        <livewire:dashboard.link-edit-form :link="$link" theme="public" />
    </div>

    <div class="pd-card max-w-2xl rounded-2xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900 sm:p-8">
        <livewire:dashboard.link-targeting :link="$link" theme="public" />
    </div>
</x-layouts.public-dashboard>
