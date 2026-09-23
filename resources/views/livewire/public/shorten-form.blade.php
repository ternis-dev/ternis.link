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

        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1rem;">
            Guests get an auto-generated 8-character link.
            <a href="{{ \App\Support\DomainUrls::dashboard('/login') }}">Log in</a> for custom slugs, shorter links and analytics.
        </p>

        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Shorten</button>
        </div>
    </form>
</div>
