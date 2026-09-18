<?php

namespace App\Services;

use App\Jobs\RecordClick;
use App\Models\Link;
use Illuminate\Http\Request;

class ClickTrackerService
{
    /**
     * Track a click on a link (dispatched to queue).
     *
     * @param  bool  $isDirectUrl  True for href.nz/url/* redirects (admin-only visibility)
     */
    public function track(Link $link, Request $request, bool $isDirectUrl = false): void
    {
        RecordClick::dispatch(
            linkId: $link->id,
            referrer: $request->header('Referer'),
            userAgent: $request->userAgent(),
            ipHash: $request->ip() ? hash('sha256', $request->ip()) : null,
            isDirectUrl: $isDirectUrl,
        );
    }
}
