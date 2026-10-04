<?php

namespace App\Services;

use App\Jobs\RecordClick;
use App\Models\Link;
use App\Models\LinkTarget;
use App\Support\IpCapture;
use App\Support\IpHash;
use Illuminate\Http\Request;

class ClickTrackerService
{
    public function __construct(
        private GeoIpService $geoIp,
    ) {}

    /**
     * Track a click on a link (dispatched to queue).
     *
     * @param  bool  $isDirectUrl  True for href.nz/url/* redirects (admin-only visibility)
     */
    public function track(
        Link $link,
        Request $request,
        bool $isDirectUrl = false,
        ?LinkTarget $target = null,
        ?array $queryParams = null,
        ?array $tags = null,
        ?string $userIdentifier = null,
    ): void {
        $geo = $this->geoIp->lookup($request);

        $queryParams ??= \App\Support\UserTracking::extractQueryParams($request);
        $tags ??= \App\Support\UserTracking::extractTags($request);
        $userIdentifier ??= \App\Support\UserTracking::extractUserIdentifier($request, $link);

        RecordClick::dispatch(
            linkId: $link->id,
            referrer: $request->header('Referer'),
            userAgent: $request->userAgent(),
            ipHash: IpHash::make($request->ip()),
            isDirectUrl: $isDirectUrl,
            countryCode: $geo['country_code'],
            city: $geo['city'],
            ip: IpCapture::enabled() ? $request->ip() : null,
            linkTargetId: $target?->id,
            queryParams: $queryParams,
            tags: $tags,
            userIdentifier: $userIdentifier,
        );
    }
}
