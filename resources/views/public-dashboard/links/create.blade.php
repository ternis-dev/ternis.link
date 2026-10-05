<x-layouts.public-dashboard title="Create Link — my.href.nz">
    <div class="mb-6">
        <a href="{{ route('public-dashboard.links') }}" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-300">← Back to Links</a>
        <h2 class="mt-2 font-display text-2xl font-bold tracking-tight">Create Short Link</h2>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Pick a domain, set a destination — custom slugs and short lengths included for members.</p>
    </div>

    <div class="pd-card max-w-2xl rounded-2xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900 sm:p-8">
        <livewire:dashboard.link-form scope="public" theme="public" />
    </div>
</x-layouts.public-dashboard>
