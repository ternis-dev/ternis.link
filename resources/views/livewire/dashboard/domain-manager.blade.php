<div>
    @if (($theme ?? 'dashboard') === 'public')
        {{-- Custom hostnames are a dash-side feature: public links only
             run on href.nz, meinlink.at, href.yt and qr.href.nz. --}}
        <x-ui.alert tone="info" class="mb-8 w-full">
            <strong>Public links run on the built-in hostnames.</strong><br>
            Custom hostnames and personal subdomains are managed on
            <a href="{{ \App\Support\DomainUrls::dashboard('/domains') }}" class="font-semibold underline underline-offset-2">dash.ternis.link</a>.
        </x-ui.alert>
    @endif

    @if (($theme ?? 'dashboard') === 'dashboard' && $canAdd)
        <x-ui.card title="Add Custom Domain" class="mb-8 w-full">
            <p class="mb-5 text-sm text-neutral-500 dark:text-neutral-400">
                Point a hostname you own at ternis.link, then verify ownership with a DNS TXT record.
                Verified domains become available in the link creation form.
            </p>

            @if ($justCreatedId && isset($instructions[$justCreatedId]))
                <x-ui.alert tone="success" class="mb-5">
                    <strong>Domain registered — one step left:</strong> publish the TXT record below, then press Verify.
                </x-ui.alert>
            @endif

            <form wire:submit="addDomain" class="flex flex-wrap items-end gap-3">
                <div class="min-w-60 flex-1">
                    <x-ui.input
                        label="Hostname *"
                        name="hostname"
                        type="text"
                        wire:model="hostname"
                        placeholder="e.g. links.example.com"
                        required
                    />
                </div>
                <x-ui.button type="submit" variant="primary">Add Domain</x-ui.button>
            </form>
        </x-ui.card>
    @elseif (($theme ?? 'dashboard') === 'dashboard')
        <x-ui.alert tone="error" class="mb-8 w-full">
            <strong>Custom domains are not included in your plan ({{ $planName }}).</strong><br>
            Upgrade to a plan with custom domains to register your own hostnames.
            You can still create links on the system domains below.
        </x-ui.alert>
    @endif

    @if (($theme ?? 'dashboard') === 'dashboard' && $canClaimSubdomain)
        <x-ui.card title="Your ternis.link Subdomain" class="mb-8 w-full">
            @if ($claimedHostname)
                <x-ui.alert tone="success" class="mb-5">
                    <strong><code>{{ $claimedHostname }}</code> is yours!</strong><br>
                    Already verified — it’s available in the link creation form right away.
                </x-ui.alert>
            @endif

            @if ($personalSubdomain)
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                    Your personal subdomain is <code>{{ $personalSubdomain->hostname }}</code> — verified and ready for links.
                    One per account.
                </p>
            @else
                <p class="mb-5 text-sm text-neutral-500 dark:text-neutral-400">
                    Claim a personal <code>{name}.ternis.link</code> address. No DNS setup needed —
                    the app owns the parent zone, so claims are verified instantly. One per account.
                </p>

                <form wire:submit="claimSubdomain" class="flex flex-wrap items-end gap-3">
                    <div class="min-w-60 flex-1">
                        <x-ui.input
                            label="Subdomain name *"
                            name="subdomain"
                            type="text"
                            wire:model="subdomain"
                            placeholder="e.g. fabian"
                            required
                            hint="3–63 lowercase letters, numbers and dashes. You’ll get {name}.ternis.link."
                        />
                    </div>
                    <x-ui.button type="submit" variant="primary">Claim Subdomain</x-ui.button>
                </form>
            @endif
        </x-ui.card>
    @endif

    <x-ui.card title="Your Domains" class="mb-8">
        <div class="mb-4 flex flex-wrap items-center justify-end gap-3">
            @php($customizerTable = \App\Livewire\Dashboard\DomainManager::OWN_TABLE)
            @include('livewire.partials.column-customizer')
        </div>
        <x-ui.table>
            <thead>
                <tr>
                    @foreach ($visibleOwnColumns as $column)
                        <th>{{ $ownColumns[$column] ?? $column }}</th>
                    @endforeach
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ownDomains as $domain)
                    <tr>
                        @foreach ($visibleOwnColumns as $column)
                            @switch($column)
                                @case('hostname')
                                    <td><code>{{ $domain->hostname }}</code></td>
                                    @break

                                @case('status')
                                    <td>
                                        @if ($domain->isVerified())
                                            <x-ui.status state="verified" />
                                        @else
                                            <x-ui.status state="pending" />
                                        @endif
                                    </td>
                                    @break

                                @case('links')
                                    <td class="text-xs text-neutral-500">{{ $domain->links_count }}</td>
                                    @break
                            @endswitch
                        @endforeach
                        <td>
                            <div class="flex gap-2">
                                @if (! $domain->isVerified())
                                    <x-ui.button wire:click="verifyDomain('{{ $domain->id }}')" size="sm" variant="primary">Verify</x-ui.button>
                                @endif
                                <x-ui.button wire:click="removeDomain('{{ $domain->id }}')" wire:confirm="Remove {{ $domain->hostname }}? Links on it stop resolving, analytics are preserved." size="sm" variant="danger">Remove</x-ui.button>
                            </div>
                        </td>
                    </tr>
                    @if (! $domain->isVerified() && isset($instructions[$domain->id]))
                        <tr>
                            <td colspan="{{ count($visibleOwnColumns) + 1 }}" class="bg-neutral-50 dark:bg-neutral-950">
                                <div class="text-sm">
                                    <strong>Verify ownership</strong> — publish this DNS record, wait for propagation, then press Verify:
                                    <div class="my-2 rounded-lg border border-neutral-300 bg-white p-3 font-mono text-xs break-all dark:border-neutral-700 dark:bg-neutral-900">
                                        TXT&nbsp;&nbsp;{{ $instructions[$domain->id]['host'] }}<br>
                                        {{ $instructions[$domain->id]['value'] }}
                                    </div>
                                    <x-ui.button
                                        size="sm"
                                        onclick="navigator.clipboard.writeText(@js($instructions[$domain->id]['value'])).then(() => { this.textContent = 'Copied!'; setTimeout(() => this.textContent = 'Copy token', 2000); })"
                                    >Copy token</x-ui.button>
                                    @error("verify.{$domain->id}") <p class="mt-2 text-xs font-medium" role="alert">{{ $message }}</p> @enderror
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="{{ count($visibleOwnColumns) + 1 }}">
                            <x-ui.empty-state>No custom domains yet. Add one above to use your own hostname for short links.</x-ui.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>

    <x-ui.card title="System Domains">
        <p class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">
            Always available for link creation — no verification needed.
        </p>
        <div class="mb-4 flex flex-wrap items-center justify-end gap-3">
            @php($customizerTable = \App\Livewire\Dashboard\DomainManager::SYSTEM_TABLE)
            @include('livewire.partials.column-customizer')
        </div>
        <x-ui.table>
            <thead>
                <tr>
                    @foreach ($visibleSystemColumns as $column)
                        <th>{{ $systemColumns[$column] ?? $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($systemDomains as $domain)
                    <tr>
                        @foreach ($visibleSystemColumns as $column)
                            @switch($column)
                                @case('hostname')
                                    <td><code>{{ $domain->hostname }}</code></td>
                                    @break

                                @case('type')
                                    <td class="text-xs text-neutral-500">{{ $domain->type->value ?? $domain->type }}</td>
                                    @break

                                @case('links')
                                    <td class="text-xs text-neutral-500">{{ $domain->links_count }}</td>
                                    @break
                            @endswitch
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>
    </x-ui.card>
</div>
