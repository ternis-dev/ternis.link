<?php

namespace App\Services;

use App\Jobs\RecordClick;
use App\Models\Link;
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
    public function track(Link $link, Request $request, bool $isDirectUrl = false): void
    {
        $geo = $this->geoIp->lookup($request);

        RecordClick::dispatch(
            linkId: $link->id,
            referrer: $request->header('Referer'),
            userAgent: $request->userAgent(),
            ipHash: IpHash::make($request->ip()),
            isDirectUrl: $isDirectUrl,
            countryCode: $geo['country_code'],
            city: $geo['city'],
            ip: IpCapture::enabled() ? $request->ip() : null,
        );
    }
}
