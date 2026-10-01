<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\BulkImportLinks;
use App\Jobs\BulkMutateLinks;
use App\Models\BulkOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BulkOperationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $ops = $request->user()->bulkOperations()->orderByDesc('created_at')->paginate(25);

        return response()->json($ops);
    }

    public function show(Request $request, BulkOperation $operation): JsonResponse
    {
        if ($operation->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'You do not own this operation.');
        }

        return response()->json($operation);
    }

    public function import(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->bulkOperations()->whereIn('status', ['pending', 'processing'])->exists()) {
            return response()->json(['message' => 'A bulk operation is already running.'], 409);
        }

        $data = $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:200'],
            'rows.*.destination_url' => ['required', 'url', 'max:2048'],
            'rows.*.domain_id' => ['nullable', 'exists:domains,id'],
            'rows.*.slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'rows.*.expires_at' => ['nullable', 'date', 'after:now'],
            'rows.*.description' => ['nullable', 'string', 'max:500'],
            'rows.*.tags' => ['nullable', 'array', 'max:10'],
        ]);

        $op = BulkOperation::create([
            'user_id' => $user->id,
            'type' => 'import',
            'status' => 'pending',
            'total' => count($data['rows']),
        ]);

        BulkImportLinks::dispatch($op->id, $data['rows']);

        return response()->json($op, 202);
    }

    public function bulk(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->bulkOperations()->whereIn('status', ['pending', 'processing'])->exists()) {
            return response()->json(['message' => 'A bulk operation is already running.'], 409);
        }

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['string'],
            'action' => ['required', 'in:update,deactivate,destroy'],
            'patch.description' => ['nullable', 'string', 'max:500'],
            'patch.expires_at' => ['nullable', 'date', 'after:now'],
            'patch.is_active' => ['nullable', 'boolean'],
            'patch.domain_id' => ['nullable', 'exists:domains,id'],
        ]);

        $op = BulkOperation::create([
            'user_id' => $user->id,
            'type' => $data['action'] === 'update' ? 'update' : $data['action'],
            'status' => 'pending',
            'total' => count($data['ids']),
        ]);

        BulkMutateLinks::dispatch($op->id, $data['ids'], $data['action'], $data['patch'] ?? []);

        return response()->json($op, 202);
    }
}
