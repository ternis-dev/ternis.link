<x-layouts.app title="Network Security — ternis.link stats">
    <x-slot:head><link rel="alternate" type="text/markdown" href="{{ route('pages.stats.security-md') }}"></x-slot:head>
    <x-ui.page-header title="Network Security" subtitle="Understand how the network evaluates and protects short links." />
    <x-stats.nav />
    <div data-stats-page="security">
    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <x-ui.stat :value="number_format($stats['threat_evaluations'])" label="Security evaluations" description="All recorded evaluations" :key="'threat_evaluations'" />
        <x-ui.stat :value="number_format($stats['threats_quarantined'])" label="Threats Quarantined" description="Recorded actions and legacy tagged links" :key="'threats_quarantined'" />
        <x-ui.stat :value="number_format($stats['threats_bot_protected'])" label="Bot protection enabled" description="Recorded protection actions" :key="'threats_bot_protected'" />
        <x-ui.stat :value="number_format($stats['threat_evaluations_today'])" label="Evaluations today" description="Since midnight, application timezone" :key="'threat_evaluations_today'" />
    </div>
    <x-ui.card title="How these numbers are counted" description="Transparent totals, with historical limits.">
        <p class="text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">Quarantine totals include recorded quarantine actions and links carrying a legacy quarantine tag without a matching action. Repeat actions can include rescans; this is not a count of currently blocked unique links. Historical links deleted before logging cannot be reconstructed. Bot protection counts recorded enablement actions, not visitor challenges or verified visitors.</p>
        <p class="mt-4 text-sm text-neutral-600 dark:text-neutral-400">A human verification check confirms a visitor can continue. It does not certify that the destination is safe.</p>
    </x-ui.card>
    </div>
</x-layouts.app>
