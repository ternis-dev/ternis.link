<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesLinkGroups;
use App\Support\DomainUrls;
use App\Support\PublicDashboard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Link groups on the public dashboard (my.ternis.link / my.href.nz).
 *
 * Same groups as on dash — only the links that may be put into them
 * differ: the public dashboard manages the href.nz, meinlink.at,
 * href.yt and qr.href.nz shortener links.
 */
class PublicGroupController extends Controller
{
    use ManagesLinkGroups;

    protected function groupViews(): string
    {
        return PublicDashboard::viewNamespace().'.groups';
    }

    protected function groupRoutePrefix(): string
    {
        // Host-aware: the alias host registers its own route names.
        return PublicDashboard::routePrefix();
    }

    protected function groupScope(): string
    {
        return 'public';
    }

    /**
     * Only the public shortener links may be grouped here — a link
     * from the dash side would never show up on this dashboard.
     */
    protected function groupLinksQuery($user): HasMany|Builder
    {
        return $user->links()->notRemoved()
            ->whereHas('domain', fn (Builder $q) => $q->whereIn('hostname', DomainUrls::publicDashboardHostnames()));
    }
}
