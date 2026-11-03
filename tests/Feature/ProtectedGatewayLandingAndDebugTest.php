<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Domain;
use App\Models\Link;
use App\Models\ThreatEvaluation;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ProtectedGatewayLandingAndDebugTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $this->regularUser = User::factory()->create([
            'role' => UserRole::User,
        ]);
    }

    public function test_landing_page_renders_with_zero_trust_and_no_brand_icon(): void
    {
        ThreatEvaluation::create([
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'destination_url' => 'https://malicious.example.com',
            'destination_host' => 'malicious.example.com',
            'evaluation_type' => 'link_creation',
            'verdict' => 'malware',
            'threat_score' => 95,
            'confidence' => 0.99,
            'action_taken' => 'quarantined',
        ]);

        $response = $this->get('http://protected.ternis.link/');
        $response->assertStatus(200);
        $response->assertSee('protected.ternis.link', escape: false);
        $response->assertSee('Zero-Trust', escape: false);
        $response->assertSee('Bot Protection', escape: false);
        $response->assertSee('Client Signature &amp; Bot Classifier', escape: false);
        $response->assertSee('Request ID (rid) Lookup', escape: false);

        // Does not show admin debug mode when unauthenticated
        $response->assertDontSee('ADMIN DEBUG MODE', escape: false);
    }

    public function test_landing_page_shows_admin_debug_link_for_authenticated_admin(): void
    {
        $response = $this->actingAs($this->admin)->get('http://protected.ternis.link/');
        $response->assertStatus(200);
        $response->assertSee('Admin Debug', escape: false);
    }

    public function test_public_can_classify_client_signatures(): void
    {
        // 1. Legitimate browser
        $resBrowser = $this->postJson('http://protected.ternis.link/classify-client', [
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]);
        $resBrowser->assertOk();
        $resBrowser->assertJsonFragment(['verdict' => 'legitimate_browser']);
        $this->assertEquals('ALLOW', $resBrowser->json('recommended_action'));

        // 2. Automated crawler or tool
        $resCurl = $this->postJson('http://protected.ternis.link/classify-client', [
            'user_agent' => 'curl/8.4.0',
        ]);
        $resCurl->assertOk();
        $resCurl->assertJsonFragment(['verdict' => 'benign_tool']);
        $this->assertContains($resCurl->json('recommended_action'), ['CHALLENGE_TURNSTILE', 'CHALLENGE_UA']);

        // 3. Exploit scanner
        $resSqlmap = $this->postJson('http://protected.ternis.link/classify-client', [
            'user_agent' => 'sqlmap/1.7.11#stable (https://sqlmap.org)',
        ]);
        $resSqlmap->assertOk();
        $resSqlmap->assertJsonFragment(['verdict' => 'exploit_scanner']);
        $this->assertEquals('CHALLENGE_UA', $resSqlmap->json('recommended_action'));
    }

    public function test_public_can_lookup_request_id_format(): void
    {
        $validRid = 'rid_01J9H5W6EZTRF4D9KXM2QG8VYB';
        $res = $this->postJson('http://protected.ternis.link/lookup-rid', [
            'rid' => $validRid,
        ]);
        $res->assertOk();
        $this->assertTrue($res->json('valid_format'));
        $this->assertEquals($validRid, $res->json('request_id'));

        $invalidRid = 'not_a_valid_id';
        $resInvalid = $this->postJson('http://protected.ternis.link/lookup-rid', [
            'rid' => $invalidRid,
        ]);
        $resInvalid->assertOk();
        $this->assertFalse($resInvalid->json('valid_format'));
    }

    public function test_debug_endpoint_is_forbidden_to_guests_and_regular_users(): void
    {
        // Guests forbidden
        $this->get('http://protected.ternis.link/debug')
            ->assertStatus(403);

        // Regular users forbidden
        $this->actingAs($this->regularUser)
            ->get('http://protected.ternis.link/debug')
            ->assertStatus(403);

        $this->actingAs($this->regularUser)
            ->postJson('http://protected.ternis.link/debug/analyze', ['user_agent' => 'curl/8.4.0'])
            ->assertStatus(403);
    }

    public function test_debug_endpoint_renders_for_admin(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('http://protected.ternis.link/debug');

        $response->assertStatus(200);
        $response->assertSee('ADMIN DEBUG MODE', escape: false);
        $response->assertSee('Protection Decision Sandbox', escape: false);
        $response->assertSee('Human Verification Cookie Cockpit', escape: false);
        $response->assertSee('Rate Limit &amp; IP Transport', escape: false);
        $response->assertSee('Challenge Signature Generator &amp; Anti-Replay Validator', escape: false);
    }

    public function test_admin_can_simulate_protection_decisions_step_by_step(): void
    {
        $link = Link::create([
            'slug' => 'debug-shielded',
            'destination_url' => 'https://example.com/target',
            'domain_id' => Domain::first()->id,
            'user_id' => $this->admin->id,
            'bot_protection' => true,
        ]);

        // 1. Without cookie -> should challenge Turnstile
        $response = $this->actingAs($this->admin)
            ->postJson('http://protected.ternis.link/debug/simulate', [
                'link_slug_or_id' => $link->slug,
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'has_cookie' => 'no',
            ]);

        $response->assertOk();
        $response->assertJsonPath('result.decision', 'CHALLENGE_TURNSTILE');
        $this->assertNotEmpty($response->json('result.trace'));

        // 2. With cookie override -> should allow
        $resWithCookie = $this->actingAs($this->admin)
            ->postJson('http://protected.ternis.link/debug/simulate', [
                'link_slug_or_id' => $link->slug,
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'has_cookie' => 'yes',
            ]);

        $resWithCookie->assertOk();
        $resWithCookie->assertJsonPath('result.decision', 'ALLOW');

        // 3. Suspicious user-agent -> should challenge UA
        $resUa = $this->actingAs($this->admin)
            ->postJson('http://protected.ternis.link/debug/simulate', [
                'link_slug_or_id' => '',
                'user_agent' => 'curl/8.4.0',
                'has_cookie' => 'no',
            ]);

        $resUa->assertOk();
        $resUa->assertJsonPath('result.decision', 'CHALLENGE_USER_AGENT');
    }

    public function test_simulation_resolves_domain_and_rejects_ambiguous_slugs(): void
    {
        $domains = Domain::take(2)->get();
        foreach ($domains as $domain) {
            Link::create([
                'domain_id' => $domain->id,
                'slug' => 'shared-debug',
                'destination_url' => 'https://example.com',
                'user_id' => $this->admin->id,
            ]);
        }
        $this->actingAs($this->admin)
            ->postJson('http://protected.ternis.link/debug/simulate', ['link_slug_or_id' => 'shared-debug'])
            ->assertUnprocessable();
        $this->postJson('http://protected.ternis.link/debug/simulate', [
            'link_slug_or_id' => 'https://'.$domains[1]->hostname.'/shared-debug',
        ])->assertOk()->assertJsonPath('link.short_url', 'https://'.$domains[1]->hostname.'/shared-debug');
    }

    public function test_admin_can_toggle_human_verification_cookie(): void
    {
        // 1. Issue cookie
        $issueResponse = $this->actingAs($this->admin)
            ->post('http://protected.ternis.link/debug/cookie', ['action' => 'issue']);

        $issueResponse->assertStatus(302);
        $issueResponse->assertCookie('tl_human_verified');

        // 2. Clear cookie
        $clearResponse = $this->actingAs($this->admin)
            ->post('http://protected.ternis.link/debug/cookie', ['action' => 'clear']);

        $clearResponse->assertStatus(302);
        $clearResponse->assertCookieExpired('tl_human_verified');
    }

    public function test_admin_can_generate_and_test_signatures(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('http://protected.ternis.link/debug/test-signature', [
                'link_id' => '01JTESTLINKID',
            ]);

        $response->assertOk();
        $this->assertTrue($response->json('is_valid'));
        $this->assertNotEmpty($response->json('signature'));
        $this->assertStringContainsString('protected.ternis.link/challenge', $response->json('challenge_url'));
        $this->assertStringContainsString('protected.ternis.link/user-agent', $response->json('user_agent_url'));
    }

    public function test_admin_can_clear_rate_limit(): void
    {
        $testIp = '198.51.100.99';
        RateLimiter::hit('bot_verify:'.$testIp, 60);
        $this->assertEquals(1, RateLimiter::attempts('bot_verify:'.$testIp));

        $response = $this->actingAs($this->admin)
            ->postJson('http://protected.ternis.link/debug/clear-ratelimit', [
                'ip' => $testIp,
            ]);

        $response->assertOk();
        $this->assertTrue($response->json('success'));
        $this->assertEquals(0, RateLimiter::attempts('bot_verify:'.$testIp));
    }

    public function test_admin_bot_protection_page_renders_in_admin_console(): void
    {
        // Regular user forbidden
        $this->actingAs($this->regularUser)
            ->get('http://admin.ternis.link/bot-protection')
            ->assertForbidden();

        // Admin user can view
        $response = $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/bot-protection')
            ->assertOk();

        $response->assertSee('Bot Protection', escape: false);
        $response->assertSee('Protection Decision Sandbox', escape: false);
        $response->assertSee('Active Suspicious Client Signatures', escape: false);
        $response->assertSee('Operational Parameters', escape: false);
    }

    public function test_admin_bot_protection_simulation_works(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('http://admin.ternis.link/bot-protection/simulate', [
                'user_agent' => 'curl/8.4.0',
                'has_cookie' => 'no',
            ]);

        $response->assertOk();
        $response->assertJsonPath('result.decision', 'CHALLENGE_USER_AGENT');
        $this->assertNotEmpty($response->json('result.trace'));
    }
}
