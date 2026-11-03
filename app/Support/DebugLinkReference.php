<?php

namespace App\Support;

use App\Models\Link;
use Illuminate\Validation\ValidationException;

class DebugLinkReference
{
    public static function resolve(string $reference): ?Link
    {
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        $query = Link::with('domain')->where('is_removed', false);
        if (str_contains($reference, '/')) {
            $parts = parse_url(str_contains($reference, '://') ? $reference : 'https://'.$reference);
            $host = strtolower($parts['host'] ?? '');
            $slug = ltrim($parts['path'] ?? '', '/');
            $query->whereHas('domain', fn ($q) => $q->where('hostname', $host))->where('slug', $slug);
        } else {
            $byId = (clone $query)->find($reference);
            if ($byId) {
                return $byId;
            }
            $query->where('slug', $reference);
        }

        $matches = $query->limit(2)->get();
        if ($matches->count() !== 1) {
            throw ValidationException::withMessages([
                'link_slug_or_id' => 'Enter an existing short link (domain/path) or link ID. Bare slugs must match exactly one domain.',
            ]);
        }

        return $matches->first();
    }
}
