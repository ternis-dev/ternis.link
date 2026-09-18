<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Link;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClickController extends Controller
{
    /**
     * GET /v1/links/{link}/clicks — Get click analytics for a link.
     */
    public function index(Request $request, Link $link): JsonResponse
    {
        if ($link->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }

        $clicks = $link->clicks()
            ->when(! $request->user()->isAdmin(), function ($query) {
                // Non-admins cannot see direct URL redirect clicks
                $query->where('is_direct_url', false);
            })
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($clicks);
    }

    /**
     * GET /v1/links/{link}/clicks/summary — Aggregated click stats.
     */
    public function summary(Request $request, Link $link): JsonResponse
    {
        if ($link->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }

        $baseQuery = $link->clicks()
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('is_direct_url', false));

        $summary = [
            'total_clicks' => $baseQuery->count(),
            'unique_visitors' => $baseQuery->distinct('ip_hash')->count('ip_hash'),
            'top_referrers' => (clone $baseQuery)
                ->selectRaw('referrer, COUNT(*) as count')
                ->whereNotNull('referrer')
                ->groupBy('referrer')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            'top_countries' => (clone $baseQuery)
                ->selectRaw('country_code, COUNT(*) as count')
                ->whereNotNull('country_code')
                ->groupBy('country_code')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            'clicks_by_day' => (clone $baseQuery)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupByRaw('DATE(created_at)')
                ->orderBy('date')
                ->limit(30)
                ->get(),
        ];

        return response()->json($summary);
    }
}
