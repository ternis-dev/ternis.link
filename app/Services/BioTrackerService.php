<?php

namespace App\Services;

use App\Jobs\RecordBioEvent;
use App\Models\BioButton;
use App\Models\BioPage;
use App\Support\IpHash;
use Illuminate\Http\Request;

class BioTrackerService
{
    public function __construct(
        private GeoIpService $geoIp,
    ) {}

    public function trackView(BioPage $page, Request $request): void
    {
        $geo = $this->geoIp->lookup($request);

        RecordBioEvent::dispatch(
            bioPageId: $page->id,
            kind: 'view',
            referrer: $request->header('Referer'),
            userAgent: $request->userAgent(),
            ipHash: IpHash::make($request->ip()),
            countryCode: $geo['country_code'],
            city: $geo['city'],
        );
    }

    public function trackTap(BioPage $page, BioButton $button, Request $request): void
    {
        $geo = $this->geoIp->lookup($request);

        RecordBioEvent::dispatch(
            bioPageId: $page->id,
            kind: 'tap',
            bioButtonId: $button->id,
            referrer: $request->header('Referer'),
            userAgent: $request->userAgent(),
            ipHash: IpHash::make($request->ip()),
            countryCode: $geo['country_code'],
            city: $geo['city'],
        );
    }
}
