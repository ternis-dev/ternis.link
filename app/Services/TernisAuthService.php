<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\OAuthIdentity;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TernisAuthService
{
    private string $baseUrl;

    private string $clientId;

    private string $clientSecret;

    private string $redirectUri;

    private string $scopes;

    public function __construct()
    {
        $this->baseUrl = config('services.ternis_auth.base_url', 'https://auth.ternis.net');
        $this->clientId = (string) config('services.ternis_auth.client_id', '');
        $this->clientSecret = (string) config('services.ternis_auth.client_secret', '');
        $this->redirectUri = (string) config('services.ternis_auth.redirect_uri', '');
        $this->scopes = (string) config('services.ternis_auth.scopes', 'openid profile email ternis:sso');
    }

    /**
     * Generate PKCE code verifier (43-128 char random string).
     */
    public function generateCodeVerifier(): string
    {
        return Str::random(64);
    }

    /**
     * Generate PKCE code challenge (Base64URL-encoded SHA-256 of verifier).
     */
    public function generateCodeChallenge(string $verifier): string
    {
        $hash = hash('sha256', $verifier, true);

        return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    }

    /**
     * Build the authorization URL to redirect the user to Ternis Auth.
     */
    public function getAuthorizationUrl(string $state, string $codeChallenge): string
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => $this->scopes,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return "{$this->baseUrl}/oauth/authorize?{$params}";
    }

    /**
     * Build a silent SSO check URL (prompt=none).
     * Returns auth code immediately if session exists, or ?error=login_required.
     */
    public function getSilentAuthUrl(string $state, string $codeChallenge): string
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => $this->scopes,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'prompt' => 'none',
        ]);

        return "{$this->baseUrl}/oauth/authorize?{$params}";
    }

    /**
     * Exchange authorization code for tokens.
     *
     * @return array{access_token: string, refresh_token: ?string, expires_in: int}
     */
    public function exchangeCode(string $code, string $codeVerifier): array
    {
        $response = Http::asForm()->post("{$this->baseUrl}/oauth/token", [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
            'code_verifier' => $codeVerifier,
        ]);

        $response->throw();

        return $response->json();
    }

    /**
     * Fetch user info from the OIDC userinfo endpoint.
     *
     * @return array Full userinfo claims (sub, name, email, picture, user_type, etc.)
     */
    public function getUserInfo(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get("{$this->baseUrl}/oauth/userinfo");

        $response->throw();

        return $response->json();
    }

    /**
     * Find or create a local User from SSO claims, update OAuth tokens.
     * This is the core "SSO callback" logic.
     */
    public function findOrCreateUser(array $tokenData, array $userInfo): User
    {
        $user = User::updateOrCreate(
            ['sso_sub' => $userInfo['sub']],
            [
                'name' => $userInfo['name'] ?? 'Ternis User',
                'email' => $userInfo['email'],
                'avatar_url' => $userInfo['picture'] ?? null,
                'sso_user_type' => $userInfo['user_type'] ?? null,
                'role' => $this->mapRole($userInfo),
            ]
        );

        // Assign default plan if new user or missing plan
        if (! $user->plan_id) {
            $freePlan = Plan::where('name', 'free')->first();
            if ($freePlan) {
                $user->update(['plan_id' => $freePlan->id]);
            }
        }

        // Store/update OAuth tokens
        $expiresIn = $tokenData['expires_in'] ?? 3600;
        OAuthIdentity::updateOrCreate(
            ['user_id' => $user->id],
            [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds((int) $expiresIn),
                'sso_claims' => $userInfo,
                'claims_synced_at' => now(),
            ]
        );

        return $user->fresh();
    }

    /**
     * Refresh an expired access token using the stored refresh token.
     * Re-syncs the user profile and role from fresh claims.
     *
     * Returns false when there is nothing to refresh with or the
     * provider rejects the request — callers should re-authenticate.
     */
    public function refreshAccessToken(User $user): bool
    {
        $identity = $user->oauthIdentity;

        if (! $identity || ! $identity->refresh_token) {
            return false;
        }

        try {
            $response = Http::asForm()->post("{$this->baseUrl}/oauth/token", [
                'grant_type' => 'refresh_token',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $identity->refresh_token,
            ]);

            if ($response->failed()) {
                return false;
            }

            $tokens = $response->json();

            if (empty($tokens['access_token'])) {
                return false;
            }

            $userInfo = $this->getUserInfo($tokens['access_token']);

            $identity->update([
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? $identity->refresh_token,
                'token_expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 3600)),
                'sso_claims' => $userInfo,
                'claims_synced_at' => now(),
            ]);

            $this->syncUserFromClaims($user, $userInfo);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * RP-initiated logout URL, or null when SSO logout is disabled.
     */
    public function getEndSessionUrl(): ?string
    {
        if (! config('services.ternis_auth.end_session', false)) {
            return null;
        }

        $path = config('services.ternis_auth.end_session_path', '/oauth/logout');
        $redirect = config('services.ternis_auth.post_logout_redirect_uri') ?: url('/');
        $params = http_build_query(['post_logout_redirect_uri' => $redirect]);

        return "{$this->baseUrl}{$path}?{$params}";
    }

    /**
     * Update profile fields and role from fresh SSO claims.
     */
    private function syncUserFromClaims(User $user, array $userInfo): void
    {
        $user->update([
            'name' => $userInfo['name'] ?? $user->name,
            'email' => $userInfo['email'] ?? $user->email,
            'avatar_url' => $userInfo['picture'] ?? $user->avatar_url,
            'sso_user_type' => $userInfo['user_type'] ?? $user->sso_user_type,
            'role' => $this->mapRole($userInfo),
        ]);
    }

    /**
     * Map SSO user_type + claims to a local UserRole.
     */
    public function mapRole(array $userInfo): UserRole
    {
        $userType = $userInfo['user_type'] ?? null;

        // Ternis members: check if admin-level or family
        if ($userType === 'ternis_member') {
            $badge = $userInfo['ternis_member']['member_badge'] ?? null;
            if (in_array($badge, ['ternis-core', 'ternis-admin'])) {
                return UserRole::Admin;
            }

            return UserRole::Family;
        }

        // Verified partners
        if ($userType === 'partner' || ! empty($userInfo['ternis_partner'])) {
            return UserRole::Partner;
        }

        // Everyone else (general users, paying customers)
        return UserRole::User;
    }
}
