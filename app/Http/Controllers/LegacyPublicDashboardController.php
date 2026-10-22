<?php

namespace App\Http\Controllers;

use App\Support\PublicDashboard;
use Illuminate\Http\Request;

/**
 * Legacy public dashboard (my.ternis.link/_legacy/*) — the previous
 * indigo UI over the same links, stats, exports, and partition as the
 * new public dashboard. Users opt in per account
 * (`users.public_dashboard_legacy`) and switch back any time.
 */
class LegacyPublicDashboardController extends PublicDashboardController
{
    protected string $views = 'public-dashboard.legacy';

    /**
     * Legacy routes live under the host-aware public dashboard prefix.
     */
    protected function routePrefix(): string
    {
        return PublicDashboard::routePrefix().'legacy.';
    }

    /**
     * Legacy overview: no preference bounce here (this IS the legacy
     * home), same data as the new dashboard.
     */
    public function index(Request $request)
    {
        // The my.href.nz alias host has its own design — the previous
        // indigo UI is not offered there.
        if (! PublicDashboard::hasLegacyUi()) {
            return redirect()->route(rtrim(PublicDashboard::routePrefix(), '.'));
        }

        return view($this->views.'.index', $this->overview($request->user()));
    }

    /**
     * The account sections (API keys, domains, settings) only exist in
     * the current UI — the legacy views were never written for them.
     * Guard the inherited actions so a future route 404s instead of
     * rendering a missing view.
     */
    public function apiKeys()
    {
        abort(404);
    }

    public function domains()
    {
        abort(404);
    }

    public function settings()
    {
        abort(404);
    }

    /**
     * Leave the legacy dashboard: clear the opt-in and land on the
     * new public dashboard home.
     */
    public function switchToNew(Request $request)
    {
        $request->user()->update(['public_dashboard_legacy' => false]);

        return redirect()->route(rtrim(PublicDashboard::routePrefix(), '.'));
    }
}
