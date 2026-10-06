<?php

namespace App\Http\Controllers;

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

    protected string $routes = 'public-dashboard.legacy';

    /**
     * Legacy overview: no preference bounce here (this IS the legacy
     * home), same data as the new dashboard.
     */
    public function index(Request $request)
    {
        return view($this->views.'.index', $this->overview($request->user()));
    }

    /**
     * Leave the legacy dashboard: clear the opt-in and land on the
     * new public dashboard home.
     */
    public function switchToNew(Request $request)
    {
        $request->user()->update(['public_dashboard_legacy' => false]);

        return redirect()->route('public-dashboard');
    }
}
