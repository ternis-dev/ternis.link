<?php

namespace App\Services;

use App\Jobs\RecordBioEvent;
use App\Models\BioButton;
use App\Models\BioEvent;
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

    /**
     * Record an RSVP headcount (one per visitor hash; duplicates are
     * filtered by the caller before dispatch).
     */
    public function trackRsvp(BioPage $page, BioButton $button, Request $request): void
    {
        $geo = $this->geoIp->lookup($request);

        RecordBioEvent::dispatch(
            bioPageId: $page->id,
            kind: 'rsvp',
            bioButtonId: $button->id,
            referrer: $request->header('Referer'),
            userAgent: $request->userAgent(),
            ipHash: IpHash::make($request->ip()),
            countryCode: $geo['country_code'],
            city: $geo['city'],
        );
    }

    public function hasRsvpd(BioButton $button, Request $request): bool
    {
        $hash = IpHash::make($request->ip());

        if ($hash === null) {
            return false;
        }

        return BioEvent::where('bio_button_id', $button->id)
            ->where('kind', 'rsvp')
            ->where('ip_hash', $hash)
            ->exists();
    }
}
