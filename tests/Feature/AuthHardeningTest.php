<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\OAuthIdentity;
use App\Models\User;
use App\Services\TernisAuthService;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class]);
    }

    private function userWithIdentity(array $overrides = []): User
    {
        $user = User::factory()->create();

        OAuthIdentity::create(array_merge([
            'user_id' => $user->id,
            'access_token' => 'old-access-token',
            'refresh_token' => 'old-refresh-token',
            'token_expires_at' => now()->subHour(),
            'sso_claims' => ['sub' => $user->sso_sub],
            'claims_synced_at' => now()->subHour(),
        ], $overrides));

        return $user->fresh();
    }

    private function fakeRefresh(string $sub, string $badge = 'ternis-core'): void
    {
        Http::fake([
            'https://auth.ternis.net/oauth/token' => Http::response([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'expires_in' => 3600,
            ]),
            'https://auth.ternis.net/oauth/userinfo' => Http::response([
                'sub' => $sub,
                'name' => 'Refreshed User',
                'email' => 'refreshed@ternis.dev',
                'picture' => null,
                'user_type' => 'ternis_member',
                'ternis_member' => ['member_badge' => $badge],
            ]),
        ]);
    }

    public function test_refresh_rotates_tokens_and_syncs_role(): void
    {
        $user = $this->userWithIdentity();
        $this->fakeRefresh($user->sso_sub);

        $this->assertTrue(app(TernisAuthService::class)->refreshAccessToken($user));

        $identity = $user->oauthIdentity->fresh();
        $this->assertEquals('new-access-token', $identity->access_token);
        $this->assertEquals('new-refresh-token', $identity->refresh_token);
        $this->assertTrue($identity->token_expires_at->isFuture());
        $this->assertEquals(UserRole::Admin, $user->fresh()->role);
        $this->assertEquals('Refreshed User', $user->fresh()->name);
    }

    public function test_refresh_fails_without_stored_refresh_token(): void
    {
        $user = $this->userWithIdentity(['refresh_token' => null]);
        Http::fake();

        $this->assertFalse(app(TernisAuthService::class)->refreshAccessToken($user));
        $this->assertEquals('old-access-token', $user->oauthIdentity->fresh()->access_token);
        Http::assertNothingSent();
    }

    public function test_refresh_fails_when_provider_rejects_grant(): void
    {
        $user = $this->userWithIdentity();

        Http::fake([
            'https://auth.ternis.net/oauth/token' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $this->assertFalse(app(TernisAuthService::class)->refreshAccessToken($user));
        $this->assertEquals('old-access-token', $user->oauthIdentity->fresh()->access_token);
    }

    public function test_dashboard_transparently_refreshes_expired_token(): void
    {
        $user = $this->userWithIdentity();
        $this->fakeRefresh($user->sso_sub);

        $response = $this->actingAs($user)
            ->get('http://dash.ternis.link/dashboard');

        $response->assertStatus(200);
        $this->assertAuthenticatedAs($user);
        $this->assertEquals('new-access-token', $user->oauthIdentity->fresh()->access_token);
    }

    public function test_dashboard_logs_out_when_refresh_fails(): void
    {
        $user = $this->userWithIdentity();

        Http::fake([
            'https://auth.ternis.net/oauth/token' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $response = $this->actingAs($user)
            ->get('http://dash.ternis.link/dashboard');

        $response->assertRedirect('http://dash.ternis.link/login');
        $this->assertGuest();
    }

    public function test_dashboard_skips_users_without_sso_identity(): void
    {
        Http::fake(); // Any stray HTTP would return empty 200; none should happen.

        $user = User::factory()->create(); // Demo-style login, no OAuthIdentity.

        $response = $this->actingAs($user)
            ->get('http://dash.ternis.link/dashboard');

        $response->assertStatus(200);
        Http::assertNothingSent();
    }

    public function test_silent_route_uses_prompt_none(): void
    {
        $response = $this->get('http://dash.ternis.link/auth/silent');

        $response->assertStatus(302);
        $targetUrl = $response->headers->get('Location');
        $this->assertStringStartsWith('https://auth.ternis.net/oauth/authorize', $targetUrl);
        $this->assertStringContainsString('prompt=none', $targetUrl);

        $this->assertNotNull(session('oauth_state'));
        $this->assertNotNull(session('oauth_code_verifier'));
    }

    public function test_silent_route_redirects_authenticated_users_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('http://dash.ternis.link/auth/silent')
            ->assertRedirect(route('dashboard'));
    }

    public function test_logout_stays_local_by_default(): void
    {
        config()->set('services.ternis_auth.end_session', false);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('http://dash.ternis.link/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_logout_continues_to_sso_when_enabled(): void
    {
        config()->set('services.ternis_auth.end_session', true);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('http://dash.ternis.link/logout');

        $this->assertGuest();
        $targetUrl = $response->headers->get('Location');
        $this->assertStringStartsWith('https://auth.ternis.net/oauth/logout', $targetUrl);
        $this->assertStringContainsString('post_logout_redirect_uri=', $targetUrl);
    }
}
