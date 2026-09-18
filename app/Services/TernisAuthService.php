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
