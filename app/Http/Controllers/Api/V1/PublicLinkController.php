<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DomainType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePublicLinkRequest;
use App\Models\Domain;
use App\Services\LinkService;
use App\Support\IpHash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicLinkController extends Controller
{
    public function __construct(
        private LinkService $linkService,
    ) {}

    /**
     * POST /v1/links/public — Create a short link without authentication.
     *
     * Restricted to public system domains (e.g. href.nz). Guest links
     * are always auto-generated (8 chars, no custom slug) and carry no
     * owner — attributed only via a hashed creator IP for daily quota.
     */
    public function store(StorePublicLinkRequest $request): JsonResponse
    {
        $domain = $this->resolveDomain($request);

        if (! $domain->isSystemDomain() || $domain->type !== DomainType::Public || ! $domain->isUsableForLinks()) {
            return response()->json(['message' => 'Guest links can only be created on public domains.'], 422);
        }

        $link = $this->linkService->create(
            destinationUrl: $request->validated('destination_url'),
            domain: $domain,
            user: null,
            customSlug: null,
            expiresAt: $request->validated('expires_at') ? new \DateTime($request->validated('expires_at')) : null,
            creatorIpHash: IpHash::make($request->ip()),
        );

        $link->load('domain');

        return response()->json([
            ...$link->toArray(),
            'short_url' => "https://{$domain->hostname}/{$link->slug}",
        ], 201);
    }

    /**
     * Explicit domain_id wins; otherwise use the current request domain
     * when it is public, falling back to href.nz.
     */
    private function resolveDomain(Request $request): Domain
    {
        if ($request->filled('domain_id')) {
            return Domain::findOrFail($request->input('domain_id'));
        }

        $current = $request->attributes->get('domain_model');

        if ($current instanceof Domain
            && $current->isSystemDomain()
            && $current->type === DomainType::Public
            && $current->isUsableForLinks()) {
            return $current;
        }

        return Domain::where('hostname', 'href.nz')->firstOrFail();
    }
}
