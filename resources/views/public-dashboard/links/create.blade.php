<x-layouts.public-dashboard title="Create Link — my.ternis.link">
    <x-pd.head
        title="Create Short Link"
        subtitle="Pick a domain, set a destination — custom slugs and short lengths included for members."
        :backHref="route('public-dashboard.links')"
        backLabel="Back to Links"
    />

    <div class="nd-card max-w-2xl rounded-xl border border-neutral-200 bg-white p-6 sm:p-8 dark:border-neutral-800 dark:bg-neutral-900">
        <livewire:dashboard.link-form scope="public" theme="public" />
    </div>
</x-layouts.public-dashboard>
