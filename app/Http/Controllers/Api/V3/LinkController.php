<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Api\V1\LinkController as V1LinkController;
use App\Http\Requests\StoreLinkRequest;
use App\Models\ApiKey;
use App\Models\Domain;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class LinkController extends V1LinkController
{
    public function store(StoreLinkRequest $request): JsonResponse
    {
        $domainId = $request->validated('domain_id');

        if (! $request->user()->availableDomains()->contains('id', $domainId)) {
            throw ValidationException::withMessages([
                'domain_id' => 'Choose a usable domain returned by GET /v3/domains.',
            ]);
        }

        return parent::store($request);
    }
}
