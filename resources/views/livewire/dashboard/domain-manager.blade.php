<div>
    @if ($canAdd)
        <div class="card" style="max-width: 650px; margin-bottom: 2rem;">
            <h2 class="card-title">Add Custom Domain</h2>
            <p style="color: var(--text-secondary); margin-bottom: 1.25rem; font-size: 0.9rem;">
                Point a hostname you own at ternis.link, then verify ownership with a DNS TXT record.
                Verified domains become available in the link creation form.
            </p>

            @if ($justCreatedId && isset($instructions[$justCreatedId]))
                <div class="alert alert-success">
                    <strong>Domain registered — one step left:</strong> publish the TXT record below, then press Verify.
                </div>
            @endif

            <form wire:submit="addDomain">
                <div class="form-group">
                    <label for="hostname">Hostname *</label>
                    <input
                        type="text"
                        id="hostname"
                        wire:model="hostname"
                        placeholder="e.g. links.example.com"
                        required
                    >
                    @error('hostname') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary">Add Domain</button>
            </form>
        </div>
    @else
        <div class="alert alert-error" style="max-width: 650px; margin-bottom: 2rem;">
            <strong>Custom domains are not included in your plan ({{ $planName }}).</strong><br>
            Upgrade to a plan with custom domains to register your own hostnames.
            You can still create links on the system domains below.
        </div>
    @endif

    <div class="card" style="margin-bottom: 2rem;">
        <h2 class="card-title">Your Domains</h2>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Hostname</th>
                        <th>Status</th>
                        <th>Links</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ownDomains as $domain)
                        <tr>
                            <td><code>{{ $domain->hostname }}</code></td>
                            <td>
                                @if ($domain->isVerified())
                                    <span style="color: var(--success); font-weight: 600; font-size: 0.85rem;">● Verified</span>
                                @else
                                    <span style="color: var(--warning, #b45309); font-weight: 600; font-size: 0.85rem;">● Pending DNS</span>
                                @endif
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">{{ $domain->links_count }}</td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    @if (! $domain->isVerified())
                                        <button wire:click="verifyDomain({{ $domain->id }})" class="btn btn-primary btn-sm">Verify</button>
                                    @endif
                                    <button wire:click="removeDomain({{ $domain->id }})" wire:confirm="Remove {{ $domain->hostname }}? Links on it stop resolving, analytics are preserved." class="btn btn-danger btn-sm">Remove</button>
                                </div>
                            </td>
                        </tr>
                        @if (! $domain->isVerified() && isset($instructions[$domain->id]))
                            <tr>
                                <td colspan="4" style="background: var(--bg-primary);">
                                    <div style="font-size: 0.85rem;">
                                        <strong>Verify ownership</strong> — publish this DNS record, wait for propagation, then press Verify:
                                        <div style="margin: 0.5rem 0; padding: 0.75rem; background: var(--bg-secondary, var(--bg-primary)); border-radius: var(--radius-sm); border: 1px solid var(--border-color); font-family: var(--font-mono); font-size: 0.8rem; word-break: break-all;">
                                            TXT&nbsp;&nbsp;{{ $instructions[$domain->id]['host'] }}<br>
                                            {{ $instructions[$domain->id]['value'] }}
                                        </div>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button
                                                type="button"
                                                class="btn btn-secondary btn-sm"
                                                onclick="navigator.clipboard.writeText(@js($instructions[$domain->id]['value'])).then(() => { this.textContent = 'Copied!'; setTimeout(() => this.textContent = 'Copy token', 2000); })"
                                            >Copy token</button>
                                        </div>
                                        @error("verify.{$domain->id}") <div class="form-error" style="margin-top: 0.5rem;">{{ $message }}</div> @enderror
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                No custom domains yet. Add one above to use your own hostname for short links.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2 class="card-title">System Domains</h2>
        <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.9rem;">
            Always available for link creation — no verification needed.
        </p>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Hostname</th>
                        <th>Type</th>
                        <th>Links</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($systemDomains as $domain)
                        <tr>
                            <td><code>{{ $domain->hostname }}</code></td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">{{ $domain->type->value ?? $domain->type }}</td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">{{ $domain->links_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
