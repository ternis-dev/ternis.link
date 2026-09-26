<?php

namespace App\Http\Controllers;

use App\Services\AltchaService;
use Illuminate\Http\JsonResponse;

/**
 * Stateless Altcha challenge dispenser for the self-hosted widget
 * (same origin — no external requests). Challenges are HMAC-signed
 * and expire after AltchaService::EXPIRES_SECONDS.
 */
class AltchaController extends Controller
{
    public function challenge(AltchaService $altcha): JsonResponse
    {
        return response()->json($altcha->challenge())->header('Cache-Control', 'no-store');
    }
}
