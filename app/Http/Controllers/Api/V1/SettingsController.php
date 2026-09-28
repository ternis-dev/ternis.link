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
     * GET /v1/settings — Theme/layout + email notification preferences.
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
        $validated = $request->validate([
            'nav_layout' => ['sometimes', 'required', Rule::in(User::NAV_LAYOUTS)],
            'theme' => ['sometimes', 'required', Rule::in(User::THEMES)],
            'notify_security_email' => ['sometimes', 'required', 'boolean'],
            'notify_admin_security_email' => ['sometimes', 'required', 'boolean'],
            'notify_server_error_email' => ['sometimes', 'required', 'boolean'],
        ]);

        $request->user()->update($validated);

        return response()->json($this->present($request->user()->fresh()));
    }

    private function present(User $user): array
    {
        return [
            'nav_layout' => $user->nav_layout,
            'theme' => $user->theme,
            'notify_security_email' => (bool) $user->notify_security_email,
            'notify_admin_security_email' => (bool) $user->notify_admin_security_email,
            'notify_server_error_email' => (bool) $user->notify_server_error_email,
        ];
    }
}
