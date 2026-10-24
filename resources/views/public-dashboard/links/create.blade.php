<x-layouts.public-dashboard title="Create Link — {{ \App\Support\PublicDashboard::label() }}">
    <x-pd.head
        title="Create Short Link"
        subtitle="Choose a domain and destination — custom slugs and link parameters included."
        :backHref="\App\Support\PublicDashboard::route('links')"
        backLabel="Back to Links"
    />

    <div class="nd-card w-full rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8 dark:border-slate-800/80 dark:bg-[#111720]">
        <livewire:dashboard.link-form scope="public" theme="public" />
    </div>
</x-layouts.public-dashboard>
