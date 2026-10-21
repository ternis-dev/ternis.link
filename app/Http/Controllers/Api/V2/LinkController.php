<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V1\LinkController as V1LinkController;
use App\Http\Requests\StoreLinkRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class LinkController extends V1LinkController
{
    public function store(StoreLinkRequest $request): JsonResponse
    {
        if (! $request->user()->availableDomains()->contains('id', $request->validated('domain_id'))) {
            throw ValidationException::withMessages(['domain_id' => 'Choose a usable domain returned by GET /v2/domains.']);
        }

        return parent::store($request);
    }
}
