<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\OAuthIdentity;
use App\Models\User;
use App\Services\TernisAuthService;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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

    public function test_clicked_login_uses_clicked_branding(): void
    {
        $response = $this->get('http://clicked.at/login');

        $response->assertOk();
        $response->assertSee('clicked');
        $response->assertSee('Sign in with Ternis Auth');
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

    public function test_auth_callback_without_session_state_restarts_login(): void
    {
        // No oauth_state in session (cookies blocked, expired session,
        // retried callback): recoverable redirect, not a 403.
        $response = $this->get('http://dash.ternis.link/auth/callback?code=some_code&state=some_state');

        $response->assertRedirect('http://dash.ternis.link/login');
        $response->assertSessionHas('error', 'Your sign-in session expired before Ternis Auth sent you back (cookies blocked, private window, or a retried page). Please sign in again.');
        $this->assertGuest();
    }

    public function test_auth_callback_with_mismatched_state_aborts(): void
    {
        $response = $this->withSession([
            'oauth_state' => 'stored_state',
            'oauth_code_verifier' => 'test_code_verifier_1234567890123456789012345678901234567890',
        ])->get('http://dash.ternis.link/auth/callback?code=some_code&state=other_state');

        $response->assertForbidden();
        $this->assertGuest();
    }

    public function test_auth_callback_with_rejected_code_redirects_to_login(): void
    {
        Http::fake([
            'https://auth.ternis.net/oauth/token' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $state = 'test_random_state_string';
        $verifier = 'test_code_verifier_1234567890123456789012345678901234567890';

        // A stale/reused/pasted code must not 500 — back to login instead.
        $response = $this->withSession([
            'oauth_state' => $state,
            'oauth_code_verifier' => $verifier,
        ])->get("http://dash.ternis.link/auth/callback?code=stale_code&state={$state}");

        $response->assertRedirect('http://dash.ternis.link/login');
        $response->assertSessionHas('error', 'Sign-in failed at Ternis Auth (token step). Please try again.');
        $this->assertGuest();
    }

    public function test_auth_callback_with_failing_userinfo_redirects_to_login(): void
    {
        Http::fake([
            'https://auth.ternis.net/oauth/token' => Http::response([
                'access_token' => 'mock-access-token',
                'refresh_token' => 'mock-refresh-token',
                'expires_in' => 3600,
            ]),
            'https://auth.ternis.net/oauth/userinfo' => Http::response(['error' => 'invalid_token'], 401),
        ]);

        $state = 'test_random_state_string';
        $verifier = 'test_code_verifier_1234567890123456789012345678901234567890';

        $response = $this->withSession([
            'oauth_state' => $state,
            'oauth_code_verifier' => $verifier,
        ])->get("http://dash.ternis.link/auth/callback?code=mock_code&state={$state}");

        $response->assertRedirect('http://dash.ternis.link/login');
        $response->assertSessionHas('error', 'Sign-in failed while fetching your profile. Please try again.');
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

    public function test_callback_heals_identity_row_undecryptable_after_key_rotation(): void
    {
        // Production case: APP_KEY was rotated, so the stored tokens
        // (encrypted with the old key) throw DecryptException on read.
        // Simulate with a garbage ciphertext row (same exception class).
        $user = User::factory()->create([
            'sso_sub' => '11111111-2222-3333-4444-555555555555',
        ]);
        DB::table('oauth_identities')->insert([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'access_token' => 'ciphertext-from-a-previous-app-key',
            'refresh_token' => 'ciphertext-from-a-previous-app-key',
            'token_expires_at' => now()->subHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            'https://auth.ternis.net/oauth/token' => Http::response([
                'access_token' => 'fresh-access-token',
                'refresh_token' => 'fresh-refresh-token',
                'expires_in' => 3600,
            ]),
            'https://auth.ternis.net/oauth/userinfo' => Http::response([
                'sub' => '11111111-2222-3333-4444-555555555555',
                'name' => 'Fabian Ternis',
                'email' => 'fabian@ternis.dev',
                'user_type' => 'general',
            ]),
        ]);

        $state = 'test_random_state_string';
        $verifier = 'test_code_verifier_1234567890123456789012345678901234567890';

        // Must log in — not bounce to /login with an error.
        $response = $this->withSession([
            'oauth_state' => $state,
            'oauth_code_verifier' => $verifier,
        ])->get("http://dash.ternis.link/auth/callback?code=mock_code&state={$state}");

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        // Stale row replaced by one holding the fresh tokens.
        $identity = $user->fresh()->oauthIdentity;
        $this->assertNotNull($identity);
        $this->assertSame('fresh-access-token', $identity->access_token);
        $this->assertSame('fresh-refresh-token', $identity->refresh_token);
        $this->assertSame(1, OAuthIdentity::where('user_id', $user->id)->count());
    }

    public function test_refresh_drops_undecryptable_identity_instead_of_throwing(): void
    {
        $user = User::factory()->create();
        DB::table('oauth_identities')->insert([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'access_token' => 'ciphertext-from-a-previous-app-key',
            'refresh_token' => 'ciphertext-from-a-previous-app-key',
            'token_expires_at' => now()->subHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // No HTTP fakes: must return false before any network call.
        $this->assertFalse(app(TernisAuthService::class)->refreshAccessToken($user->fresh()));
        $this->assertSame(0, OAuthIdentity::where('user_id', $user->id)->count());
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
