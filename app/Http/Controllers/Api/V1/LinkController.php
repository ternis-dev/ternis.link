<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLinkRequest;
use App\Http\Requests\UpdateLinkRequest;
use App\Models\ActivityLog;
use App\Models\Domain;
use App\Models\Link;
use App\Services\LinkService;
use App\Support\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * links carrying that exact tag.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $adminAll = $user->isAdmin() && $request->query('scope', 'all') === 'all';

        $tag = strtolower(trim((string) $request->query('tag', '')));

        $links = ($adminAll ? Link::query() : $user->links())->notRemoved()
            ->with($adminAll ? ['domain', 'user'] : 'domain')
            ->when($tag !== '', fn ($query) => $query->where('tags', 'like', '%"'.$tag.'"%'))
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($links);
    }

    /**
     * POST /v1/links — Create a new short link.
     */
    public function store(StoreLinkRequest $request): JsonResponse
    {
        $domain = Domain::findOrFail($request->validated('domain_id'));
        $user = $request->user();
        $customSlug = $request->validated('slug');

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
        );

        Activity::record(ActivityLog::LINK_CREATED, $user, $link, [
            'slug' => $link->slug,
            'domain' => $domain->hostname,
            'via' => 'api',
        ]);

        return response()->json($link->load('domain'), 201);
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

        return response()->json($link->load('domain'));
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

        Activity::record(ActivityLog::LINK_UPDATED, $request->user(), $link, [
            'slug' => $link->slug,
            'via' => 'api',
        ]);

        return response()->json($link->load('domain'));
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

        Activity::record(ActivityLog::LINK_DEACTIVATED, $request->user(), $link, [
            'slug' => $link->slug,
            'via' => 'api',
        ]);

        return response()->json(null, 204);
    }
}
