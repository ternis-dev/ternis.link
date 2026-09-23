<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    /**
     * Deep health check for load balancers / container probes.
     * 200 when the database is reachable, 503 otherwise.
     */
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();

            return response()->json(['status' => 'ok', 'database' => 'ok']);
        } catch (Throwable) {
            return response()->json(['status' => 'degraded', 'database' => 'error'], 503);
        }
    }
}
