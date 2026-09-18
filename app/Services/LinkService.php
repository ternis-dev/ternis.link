<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Link;
use App\Models\User;

class LinkService
{
    public function __construct(
        private SlugGeneratorService $slugGenerator,
    ) {}

    /**
     * Create a new short link.
     */
    public function create(
        string $destinationUrl,
        Domain $domain,
        ?User $user = null,
        ?string $customSlug = null,
        ?\DateTimeInterface $expiresAt = null,
    ): Link {
        $minLength = $user?->plan?->min_slug_length ?? 8;

        $slug = $customSlug ?? $this->slugGenerator->generate($minLength, $domain->id);

        return Link::create([
            'slug' => $slug,
            'destination_url' => $destinationUrl,
            'domain_id' => $domain->id,
            'user_id' => $user?->id,
            'is_active' => true,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Resolve a slug to a link on the given domain.
     */
    public function resolveSlug(string $slug, Domain $domain): ?Link
    {
        return Link::accessible()
            ->where('domain_id', $domain->id)
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Find or create a link record for tracking direct URL redirects (/url/{url}).
     */
    public function findOrCreateDirectUrlLink(string $destinationUrl, Domain $domain): Link
    {
        $hashSlug = 'u_'.substr(hash('sha256', $destinationUrl), 0, 16);

        return Link::firstOrCreate(
            [
                'domain_id' => $domain->id,
                'slug' => $hashSlug,
            ],
            [
                'destination_url' => $destinationUrl,
                'is_active' => true,
            ]
        );
    }

    /**
     * Update a link.
     */
    public function update(Link $link, array $data): Link
    {
        $link->update($data);

        return $link->fresh();
    }

    /**
     * Soft-deactivate a link (don't hard-delete, preserve analytics).
     */
    public function deactivate(Link $link): void
    {
        $link->update(['is_active' => false]);
    }
}
