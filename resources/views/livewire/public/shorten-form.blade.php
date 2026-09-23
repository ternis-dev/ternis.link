<div class="card" style="max-width: 650px; margin: 2rem auto 0;">
    <h2 class="card-title">Shorten a link — no account needed</h2>

    @if ($shortUrl)
        <div class="alert alert-success" style="margin-bottom: 1.5rem;">
            <strong>Link created successfully!</strong><br>
            Short URL: <code>{{ $shortUrl }}</code>
        </div>
    @endif

    <form wire:submit="create">
        <div class="form-group">
            <label for="public_destination_url">Destination URL *</label>
            <input
                type="url"
                id="public_destination_url"
                wire:model="destination_url"
                placeholder="https://example.com/very-long-url"
                required
            >
            @error('destination_url') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="public_slug">Custom Slug (optional)</label>
            <input
                type="text"
                id="public_slug"
                wire:model="slug"
                placeholder="Leave blank for automatic generation"
            >
            <small style="color: var(--text-muted); display: block; margin-top: 0.25rem;">
                Alphanumeric characters, dashes, and underscores only. Min {{ \App\Services\LinkService::ANONYMOUS_MIN_SLUG_LENGTH }} chars for guests —
                <a href="{{ route('login') }}">log in</a> for shorter slugs and analytics.
            </small>
            @error('slug') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Shorten</button>
        </div>
    </form>
</div>
