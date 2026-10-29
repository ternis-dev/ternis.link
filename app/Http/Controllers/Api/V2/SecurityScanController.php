<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\AiThreatDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SecurityScanController extends Controller
{
    public function __construct(
        protected AiThreatDetectionService $threatService,
    ) {}

    public function scan(Request $request): JsonResponse
    {
        $url = $request->input('url') ?? $request->query('url');

        if (! is_string($url) || trim($url) === '') {
            return response()->json([
                'message' => 'The url field is required.',
                'errors' => [
                    'url' => ['The destination URL to scan is required.'],
                ],
            ], 422);
        }

        $fresh = filter_var($request->input('fresh') ?? $request->query('fresh'), FILTER_VALIDATE_BOOLEAN);

        $result = $this->threatService->scanUrl($url, ['fresh' => $fresh]);

        if (! ($result['is_valid'] ?? false)) {
            return response()->json([
                'message' => $result['reasoning'] ?? 'The provided destination is not a valid URL.',
                'errors' => [
                    'url' => [$result['reasoning'] ?? 'Invalid URL.'],
                ],
            ], 422);
        }

        $fields = $request->input('fields') ?? $request->query('fields');
        if (is_string($fields)) {
            $fields = array_filter(array_map('trim', explode(',', $fields)));
        }

        if (is_array($fields) && $fields !== []) {
            $filtered = Arr::only($result, array_merge($fields, ['url', 'is_valid', 'cached', 'scanned_at']));

            return response()->json($filtered);
        }

        return response()->json($result);
    }
}
