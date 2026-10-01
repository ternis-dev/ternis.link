<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\BuildPrivacyExport;
use App\Models\PrivacyExport;
use App\Services\AccountErasureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountController extends Controller
{
    public function dataSummary(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'links' => $user->links()->count(),
            'domains' => $user->domains()->count(),
            'api_keys' => $user->apiKeys()->count(),
            'deletion_requested_at' => $user->deletion_requested_at,
            'retention' => config('privacy'),
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->privacyExports()->whereIn('status', ['pending', 'processing'])->exists()) {
            return response()->json(['message' => 'An export is already running.'], 409);
        }

        $export = PrivacyExport::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'expires_at' => now()->addDays((int) config('privacy.export_ttl_days', 7)),
        ]);

        BuildPrivacyExport::dispatch($export->id);

        return response()->json($export, 202);
    }

    public function exports(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->privacyExports()->orderByDesc('created_at')->paginate(25)
        );
    }

    public function download(Request $request, PrivacyExport $export): StreamedResponse|JsonResponse
    {
        if ($export->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'You do not own this export.');
        }

        if ($export->status !== 'done' || ! $export->path || ! Storage::disk('local')->exists($export->path)) {
            return response()->json(['message' => 'Export not ready.'], 404);
        }

        if ($export->expires_at && $export->expires_at->isPast()) {
            return response()->json(['message' => 'Export expired.'], 410);
        }

        return Storage::disk('local')->download($export->path, 'ternis-export.zip');
    }

    public function scheduleDeletion(Request $request, AccountErasureService $erasure): JsonResponse
    {
        // Fresh SSO required: API-key Bearer alone cannot wipe an account.
        if (($request->attributes->get('auth_via', 'api-key')) !== 'sso') {
            return response()->json(['message' => 'Fresh SSO login required.'], 403);
        }

        $data = $request->validate(['confirm' => ['required', 'in:DELETE-ME']]);

        $deletionAt = $erasure->schedule($request->user());

        return response()->json([
            'message' => 'Deletion scheduled.',
            'deletion_at' => $deletionAt->format(DATE_ATOM),
        ], 202);
    }

    public function cancelDeletion(Request $request, AccountErasureService $erasure): JsonResponse
    {
        $erasure->cancel($request->user());

        return response()->json(['message' => 'Deletion cancelled.']);
    }
}
