<x-layouts.hnz-dashboard title="Create Link — {{ \App\Support\PublicDashboard::label() }}">
    <x-hnz.head
        title="Create Short Link"
        subtitle="Pick a domain, set a destination — custom slugs and short lengths included for members."
        :backHref="\App\Support\PublicDashboard::route('links')"
        backLabel="Back to Links"
    />

    <div class="nd-card w-full rounded-xl border border-neutral-200 bg-white p-6 sm:p-8 dark:border-neutral-800 dark:bg-neutral-900">
        <livewire:dashboard.link-form scope="public" theme="public" />
    </div>
</x-layouts.hnz-dashboard>
