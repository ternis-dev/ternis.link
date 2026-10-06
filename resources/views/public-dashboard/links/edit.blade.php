<x-layouts.public-dashboard title="Edit Link — my.ternis.link">
    <x-pd.head
        title="Edit Short Link"
        subtitle="Change where it points, when it expires, or switch it off."
        :backHref="$backHref ?? route('public-dashboard.links')"
        :backLabel="$backLabel ?? 'Back to Links'"
    />

    @if (session('info'))
        <div class="mb-6 max-w-2xl rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-200" role="status">{{ session('info') }}</div>
    @endif

    <div class="nd-card mb-6 max-w-2xl rounded-xl border border-neutral-200 bg-white p-6 sm:p-8 dark:border-neutral-800 dark:bg-neutral-900">
        <livewire:dashboard.link-edit-form :link="$link" theme="public" />
    </div>

    <div class="nd-card max-w-2xl rounded-xl border border-neutral-200 bg-white p-6 sm:p-8 dark:border-neutral-800 dark:bg-neutral-900">
        <livewire:dashboard.link-targeting :link="$link" theme="public" />
    </div>
</x-layouts.public-dashboard>
