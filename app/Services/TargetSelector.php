<?php

namespace App\Services;

use App\Models\Link;
use App\Models\LinkTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TargetSelector
{
    public function __construct(
        private DeviceDetector $devices,
    ) {}

    public static function cacheKey(string $linkId): string
    {
        return "link-targets:{$linkId}";
    }

    public static function forgetCached(string $linkId): void
    {
        Cache::forget(self::cacheKey($linkId));
    }

    /**
     * @return list<LinkTarget>
     */
    public function targetsFor(Link $link): array
    {
        $key = self::cacheKey($link->id);
        $cached = Cache::get($key);

        if (is_array($cached)) {
            $targets = [];
            foreach ($cached as $attrs) {
                $t = LinkTarget::hydrate([$attrs])->first();
                if ($t) {
                    $t->setAttribute('link_id', $link->id);
                    $targets[] = $t;
                }
            }

            return $targets;
        }

        $targets = LinkTarget::where('link_id', $link->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->all();

        Cache::put($key, array_map(fn (LinkTarget $t) => $t->getAttributes(), $targets), LinkService::RESOLVE_CACHE_TTL);

        return $targets;
    }

    public function pick(Link $link, Request $request): ?LinkTarget
    {
        $targets = array_filter(
            $this->targetsFor($link),
            fn (LinkTarget $t) => $t->is_active && $t->weight > 0
        );

        if ($targets === []) {
            return null;
        }

        $country = strtoupper((string) ($request->attributes->get('geo_country') ?? ''));
        if ($country === '' && $request->attributes->has('geoip')) {
            $geo = $request->attributes->get('geoip');
            $country = strtoupper((string) (is_array($geo) ? ($geo['country_code'] ?? '') : ''));
        }
        $device = $this->devices->detect($request->userAgent());

        $scored = [];
        foreach ($targets as $t) {
            $codes = array_map('strtoupper', (array) ($t->country_codes ?? []));
            $matchesCountry = $codes !== [] && $country !== '' && in_array($country, $codes, true);
            $matchesDevice = $t->device !== null && $t->device === $device;

            $hasConstraint = $codes !== [] || $t->device !== null;
            $score = ($matchesCountry ? 2 : 0) + ($matchesDevice ? 1 : 0);

            if (! $hasConstraint || $score > 0) {
                $scored[] = ['target' => $t, 'score' => $score, 'constrained' => $hasConstraint];
            }
        }

        if ($scored === []) {
            return null;
        }

        $best = max(array_column($scored, 'score'));
        $pool = array_values(array_filter($scored, fn ($s) => $s['score'] === $best));

        // Prefer constrained matches over the unconstrained rotation pool on ties,
        // except when nothing matched at all (score 0 → pure rotation).
        if ($best > 0) {
            $constrained = array_values(array_filter($pool, fn ($s) => $s['constrained']));
            if ($constrained !== []) {
                $pool = $constrained;
            }
        }

        $total = array_sum(array_map(fn ($s) => $s['target']->weight, $pool));
        if ($total <= 0) {
            return $pool[0]['target'];
        }

        $hash = crc32((string) $request->ip().'|'.date('Y-m-d').'|'.$link->id);
        $cursor = $hash % $total;

        foreach ($pool as $s) {
            $cursor -= $s['target']->weight;
            if ($cursor < 0) {
                return $s['target'];
            }
        }

        return $pool[0]['target'];
    }
}
