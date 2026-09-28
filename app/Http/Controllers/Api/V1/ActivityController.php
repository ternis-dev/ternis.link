<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * GET /v1/activity — Personal activity history (own actions plus
     * actions others performed on the user's stuff), newest first.
     */
    public function index(Request $request): JsonResponse
    {
        $entries = ActivityLog::visibleTo($request->user()->id)
            ->with(['actor', 'subjectOwner'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($entries);
    }
}
