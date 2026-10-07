<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    /**
     * GET /v1/settings — Theme/layout, notification, and domain preferences.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->present($request->user()));
    }

    /**
     * PATCH /v1/settings — Update preferences (same rules as web SettingsForm).
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $allowedIds = $user->availableDomains()->pluck('id')->all();

        $validated = $request->validate([
            'nav_layout' => ['sometimes', 'required', Rule::in(User::NAV_LAYOUTS)],
            'theme' => ['sometimes', 'required', Rule::in(User::THEMES)],
            'notify_security_email' => ['sometimes', 'required', 'boolean'],
            'notify_admin_security_email' => ['sometimes', 'required', 'boolean'],
            'notify_server_error_email' => ['sometimes', 'required', 'boolean'],
            'default_domain_id' => ['sometimes', 'nullable', 'string', Rule::in(['', ...$allowedIds])],
            'domain_order' => ['sometimes', 'nullable', 'array'],
            'domain_order.*' => ['string'],
        ]);

        if (array_key_exists('default_domain_id', $validated)) {
            $validated['default_domain_id'] = $validated['default_domain_id'] ?: null;
        }

        if (array_key_exists('domain_order', $validated)) {
            $validated['domain_order'] = $validated['domain_order'] ? array_values($validated['domain_order']) : null;
        }

        $user->update($validated);

        return response()->json($this->present($user->fresh()));
    }

    private function present(User $user): array
    {
        return [
            'nav_layout' => $user->nav_layout,
            'theme' => $user->theme,
            'notify_security_email' => (bool) $user->notify_security_email,
            'notify_admin_security_email' => (bool) $user->notify_admin_security_email,
            'notify_server_error_email' => (bool) $user->notify_server_error_email,
            'default_domain_id' => $user->default_domain_id,
            'default_domain' => $user->defaultDomain?->hostname,
            'domain_order' => $user->domain_order,
        ];
    }
}
