<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ApiSessionToken;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * The CORS-protected auth API of the public dashboard. A cross-origin
 * client asks whether the visitor is signed in and exchanges the
 * session for a short-lived bearer token.
 */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class]);

        $this->user = User::factory()->create();

        Config::set('cors.allowed_origins', ['https://app.example.com']);
    }

    public function test_session_reports_anonymous_visitors(): void
    {
        $this->getJson('http://my.ternis.link/auth/api/session')
            ->assertOk()
            ->assertJson([
                'authenticated' => false,
                'host' => 'my.ternis.link',
                'user' => null,
            ]);
    }

    public function test_session_reports_the_signed_in_user(): void
    {
        $this->actingAs($this->user)
            ->getJson('http://my.ternis.link/auth/api/session')
            ->assertOk()
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('user.id', $this->user->id)
            ->assertJsonPath('user.email', $this->user->email);
    }

    public function test_session_is_available_on_the_alias_host(): void
    {
        $this->actingAs($this->user)
            ->getJson('http://my.href.nz/auth/api/session')
            ->assertOk()
            ->assertJsonPath('host', 'my.href.nz')
            ->assertJsonPath('authenticated', true);
    }

    public function test_token_requires_a_session(): void
    {
        $this->postJson('http://my.ternis.link/auth/api/token')
            ->assertUnauthorized();

        $this->assertDatabaseCount('api_session_tokens', 0);
    }

    public function test_token_is_minted_for_a_signed_in_user(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('http://my.ternis.link/auth/api/token')
            ->assertCreated()
            ->assertJsonPath('token_type', 'Bearer');

        $raw = $response->json('token');

        $this->assertStringStartsWith('ts_', $raw);

        // Only the digest is stored — the raw token cannot be recovered.
        $row = ApiSessionToken::firstOrFail();

        $this->assertNotSame($raw, $row->token_hash);
        $this->assertSame(ApiSessionToken::hashToken($raw), $row->token_hash);
        $this->assertTrue($row->verifyToken($raw));
        $this->assertSame($this->user->id, $row->user_id);
    }

    public function test_token_lifetime_is_clamped(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('http://my.ternis.link/auth/api/token', ['ttl' => 999999])
            ->assertCreated();

        $this->assertSame(3600, $response->json('expires_in'));

        $response = $this->actingAs($this->user)
            ->postJson('http://my.ternis.link/auth/api/token', ['ttl' => -5])
            ->assertCreated();

        $this->assertSame(900, $response->json('expires_in'));
    }

    public function test_token_authenticates_the_rest_api(): void
    {
        $raw = $this->actingAs($this->user)
            ->postJson('http://my.ternis.link/auth/api/token')
            ->assertCreated()
            ->json('token');

        $this->getJson('http://links.t-api.de/v1/links', [
            'Authorization' => "Bearer {$raw}",
        ])->assertOk();

        $this->assertDatabaseHas('api_session_tokens', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_revoked_token_stops_working(): void
    {
        $raw = $this->actingAs($this->user)
            ->postJson('http://my.ternis.link/auth/api/token')
            ->assertCreated()
            ->json('token');

        $this->actingAs($this->user)
            ->deleteJson('http://my.ternis.link/auth/api/token', [], [
                'Authorization' => "Bearer {$raw}",
            ])->assertNoContent();

        $this->getJson('http://links.t-api.de/v1/links', [
            'Authorization' => "Bearer {$raw}",
        ])->assertUnauthorized();
    }

    public function test_expired_token_stops_working(): void
    {
        $raw = $this->actingAs($this->user)
            ->postJson('http://my.ternis.link/auth/api/token')
            ->assertCreated()
            ->json('token');

        ApiSessionToken::query()->update(['expires_at' => now()->subMinute()]);

        $this->getJson('http://links.t-api.de/v1/links', [
            'Authorization' => "Bearer {$raw}",
        ])->assertUnauthorized();
    }

    public function test_disallowed_origin_is_rejected(): void
    {
        $this->getJson('http://my.ternis.link/auth/api/session', [
            'Origin' => 'https://evil.example',
        ])->assertForbidden();
    }

    public function test_allowed_origin_gets_cors_headers(): void
    {
        $response = $this->get('http://my.ternis.link/auth/api/session', [
            'Origin' => 'https://app.example.com',
        ]);

        $response->assertOk();

        $this->assertSame(
            'https://app.example.com',
            $response->headers->get('Access-Control-Allow-Origin')
        );

        $this->assertSame('true', $response->headers->get('Access-Control-Allow-Credentials'));
    }

    public function test_preflight_is_answered_for_allowed_origins(): void
    {
        $response = $this->call('OPTIONS', 'http://my.ternis.link/auth/api/token', [], [], [], [
            'HTTP_ORIGIN' => 'https://app.example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $response->assertSuccessful();

        $this->assertSame(
            'https://app.example.com',
            $response->headers->get('Access-Control-Allow-Origin')
        );
    }

    public function test_preflight_never_grants_an_unknown_origin(): void
    {
        $response = $this->call('OPTIONS', 'http://my.ternis.link/auth/api/token', [], [], [], [
            'HTTP_ORIGIN' => 'https://evil.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        // With a single allowlisted origin the CORS layer echoes that
        // entry; what matters is that it never names the requesting
        // origin, so the browser blocks the response. The server-side
        // guard (EnsureCorsOrigin) is what actually rejects the call.
        $this->assertNotSame('https://evil.example', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_auth_api_is_not_available_on_other_hosts(): void
    {
        $this->getJson('http://dash.ternis.link/auth/api/session')->assertNotFound();
        $this->getJson('http://links.t-api.de/auth/api/session')->assertNotFound();
    }

    public function test_token_issuing_is_audited(): void
    {
        $this->actingAs($this->user)
            ->postJson('http://my.ternis.link/auth/api/token')
            ->assertCreated();

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $this->user->id,
            'action' => ActivityLog::AUTH_TOKEN_ISSUED,
        ]);
    }
}
