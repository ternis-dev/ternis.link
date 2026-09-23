<div>
    <div class="card" style="max-width: 650px; margin-bottom: 2rem;">
        <h2 class="card-title">Generate API Key</h2>
        <p style="color: var(--text-secondary); margin-bottom: 1.25rem; font-size: 0.9rem;">
            API keys allow you to authenticate with <code>links.t-api.de</code> programmatically.
        </p>

        @if ($newlyCreatedKey)
            <div class="alert alert-success">
                <strong>New API Key Generated:</strong><br>
                <div style="margin: 0.75rem 0; padding: 0.75rem; background: var(--bg-primary); border-radius: var(--radius-sm); font-family: var(--font-mono); font-size: 0.9rem; word-break: break-all; color: var(--text-primary); border: 1px solid var(--border-color);">
                    {{ $newlyCreatedKey }}
                </div>
                <small style="display: block; margin-bottom: 0.75rem;">Make sure to copy your API key now. You won't be able to see it again!</small>
                <div style="display: flex; gap: 0.5rem;">
                    <button
                        type="button"
                        class="btn btn-primary btn-sm"
                        onclick="navigator.clipboard.writeText(@js($newlyCreatedKey)).then(() => { this.textContent = 'Copied!'; setTimeout(() => this.textContent = 'Copy key', 2000); })"
                    >Copy key</button>
                    <button wire:click="dismissNewKey" class="btn btn-secondary btn-sm">I have saved my key</button>
                </div>
            </div>
        @endif

        <form wire:submit="createKey">
            <div class="form-group">
                <label for="keyName">Key Label / Name *</label>
                <input
                    type="text"
                    id="keyName"
                    wire:model="keyName"
                    placeholder="e.g. CLI Script, Production Server"
                    required
                >
                @error('keyName') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary">Generate Key</button>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title">Active API Keys</h2>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Label</th>
                        <th>Prefix</th>
                        <th>API Version</th>
                        <th>Created</th>
                        <th>Last Used</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($apiKeys as $key)
                        <tr>
                            <td style="font-weight: 600;">{{ $key->name }}</td>
                            <td><code>{{ $key->masked_key }}</code></td>
                            <td>v{{ $key->api_version }}</td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">{{ $key->created_at->format('M d, Y') }}</td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">
                                {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Never' }}
                            </td>
                            <td>
                                @if ($key->isValid())
                                    <span style="color: var(--success); font-weight: 600; font-size: 0.85rem;">● Active</span>
                                @else
                                    <span style="color: var(--danger); font-size: 0.85rem;">Revoked</span>
                                @endif
                            </td>
                            <td>
                                @if ($key->isValid())
                                    <button wire:click="revokeKey({{ $key->id }})" wire:confirm="Revoke this API key immediately?" class="btn btn-danger btn-sm">Revoke</button>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">Revoked</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                No API keys yet. Generate one above to access the API.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
