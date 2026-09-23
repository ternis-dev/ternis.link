<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLinkRequest;
use App\Http\Requests\UpdateLinkRequest;
use App\Models\Domain;
use App\Models\Link;
use App\Services\LinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    public function __construct(
        private LinkService $linkService,
    ) {}

    /**
     * GET /v1/links — List the authenticated user's links.
     */
    public function index(Request $request): JsonResponse
    {
        $links = $request->user()
            ->links()
            ->with('domain')
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

        $link = $this->linkService->create(
            destinationUrl: $request->validated('destination_url'),
            domain: $domain,
            user: $request->user(),
            customSlug: $request->validated('slug'),
            expiresAt: $request->validated('expires_at') ? new \DateTime($request->validated('expires_at')) : null,
        );

        return response()->json($link->load('domain'), 201);
    }

    /**
     * GET /v1/links/{link} — Get a specific link.
     */
    public function show(Request $request, Link $link): JsonResponse
    {
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
        if ($link->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'You do not own this link.');
        }

        $link = $this->linkService->update($link, $request->validated());

        return response()->json($link->load('domain'));
    }

    /**
     * DELETE /v1/links/{link} — Deactivate a link.
     */
    public function destroy(Request $request, Link $link): JsonResponse
    {
        if ($link->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'You do not own this link.');
        }

        $this->linkService->deactivate($link);

        return response()->json(null, 204);
    }
}
