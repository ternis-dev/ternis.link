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
        if ($link->is_removed) {
            abort(404);
        }

        if ($link->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'You do not own this link.');
        }

        $clicks = $link->clicks()
            ->when(! $request->user()->isAdmin(), function ($query) {
                // Non-admins cannot see direct URL redirect clicks
                $query->where('is_direct_url', false);
            })
            ->when($request->filled('tag'), function ($query) use ($request) {
                $query->whereJsonContains('tags', $request->query('tag'));
            })
            ->when($request->filled('user_identifier'), function ($query) use ($request) {
                $query->where('user_identifier', $request->query('user_identifier'));
            })
            ->when($request->has('has_user'), function ($query) use ($request) {
                $request->boolean('has_user')
                    ? $query->whereNotNull('user_identifier')
                    : $query->whereNull('user_identifier');
            })
            ->when($request->has('has_params'), function ($query) use ($request) {
                $request->boolean('has_params')
                    ? $query->whereNotNull('query_params')
                    : $query->whereNull('query_params');
            })
            ->when($request->filled('from'), function ($query) use ($request) {
                $query->where('created_at', '>=', $request->query('from'));
            })
            ->when($request->filled('to'), function ($query) use ($request) {
                $query->where('created_at', '<=', $request->query('to'));
            })
            ->orderByDesc('created_at')
            ->paginate(min(100, max(1, $request->integer('per_page', 50))));

        return response()->json($clicks);
    }

    /**
     * GET /v1/links/{link}/clicks/summary — Aggregated click stats.
     */
    public function summary(Request $request, Link $link): JsonResponse
    {
        if ($link->is_removed) {
            abort(404);
        }

        if ($link->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'You do not own this link.');
        }

        $baseQuery = $link->clicks()
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('is_direct_url', false))
            ->when($request->filled('tag'), fn ($q) => $q->whereJsonContains('tags', $request->query('tag')))
            ->when($request->filled('user_identifier'), fn ($q) => $q->where('user_identifier', $request->query('user_identifier')))
            ->when($request->has('has_user'), function ($query) use ($request) {
                $request->boolean('has_user')
                    ? $query->whereNotNull('user_identifier')
                    : $query->whereNull('user_identifier');
            })
            ->when($request->has('has_params'), function ($query) use ($request) {
                $request->boolean('has_params')
                    ? $query->whereNotNull('query_params')
                    : $query->whereNull('query_params');
            })
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('created_at', '<=', $request->query('to')));

        // Calculate top dynamic tags from tracked clicks
        $rawTags = (clone $baseQuery)
            ->whereNotNull('tags')
            ->pluck('tags');

        $topTags = collect($rawTags)
            ->flatten()
            ->filter(fn ($t) => is_string($t) && $t !== '')
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->map(fn ($count, $tag) => ['tag' => (string) $tag, 'count' => (int) $count])
            ->values()
            ->all();

        $summary = [
            'total_clicks' => $baseQuery->count(),
            'unique_visitors' => (clone $baseQuery)->distinct('ip_hash')->count('ip_hash'),
            'unique_users' => (clone $baseQuery)->whereNotNull('user_identifier')->distinct('user_identifier')->count('user_identifier'),
            'tracked_clicks' => (clone $baseQuery)->whereNotNull('user_identifier')->count(),
            'with_params_clicks' => (clone $baseQuery)->whereNotNull('query_params')->count(),
            'top_tags' => $topTags,
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
            'by_target' => $link->targets()->orderBy('sort_order')->get()->map(function ($t) use ($link) {
                $clicks = $link->clicks()->where('link_target_id', $t->id)->count();

                return [
                    'id' => $t->id,
                    'label' => $t->label,
                    'clicks' => $clicks,
                    'share' => null,
                ];
            })->values()->all(),
        ];

        $total = (int) ($summary['total_clicks'] ?? 0);
        if ($total > 0) {
            foreach ($summary['by_target'] as &$row) {
                $row['share'] = round($row['clicks'] / $total * 100, 1);
            }
        }

        return response()->json($summary);
    }
}
