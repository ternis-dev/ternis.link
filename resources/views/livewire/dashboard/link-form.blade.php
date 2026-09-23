<div class="card" style="max-width: 650px;">
    <h2 class="card-title">New Short Link</h2>

    @if ($createdSlug)
        <div class="alert alert-success" style="margin-bottom: 1.5rem;">
            <strong>Link created successfully!</strong><br>
            Short URL: <code>https://{{ $createdDomain }}/{{ $createdSlug }}</code>
        </div>
    @endif

    <form wire:submit="create">
        <div class="form-group">
            <label for="destination_url">Destination URL *</label>
            <input
                type="url"
                id="destination_url"
                wire:model="destination_url"
                placeholder="https://example.com/very-long-url"
                required
            >
            @error('destination_url') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="domain_id">Domain *</label>
            <select id="domain_id" wire:model="domain_id" required>
                @foreach ($domains as $domain)
                    <option value="{{ $domain->id }}">{{ $domain->hostname }} ({{ $domain->type->value ?? $domain->type }})</option>
                @endforeach
            </select>
            @error('domain_id') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="slug">Custom Slug (optional)</label>
            <input
                type="text"
                id="slug"
                wire:model="slug"
                placeholder="Leave blank for automatic generation"
            >
            <small style="color: var(--text-muted); display: block; margin-top: 0.25rem;">
                Alphanumeric characters, dashes, and underscores only. Min length: {{ auth()->user()->plan?->min_slug_length ?? \App\Services\LinkService::AUTHENTICATED_DEFAULT_SLUG_LENGTH }} chars.
            </small>
            @error('slug') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="expires_at">Expiration Date (optional)</label>
            <input
                type="datetime-local"
                id="expires_at"
                wire:model="expires_at"
            >
            @error('expires_at') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Create Short Link</button>
            <a href="{{ route('dashboard.links') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
