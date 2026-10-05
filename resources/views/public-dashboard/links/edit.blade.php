<x-layouts.public-dashboard title="Edit Link — my.href.nz">
    <div class="mb-6">
        <a href="{{ route('public-dashboard.links') }}" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-300">← Back to Links</a>
        <h2 class="mt-2 font-display text-2xl font-bold tracking-tight">Edit Short Link</h2>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Change where it points, when it expires, or switch it off.</p>
    </div>

    <div class="pd-card mb-6 max-w-2xl rounded-2xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900 sm:p-8">
        <livewire:dashboard.link-edit-form :link="$link" theme="public" />
    </div>

    <div class="pd-card max-w-2xl rounded-2xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900 sm:p-8">
        <livewire:dashboard.link-targeting :link="$link" theme="public" />
    </div>
</x-layouts.public-dashboard>
