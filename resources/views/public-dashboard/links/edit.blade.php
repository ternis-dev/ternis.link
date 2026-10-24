<x-layouts.public-dashboard title="Edit Link — {{ \App\Support\PublicDashboard::label() }}">
    <x-pd.head
        title="Edit Short Link"
        subtitle="Update the target destination, expiration timetable, targeting rules, or toggle active status."
        :backHref="$backHref ?? \App\Support\PublicDashboard::route('links')"
        :backLabel="$backLabel ?? 'Back to Links'"
    />

    @if (session('info'))
        <div class="mb-6 w-full rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 text-sm font-medium text-emerald-900 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-200" role="status">{{ session('info') }}</div>
    @endif

    <div class="nd-card mb-6 w-full rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8 dark:border-slate-800/80 dark:bg-[#111720]">
        <livewire:dashboard.link-edit-form :link="$link" theme="public" />
    </div>

    <div class="nd-card w-full rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8 dark:border-slate-800/80 dark:bg-[#111720]">
        <livewire:dashboard.link-targeting :link="$link" theme="public" />
    </div>
</x-layouts.public-dashboard>
