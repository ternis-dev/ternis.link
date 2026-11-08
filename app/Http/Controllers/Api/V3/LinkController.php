<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLinkRequest;
use App\Http\Requests\UpdateLinkRequest;
use App\Models\ActivityLog;
use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\Link;
use App\Services\LinkService;
use App\Support\Activity;
use App\Support\LinkQrCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LinkController extends Controller
{
    public function __construct(
        private LinkService $linkService,
    ) {}

    /**
     * GET /v3/links — List links for the authenticated user (or all if admin).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $adminAll = $user->isAdmin() && $request->query('scope', 'mine') === 'all';

        $query = ($adminAll ? Link::query() : $user->links())->notRemoved()
            ->with(['domain', 'apiKey:id,name,key_prefix']);

        if ($request->filled('q') || $request->filled('search')) {
            $search = trim((string) ($request->query('q') ?? $request->query('search')));
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'like', "%{$search}%")
                    ->orWhere('destination_url', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tag')) {
            $tag = strtolower(trim((string) $request->query('tag')));
            $query->where('tags', 'like', '%"'.$tag.'"%');
        }

        if ($request->filled('domain')) {
            $domainFilter = trim((string) $request->query('domain'));
            $query->whereHas('domain', function ($q) use ($domainFilter) {
                $q->where('hostname', $domainFilter)->orWhere('id', $domainFilter);
            });
        }

        if ($request->query('status') === 'active') {
            $query->where('is_active', true)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
        } elseif ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        } elseif ($request->query('status') === 'expired') {
            $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
        }

        $perPage = min(100, max(1, $request->integer('per_page', 25)));
        $paginator = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'links' => collect($paginator->items())->map(fn (Link $link) => $this->formatLink($link)),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    /**
     * POST /v3/links — Create a new short link.
     */
    public function store(StoreLinkRequest $request): JsonResponse
    {
        $domainId = $request->validated('domain_id');

        if (! $request->user()->availableDomains()->contains('id', $domainId)) {
            throw ValidationException::withMessages([
                'domain' => 'The selected domain is not available for your account. Check GET /v3/domains for available domains.',
            ]);
        }

        $domain = Domain::findOrFail($domainId);
        $user = $request->user();
        $customSlug = $request->validated('slug');

        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');

        $generatedLength = $customSlug === null
            && $request->canChooseSlugLength()
            && $request->validated('slug_length') !== null
            ? (int) $request->validated('slug_length')
            : null;

        $link = $this->linkService->create(
            destinationUrl: $request->validated('destination_url'),
            domain: $domain,
            user: $user,
            customSlug: $customSlug,
            expiresAt: $request->validated('expires_at') ? new \DateTime($request->validated('expires_at')) : null,
            generatedLength: $generatedLength,
            description: $request->validated('description'),
            tags: $request->validated('tags'),
            apiKey: $apiKey,
            ogTitle: $request->validated('og_title'),
            ogDescription: $request->validated('og_description'),
            ogImageUrl: $request->validated('og_image_url'),
            password: $request->validated('password'),
            utm: [
                'utm_source' => $request->validated('utm_source'),
                'utm_medium' => $request->validated('utm_medium'),
                'utm_campaign' => $request->validated('utm_campaign'),
            ],
            userTrackingEnabled: (bool) $request->validated('user_tracking_enabled', false),
            botProtection: (bool) $request->validated('bot_protection', false),
            allowBots: (bool) $request->validated('allow_bots', false),
            healthCheckEnabled: (bool) $request->validated('health_check_enabled', false),
        );

        Activity::record(ActivityLog::LINK_CREATED, $user, $link, array_filter([
            'slug' => $link->slug,
            'domain' => $domain->hostname,
            'via' => 'api_v3',
            'auth_via' => $request->attributes->get('auth_via', 'api-key'),
            'api_key_id' => $apiKey?->getKey(),
            'api_key_name' => $apiKey?->name,
            'api_key_prefix' => $apiKey?->key_prefix,
        ], fn ($value) => $value !== null));

        return response()->json($this->formatLink($link->load(['domain', 'apiKey:id,name,key_prefix'])), 201);
    }

    /**
     * POST /v3/links/bulk — Batch create multiple short links (up to 50).
     */
    public function bulk(Request $request): JsonResponse
    {
        $request->validate([
            'links' => ['required', 'array', 'min:1', 'max:50'],
        ]);

        $user = $request->user();
        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');
        $availableDomains = $user->availableDomains();
        $defaultDomain = $apiKey?->resolvedPreferredDomain() ?? $user->resolvedDefaultDomain();

        $items = $request->input('links');
        $results = [];
        $succeeded = 0;
        $failed = 0;

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                $results[] = [
                    'index' => $index,
                    'status' => 'failed',
                    'errors' => ['Item must be an object/associative array.'],
                ];
                $failed++;
                continue;
            }

            // Normalize url
            $destUrl = $item['destination_url'] ?? $item['url'] ?? null;

            // Normalize domain
            $targetDomain = null;
            if (! empty($item['domain'])) {
                $targetDomain = $availableDomains->first(fn ($d) => $d->hostname === $item['domain'] || $d->id === $item['domain']);
            } elseif (! empty($item['domain_id'])) {
                $targetDomain = $availableDomains->first(fn ($d) => $d->id === $item['domain_id']);
            } else {
                $targetDomain = $defaultDomain;
            }

            if (! $targetDomain) {
                $results[] = [
                    'index' => $index,
                    'status' => 'failed',
                    'errors' => ['Valid and permitted domain could not be resolved.'],
                ];
                $failed++;
                continue;
            }

            // Parse tags
            $tags = $item['tags'] ?? null;
            if (is_string($tags)) {
                $tags = array_values(array_filter(array_map('trim', explode(',', $tags))));
            }

            // Parse expires
            $expiresAt = null;
            if (! empty($item['expires_at'])) {
                try {
                    $expiresAt = new \DateTime($item['expires_at']);
                } catch (\Throwable) {
                    $expiresAt = null;
                }
            } elseif (! empty($item['expires_in'])) {
                $expiresAt = $this->parseDurationToDateTime($item['expires_in']);
            }

            // Parse UTM
            $utm = null;
            if (isset($item['utm']) && is_array($item['utm'])) {
                $utm = [
                    'utm_source' => $item['utm']['source'] ?? $item['utm']['utm_source'] ?? null,
                    'utm_medium' => $item['utm']['medium'] ?? $item['utm']['utm_medium'] ?? null,
                    'utm_campaign' => $item['utm']['campaign'] ?? $item['utm']['utm_campaign'] ?? null,
                ];
            } else {
                $utm = [
                    'utm_source' => $item['utm_source'] ?? null,
                    'utm_medium' => $item['utm_medium'] ?? null,
                    'utm_campaign' => $item['utm_campaign'] ?? null,
                ];
            }

            // Validate
            $validator = Validator::make([
                'destination_url' => $destUrl,
                'slug' => $item['slug'] ?? null,
                'tags' => $tags,
                'description' => $item['description'] ?? null,
            ], [
                'destination_url' => ['required', 'url', 'max:2048'],
                'slug' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9_-]+$/', 'max:255'],
                'tags' => ['nullable', 'array', 'max:10'],
                'tags.*' => ['string', 'max:30', 'regex:/^[a-z0-9][a-z0-9-]{0,28}[a-z0-9]$/'],
                'description' => ['nullable', 'string', 'max:500'],
            ]);

            if ($validator->fails()) {
                $results[] = [
                    'index' => $index,
                    'status' => 'failed',
                    'errors' => $validator->errors()->all(),
                ];
                $failed++;
                continue;
            }

            try {
                $link = $this->linkService->create(
                    destinationUrl: $destUrl,
                    domain: $targetDomain,
                    user: $user,
                    customSlug: $item['slug'] ?? null,
                    expiresAt: $expiresAt,
                    description: $item['description'] ?? null,
                    tags: $tags,
                    apiKey: $apiKey,
                    utm: $utm,
                    password: $item['password'] ?? null,
                    botProtection: (bool) ($item['bot_protection'] ?? false),
                );

                $results[] = [
                    'index' => $index,
                    'status' => 'created',
                    'data' => $this->formatLink($link->load(['domain', 'apiKey:id,name,key_prefix'])),
                ];
                $succeeded++;
            } catch (\Throwable $e) {
                $results[] = [
                    'index' => $index,
                    'status' => 'failed',
                    'errors' => [$e->getMessage()],
                ];
                $failed++;
            }
        }

        $statusCode = $failed === 0 ? 201 : ($succeeded > 0 ? 207 : 422);

        return response()->json([
            'summary' => [
                'total' => count($items),
                'succeeded' => $succeeded,
                'failed' => $failed,
            ],
            'items' => $results,
        ], $statusCode);
    }

    /**
     * GET /v3/links/{link} — Get link details.
     */
    public function show(Request $request, Link $link): JsonResponse
    {
        $this->authorizeAccess($request, $link);

        return response()->json($this->formatLink($link->load(['domain', 'apiKey:id,name,key_prefix', 'targets'])));
    }

    /**
     * PUT/PATCH /v3/links/{link} — Update an existing link.
     */
    public function update(UpdateLinkRequest $request, Link $link): JsonResponse
    {
        $this->authorizeAccess($request, $link);

        $validated = $request->validated();
        $targets = $validated['targets'] ?? null;
        unset($validated['targets']);

        $updatedLink = $this->linkService->update($link, $validated, $request->user());

        if ($targets !== null) {
            $this->linkService->syncTargets($updatedLink, $targets, $request->user());
            $updatedLink = $updatedLink->fresh();
        }

        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');

        Activity::record(ActivityLog::LINK_UPDATED, $request->user(), $updatedLink, array_filter([
            'slug' => $updatedLink->slug,
            'via' => 'api_v3',
            'auth_via' => $request->attributes->get('auth_via', 'api-key'),
            'api_key_id' => $apiKey?->getKey(),
            'api_key_prefix' => $apiKey?->key_prefix,
        ], fn ($value) => $value !== null));

        return response()->json($this->formatLink($updatedLink->load(['domain', 'apiKey:id,name,key_prefix', 'targets'])));
    }

    /**
     * DELETE /v3/links/{link} — Deactivate a link.
     */
    public function destroy(Request $request, Link $link): JsonResponse
    {
        $this->authorizeAccess($request, $link);

        $this->linkService->deactivate($link);

        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');

        Activity::record(ActivityLog::LINK_DEACTIVATED, $request->user(), $link, array_filter([
            'slug' => $link->slug,
            'via' => 'api_v3',
            'auth_via' => $request->attributes->get('auth_via', 'api-key'),
            'api_key_id' => $apiKey?->getKey(),
            'api_key_prefix' => $apiKey?->key_prefix,
        ], fn ($value) => $value !== null));

        return response()->json([
            'message' => 'Link deactivated successfully.',
            'id' => $link->id,
            'slug' => $link->slug,
        ]);
    }

    /**
     * GET /v3/links/{link}/analytics — Comprehensive analytics summary.
     */
    public function analytics(Request $request, Link $link): JsonResponse
    {
        $this->authorizeAccess($request, $link);

        $baseQuery = $link->clicks()
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('is_direct_url', false));

        $totalClicks = (clone $baseQuery)->count();
        $uniqueVisitors = (clone $baseQuery)->distinct('ip_hash')->count('ip_hash');
        $clicks24h = (clone $baseQuery)->where('created_at', '>=', now()->subHours(24))->count();
        $clicks7d = (clone $baseQuery)->where('created_at', '>=', now()->subDays(7))->count();
        $clicks30d = (clone $baseQuery)->where('created_at', '>=', now()->subDays(30))->count();

        $topReferrers = (clone $baseQuery)
            ->selectRaw('referrer, COUNT(*) as count')
            ->whereNotNull('referrer')
            ->groupBy('referrer')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['referrer' => $r->referrer, 'clicks' => (int) $r->count]);

        $topCountries = (clone $baseQuery)
            ->selectRaw('country_code, COUNT(*) as count')
            ->whereNotNull('country_code')
            ->groupBy('country_code')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($c) => ['country_code' => $c->country_code, 'clicks' => (int) $c->count]);

        $timeline = (clone $baseQuery)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get()
            ->map(fn ($t) => ['date' => (string) $t->date, 'clicks' => (int) $t->count]);

        return response()->json([
            'link' => [
                'id' => $link->id,
                'slug' => $link->slug,
                'short_url' => LinkQrCode::shortUrl($link),
            ],
            'summary' => [
                'total_clicks' => $totalClicks,
                'unique_visitors' => $uniqueVisitors,
                'last_24_hours' => $clicks24h,
                'last_7_days' => $clicks7d,
                'last_30_days' => $clicks30d,
            ],
            'top_referrers' => $topReferrers,
            'top_countries' => $topCountries,
            'timeline' => $timeline,
        ]);
    }

    /**
     * GET /v3/links/{link}/qr — Dynamic vector SVG or raster PNG QR code.
     */
    public function qr(Request $request, Link $link): Response
    {
        $this->authorizeAccess($request, $link);

        $format = strtolower((string) $request->query('format', 'svg'));
        $size = min(1200, max(100, $request->integer('size', 300)));
        $download = $request->boolean('download');

        if ($format === 'png') {
            $content = LinkQrCode::png($link, $size);
            $mime = 'image/png';
            $filename = "qr-{$link->slug}.png";
        } else {
            $content = LinkQrCode::svg($link);
            $mime = 'image/svg+xml';
            $filename = "qr-{$link->slug}.svg";
        }

        $headers = [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ];

        if ($download) {
            $headers['Content-Disposition'] = "attachment; filename=\"{$filename}\"";
        }

        return response($content, 200, $headers);
    }

    /**
     * Authorize user access to the link.
     */
    private function authorizeAccess(Request $request, Link $link): void
    {
        if ($link->is_removed) {
            abort(404, 'Link not found.');
        }

        if ($link->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'You do not own this link.');
        }
    }

    /**
     * Formats a link model into an intuitive API v3 representation.
     */
    public function formatLink(Link $link): array
    {
        $domain = $link->domain;
        $hostname = $domain?->hostname ?? 'href.nz';
        $shortUrl = "https://{$hostname}/{$link->slug}";

        return [
            'id' => $link->id,
            'slug' => $link->slug,
            'short_url' => $shortUrl,
            'destination_url' => $link->destination_url,
            'domain' => [
                'id' => $domain?->id,
                'hostname' => $hostname,
            ],
            'description' => $link->description,
            'tags' => $link->tags ?? [],
            'is_active' => (bool) $link->is_active,
            'is_expired' => $link->isExpired(),
            'clicks_count' => (int) ($link->clicks_count ?? $link->clicks()->count()),
            'password_protected' => ! empty($link->password),
            'bot_protection' => (bool) $link->bot_protection,
            'utm' => array_filter([
                'source' => $link->utm_source,
                'medium' => $link->utm_medium,
                'campaign' => $link->utm_campaign,
            ]),
            'og' => array_filter([
                'title' => $link->og_title,
                'description' => $link->og_description,
                'image_url' => $link->og_image_url,
            ]),
            'expires_at' => $link->expires_at?->toIso8601String(),
            'created_at' => $link->created_at?->toIso8601String(),
            'updated_at' => $link->updated_at?->toIso8601String(),
            'qr_code_url' => url("/v3/links/{$link->id}/qr"),
            'analytics_url' => url("/v3/links/{$link->id}/analytics"),
        ];
    }

    private function parseDurationToDateTime(mixed $val): ?\DateTime
    {
        if (is_numeric($val)) {
            return (new \DateTime)->modify("+".(int) $val." seconds");
        }
        if (is_string($val) && preg_match('/^(\d+)\s*(s|sec|m|min|h|hours?|d|days?|w|weeks?)$/i', trim($val), $m)) {
            $num = (int) $m[1];
            $unit = match (strtolower($m[2])) {
                's', 'sec' => 'seconds',
                'm', 'min' => 'minutes',
                'h', 'hour', 'hours' => 'hours',
                'd', 'day', 'days' => 'days',
                'w', 'week', 'weeks' => 'weeks',
                default => 'days',
            };
            return (new \DateTime)->modify("+{$num} {$unit}");
        }
        return null;
    }
}
