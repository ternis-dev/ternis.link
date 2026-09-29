<?php

namespace App\Http\Controllers\Api\V1;

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
use Symfony\Component\HttpFoundation\Response;

class LinkController extends Controller
{
    public function __construct(
        private LinkService $linkService,
    ) {}

    /**
     * GET /v1/links — List links.
     *
     * Admins list ALL links by default (`?scope=mine` restricts to their
     * own); regular users list their own links only. `?tag=` filters to
     * links carrying that exact tag. `?api_key_id=` filters to links
     * created with one API key (`none` = dashboard-created, i.e. no
     * key involved).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $adminAll = $user->isAdmin() && $request->query('scope', 'all') === 'all';

        $tag = strtolower(trim((string) $request->query('tag', '')));
        $apiKeyFilter = trim((string) $request->query('api_key_id', $request->query('api_key', '')));

        $links = ($adminAll ? Link::query() : $user->links())->notRemoved()
            ->with($adminAll ? ['domain', 'user', 'apiKey:id,name,key_prefix'] : ['domain', 'apiKey:id,name,key_prefix'])
            ->when($tag !== '', fn ($query) => $query->where('tags', 'like', '%"'.$tag.'"%'))
            ->when($apiKeyFilter !== '', function ($query) use ($apiKeyFilter, $user, $adminAll) {
                if ($apiKeyFilter === 'none') {
                    $query->whereNull('api_key_id');
                } else {
                    // Non-admins may only filter by their own keys.
                    if (! $adminAll && ! $user->apiKeys()->whereKey($apiKeyFilter)->exists()) {
                        abort(403, 'You do not own this API key.');
                    }
                    $query->where('api_key_id', $apiKeyFilter);
                }
            })
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($links);
    }

    /**
     * POST /v1/links — Create a new short link.
     *
     * The resolving API key (if any) is stored on the link
     * (links.api_key_id) and in the activity metadata so every
     * token-created link stays attributable.
     */
    public function store(StoreLinkRequest $request): JsonResponse
    {
        $domain = Domain::findOrFail($request->validated('domain_id'));
        $user = $request->user();
        $customSlug = $request->validated('slug');

        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');

        // A custom slug always wins; the picker only sizes generated ones.
        // Unvalidated (ineligible) values never reach the service.
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
        );

        Activity::record(ActivityLog::LINK_CREATED, $user, $link, array_filter([
            'slug' => $link->slug,
            'domain' => $domain->hostname,
            'via' => 'api',
            'auth_via' => $request->attributes->get('auth_via', 'api-key'),
            'api_key_id' => $apiKey?->getKey(),
            'api_key_name' => $apiKey?->name,
            'api_key_prefix' => $apiKey?->key_prefix,
        ], fn ($value) => $value !== null));

        return response()->json($link->load(['domain', 'apiKey:id,name,key_prefix']), 201);
    }

    /**
     * GET /v1/links/{link} — Get a specific link.
     */
    public function show(Request $request, Link $link): JsonResponse
    {
        if ($link->is_removed) {
            abort(404);
        }

        if ($link->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'You do not own this link.');
        }

        return response()->json($link->load(['domain', 'apiKey:id,name,key_prefix']));
    }

    /**
     * GET /v1/links/{link}/qr — Return a QR code for a link.
     *
     * SVG is the default format; PNG can be requested with ?format=png.
     */
    public function qr(Request $request, Link $link): Response
    {
        $this->authorizeQrAccess($request, $link);

        $format = strtolower((string) $request->query('format', 'svg'));

        if (! in_array($format, ['svg', 'png'], true)) {
            return response()->json([
                'message' => 'The format must be svg or png.',
            ], 422);
        }

        $link->load('domain');

        if ($format === 'png') {
            return response(LinkQrCode::png($link), 200, [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'inline; filename="qr-'.$link->slug.'.png"',
            ]);
        }

        return response(LinkQrCode::svg($link), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="qr-'.$link->slug.'.svg"',
        ]);
    }

    /**
     * PUT /v1/links/{link} — Update a link.
     */
    public function update(UpdateLinkRequest $request, Link $link): JsonResponse
    {
        if ($link->is_removed) {
            abort(404);
        }

        if ($link->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'You do not own this link.');
        }

        $link = $this->linkService->update($link, $request->validated());

        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');

        Activity::record(ActivityLog::LINK_UPDATED, $request->user(), $link, array_filter([
            'slug' => $link->slug,
            'via' => 'api',
            'auth_via' => $request->attributes->get('auth_via', 'api-key'),
            'api_key_id' => $apiKey?->getKey(),
            'api_key_prefix' => $apiKey?->key_prefix,
        ], fn ($value) => $value !== null));

        return response()->json($link->load(['domain', 'apiKey:id,name,key_prefix']));
    }

    /**
     * DELETE /v1/links/{link} — Deactivate a link.
     */
    public function destroy(Request $request, Link $link): JsonResponse
    {
        if ($link->is_removed) {
            abort(404);
        }

        if ($link->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'You do not own this link.');
        }

        $this->linkService->deactivate($link);

        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');

        Activity::record(ActivityLog::LINK_DEACTIVATED, $request->user(), $link, array_filter([
            'slug' => $link->slug,
            'via' => 'api',
            'auth_via' => $request->attributes->get('auth_via', 'api-key'),
            'api_key_id' => $apiKey?->getKey(),
            'api_key_prefix' => $apiKey?->key_prefix,
        ], fn ($value) => $value !== null));

        return response()->json(null, 204);
    }

    private function authorizeQrAccess(Request $request, Link $link): void
    {
        if ($link->is_removed) {
            abort(404);
        }

        if ($link->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'You do not own this link.');
        }
    }
}
