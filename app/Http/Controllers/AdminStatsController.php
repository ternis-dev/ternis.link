<?php

namespace App\Http\Controllers;

use App\Services\AdminStatsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminStatsController extends Controller
{
    public function __construct(
        protected AdminStatsService $statsService
    ) {}

    /**
     * Stats hub — defaults to overview.
     */
    public function index(Request $request)
    {
        return $this->show($request, 'overview');
    }

    /**
     * Show specific stats page: overview, errors, activities, links, clicks, ratio.
     */
    public function show(Request $request, string $section)
    {
        $normalized = match (strtolower($section)) {
            'overview' => 'overview',
            'errors', 'error' => 'errors',
            'activities', 'activity', 'audit' => 'activities',
            'links', 'link' => 'links',
            'clicks', 'click', 'link-clicks' => 'clicks',
            'ratio', 'ratios', 'link-clicks-ratio' => 'ratio',
            default => null,
        };

        if ($normalized === null) {
            throw new NotFoundHttpException("Stats section [{$section}] not found.");
        }

        $params = $request->query();

        $data = match ($normalized) {
            'overview' => $this->statsService->getOverviewStats($params),
            'errors' => $this->statsService->getErrorStats($params),
            'activities' => $this->statsService->getActivityStats($params),
            'links' => $this->statsService->getLinkStats($params),
            'clicks' => $this->statsService->getClickStats($params),
            'ratio' => $this->statsService->getRatioStats($params),
        };

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json([
                'section' => $normalized,
                'data' => $data,
            ]);
        }

        return view("admin.stats.{$normalized}", [
            'section' => $normalized,
            'stats' => $data,
            'params' => $params,
        ]);
    }
}
