<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscoveryController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'service' => 'Ternis Links API v3',
            'version' => 3,
            'features' => [
                'preferred_domains' => true,
                'default_domain_fallback' => 'href.nz',
                'threat_detection' => true,
                'custom_slugs' => true,
                'bot_protection' => true,
                'password_protection' => true,
                'expiration' => true,
                'tags' => true,
            ],
            'endpoints' => [
                'discovery' => 'GET /v3',
                'me' => 'GET /v3/me',
                'domains' => 'GET /v3/domains',
                'links' => 'GET|POST /v3/links',
                'link_details' => 'GET|PUT|DELETE /v3/links/{link}',
                'api_keys' => 'GET|POST /v3/api-keys',
                'api_key_details' => 'GET|PATCH|DELETE /v3/api-keys/{key}',
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');

        $preferredDomain = $apiKey?->resolvedPreferredDomain() ?? $user->resolvedDefaultDomain();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'plan' => $user->plan?->name,
            ],
            'api_key' => $apiKey ? [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'prefix' => $apiKey->key_prefix,
                'preferred_domain' => $preferredDomain ? [
                    'id' => $preferredDomain->id,
                    'hostname' => $preferredDomain->hostname,
                ] : null,
            ] : null,
            'default_domain' => $preferredDomain ? [
                'id' => $preferredDomain->id,
                'hostname' => $preferredDomain->hostname,
            ] : null,
        ]);
    }

    public function domains(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');
        $preferred = $apiKey?->resolvedPreferredDomain();

        $domains = $user->availableDomains()->map(function (Domain $domain) use ($preferred, $user) {
            return [
                'id' => $domain->id,
                'hostname' => $domain->hostname,
                'type' => $domain->type->value ?? 'public',
                'is_preferred' => $preferred?->id === $domain->id,
                'is_user_default' => $user->default_domain_id === $domain->id,
            ];
        });

        return response()->json(['domains' => $domains]);
    }
}
