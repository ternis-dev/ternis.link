<?php

namespace App\Http\Controllers;

use App\Support\MigrationReadiness;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    /**
     * Deep health check for load balancers / container probes.
     * Keep serving existing redirects while optional migrations are pending.
     */
    public function __invoke(): JsonResponse
    {
        return $this->check();
    }

    public function ready(MigrationReadiness $migrations): JsonResponse
    {
        return $this->check($migrations);
    }

    private function check(?MigrationReadiness $migrations = null): JsonResponse
    {
        try {
            DB::connection()->getPdo();

            if ($migrations !== null && ! $migrations->ready()) {
                return response()->json(['status' => 'degraded', 'database' => 'ok', 'migrations' => 'pending'], 503);
            }

            return response()->json(['status' => 'ok', 'database' => 'ok']
                + ($migrations !== null ? ['migrations' => 'ok'] : []));
        } catch (Throwable) {
            return response()->json(['status' => 'degraded', 'database' => 'error'], 503);
        }
    }
}
