<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TernisAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class]);
    }

    public function test_login_page_renders(): void
    {
        $response = $this->get('http://dash.ternis.link/login');
        $response->assertStatus(200);
        $response->assertSee('Login with Ternis Auth SSO');
    }

    public function test_auth_redirect_redirects_to_ternis_auth(): void
    {
        $response = $this->get('http://dash.ternis.link/auth/redirect');

        $response->assertStatus(302);
        $targetUrl = $response->headers->get('Location');
        $this->assertStringStartsWith('https://auth.ternis.net/oauth/authorize', $targetUrl);
        $this->assertStringContainsString('code_challenge=', $targetUrl);
        $this->assertStringContainsString('code_challenge_method=S256', $targetUrl);

        $this->assertNotNull(session('oauth_state'));
        $this->assertNotNull(session('oauth_code_verifier'));
    }

    public function test_auth_callback_provisions_user_and_logs_in(): void
    {
        Http::fake([
            'https://auth.ternis.net/oauth/token' => Http::response([
                'access_token' => 'mock-access-token',
                'refresh_token' => 'mock-refresh-token',
                'expires_in' => 3600,
            ]),
            'https://auth.ternis.net/oauth/userinfo' => Http::response([
                'sub' => '11111111-2222-3333-4444-555555555555',
                'name' => 'Fabian Ternis',
                'email' => 'fabian@ternis.dev',
                'picture' => 'https://user.t-api.de/11111111-2222-3333-4444-555555555555.png',
                'user_type' => 'ternis_member',
                'ternis_member' => [
                    'member_badge' => 'ternis-core',
                ],
            ]),
        ]);

        $state = 'test_random_state_string';
        $verifier = 'test_code_verifier_1234567890123456789012345678901234567890';

        $response = $this->withSession([
            'oauth_state' => $state,
            'oauth_code_verifier' => $verifier,
        ])->get("http://dash.ternis.link/auth/callback?code=mock_code&state={$state}");

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'fabian@ternis.dev')->first();
        $this->assertNotNull($user);
        $this->assertEquals(UserRole::Admin, $user->role);
        $this->assertEquals('11111111-2222-3333-4444-555555555555', $user->sso_sub);
        $this->assertNotNull($user->oauthIdentity);
    }

    public function test_auth_callback_handles_login_required_error(): void
    {
        $response = $this->get('http://dash.ternis.link/auth/callback?error=login_required');

        $response->assertRedirect('http://dash.ternis.link/login');
        $this->assertGuest();
    }

    public function test_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('http://dash.ternis.link/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_demo_login_authenticates_user(): void
    {
        $response = $this->get('http://dash.ternis.link/auth/demo?role=admin');

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = auth()->user();
        $this->assertEquals(UserRole::Admin, $user->role);
        $this->assertEquals('admin@demo.local', $user->email);
    }

    public function test_demo_login_fails_in_production(): void
    {
        $this->app['env'] = 'production';

        $response = $this->get('http://dash.ternis.link/auth/demo?role=admin');

        $response->assertStatus(404);
    }
}
