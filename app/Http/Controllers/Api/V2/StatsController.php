<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Support\NetworkStats;
use Illuminate\Http\JsonResponse;

/**
 * Public, aggregate-only network stats (JSON twin of /pages/stats).
 * No personal data and no custom-domain hostnames are ever returned.
 */
class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(NetworkStats::overview());
    }

    public function heatmaps(): JsonResponse
    {
        return response()->json(NetworkStats::heatmaps());
    }

    /** Platform (system) domains only; custom domains are never public. */
    public function domains(): JsonResponse
    {
        return response()->json(NetworkStats::domains()
            ->filter(fn ($domain) => $domain->user_id === null)
            ->map(fn ($domain) => [
                'hostname' => $domain->hostname,
                'links' => (int) $domain->links_count,
                'clicks' => (int) ($domain->links_sum_click_count ?? 0),
            ])
            ->values());
    }

    public function creations(): JsonResponse
    {
        return $this->series(NetworkStats::creationsByDay(30));
    }

    public function clicks(): JsonResponse
    {
        return $this->series(NetworkStats::clicksByDay(30));
    }

    public function qr(): JsonResponse
    {
        return $this->series(NetworkStats::qrByDay(30));
    }

    /**
     * Zero-filled last 30 days, oldest first, as {date, count} rows.
     *
     * @param  array{labels: list<string>, values: list<int>}  $series
     */
    private function series(array $series): JsonResponse
    {
        $days = NetworkStats::lastDays(30)->pluck('date')->values();

        return response()->json($days->map(fn ($date, $i) => [
            'date' => $date,
            'count' => (int) ($series['values'][$i] ?? 0),
        ])->values());
    }
}
