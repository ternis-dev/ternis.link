<?php

namespace App\Http\Controllers\Concerns;

use App\Support\DomainUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Strict hostname partition for the public dashboards (new + legacy):
 * only links on href.nz, meinlink.at, href.yt (+ qr.href.nz). Everything
 * else (clicked.at, ternis.link, href.re, partner, custom, domain-less)
 * lives on dash.ternis.link — see DashboardController::personalQuery().
 */
trait ScopesPublicLinks
{
    /**
     * @return HasMany|Builder
     */
    private function publicLinksQuery(object $user): object
    {
        return $user->links()->notRemoved()->whereHas(
            'domain',
            fn (Builder $q) => $q->whereIn('hostname', DomainUrls::publicDashboardHostnames())
        );
    }
}
