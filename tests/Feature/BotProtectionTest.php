<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\LinkEditForm;
use App\Livewire\Dashboard\LinkForm;
use App\Livewire\Dashboard\LinkTable;
use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use App\Services\BotProtectionService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class BotProtectionTest extends TestCase
{
    use RefreshDatabase;

    private Domain $domain;

    private User $user;

    private BotProtectionService $botProtection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->domain = Domain::where('hostname', 'href.nz')->first();
        $this->user = User::factory()->create();
        $this->botProtection = app(BotProtectionService::class);
    }

    public function test_bot_protected_link_redirects_to_protected_gateway_challenge(): void
    {
        $link = Link::create([
            'slug' => 'shielded',
            'destination_url' => 'https://example.com/target-destination',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'bot_protection' => true,
        ]);

        $response = $this->withHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)')
            ->get("http://href.nz/{$link->slug}");

        $response->assertStatus(302);
        $this->assertStringContainsString('protected.ternis.link/challenge', $response->headers->get('Location'));
        $this->assertStringContainsString("link={$link->id}", $response->headers->get('Location'));
        $this->assertStringContainsString('rid=', $response->headers->get('Location'));
        $this->assertStringContainsString('sig=', $response->headers->get('Location'));
    }

    public function test_suspicious_user_agent_redirects_to_user_agent_challenge(): void
    {
        $link = Link::create([
            'slug' => 'normal-link',
            'destination_url' => 'https://example.com/target-destination',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'bot_protection' => false,
        ]);

        $response = $this->withHeader('User-Agent', 'curl/8.4.0')
            ->get("http://href.nz/{$link->slug}");

        $response->assertStatus(302);
        $this->assertStringContainsString('protected.ternis.link/user-agent', $response->headers->get('Location'));
        $this->assertStringContainsString("link={$link->id}", $response->headers->get('Location'));
        $this->assertStringContainsString('rid=', $response->headers->get('Location'));
    }

    public function test_human_verified_cookie_bypasses_bot_protection(): void
    {
        $link = Link::create([
            'slug' => 'shielded-bypass',
            'destination_url' => 'https://example.com/target-destination',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'bot_protection' => true,
        ]);

        $cookie = $this->botProtection->makeHumanVerificationCookie('href.nz');

        $response = $this->withHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)')
            ->withCookie($cookie->getName(), $cookie->getValue())
            ->get("http://href.nz/{$link->slug}");

        $response->assertStatus(302);
        $response->assertRedirect('https://example.com/target-destination');
    }

    public function test_protected_gateway_renders_challenge_page(): void
    {
        $link = Link::create([
            'slug' => 'gate-check',
            'destination_url' => 'https://example.com/target-destination',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'bot_protection' => true,
        ]);

        $url = $this->botProtection->buildChallengeUrl($link, request());

        $response = $this->get($url);
        $response->assertStatus(200);
        $response->assertSee('Verify you are human', escape: false);
        $response->assertSee($link->slug, escape: false);
        $response->assertSee('protected.ternis.link', escape: false);
    }

    public function test_protected_gateway_renders_user_agent_challenge_page(): void
    {
        $link = Link::create([
            'slug' => 'ua-check',
            'destination_url' => 'https://example.com/target-destination',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'bot_protection' => false,
        ]);

        $url = $this->botProtection->buildUserAgentChallengeUrl($link, request());

        $response = $this->withHeader('User-Agent', 'python-requests/2.31.0')
            ->get($url);

        $response->assertStatus(200);
        $response->assertSee('Automated Client Detected', escape: false);
        $response->assertSee('python-requests/2.31.0', escape: false);
    }

    public function test_tampered_signature_is_rejected(): void
    {
        $link = Link::create([
            'slug' => 'tamper-test',
            'destination_url' => 'https://example.com/target-destination',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'bot_protection' => true,
        ]);

        $response = $this->get("http://protected.ternis.link/challenge?link={$link->id}&rid=rid_test&ts=".time().'&sig=invalid-signature');
        $response->assertStatus(403);
    }

    public function test_successful_verification_sets_cookie_and_redirects(): void
    {
        $link = Link::create([
            'slug' => 'verified-redirect',
            'destination_url' => 'https://example.com/target-destination',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'bot_protection' => true,
        ]);

        $rid = 'rid_01JTESTING1234567890';
        $ts = time();
        $sig = $this->botProtection->signChallenge($link->id, $rid, $ts);

        $response = $this->post('http://protected.ternis.link/verify', [
            'link' => $link->id,
            'rid' => $rid,
            'ts' => $ts,
            'sig' => $sig,
            'cf-turnstile-response' => 'test-dummy-token',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('https://example.com/target-destination');
        $response->assertCookie(config('bot_protection.cookie_name', 'tl_human_verified'));
    }

    public function test_password_and_bot_protected_link_requires_password_on_protected_gateway(): void
    {
        $link = Link::create([
            'slug' => 'double-locked',
            'destination_url' => 'https://example.com/secret-room',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'password_hash' => Hash::make('CorrectPassword123'),
            'bot_protection' => true,
        ]);

        $rid = 'rid_01JTESTINGPASSWORD';
        $ts = time();
        $sig = $this->botProtection->signChallenge($link->id, $rid, $ts);

        // Wrong password fails
        $wrongResponse = $this->post('http://protected.ternis.link/verify', [
            'link' => $link->id,
            'rid' => $rid,
            'ts' => $ts,
            'sig' => $sig,
            'password' => 'WrongPassword',
            'cf-turnstile-response' => 'test-token',
        ]);

        $wrongResponse->assertSessionHasErrors('password');

        // Correct password succeeds and sets session unlock
        $correctResponse = $this->post('http://protected.ternis.link/verify', [
            'link' => $link->id,
            'rid' => $rid,
            'ts' => $ts,
            'sig' => $sig,
            'password' => 'CorrectPassword123',
            'cf-turnstile-response' => 'test-token',
        ]);

        $correctResponse->assertStatus(302);
        $correctResponse->assertRedirect('https://example.com/secret-room');
        $correctResponse->assertCookie(config('bot_protection.cookie_name', 'tl_human_verified'));
    }

    public function test_rate_limiting_on_verify_endpoint(): void
    {
        $link = Link::create([
            'slug' => 'rate-limit-test',
            'destination_url' => 'https://example.com/target-destination',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'bot_protection' => true,
        ]);

        $ip = '198.51.100.42';
        $rateLimitKey = 'bot_verify:'.$ip;
        RateLimiter::clear($rateLimitKey);

        $maxAttempts = (int) config('bot_protection.rate_limit.max_attempts', 30);
        for ($i = 0; $i < $maxAttempts; $i++) {
            RateLimiter::hit($rateLimitKey, 60);
        }

        $rid = 'rid_rate_limit';
        $ts = time();
        $sig = $this->botProtection->signChallenge($link->id, $rid, $ts);

        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post('http://protected.ternis.link/verify', [
                'link' => $link->id,
                'rid' => $rid,
                'ts' => $ts,
                'sig' => $sig,
            ]);

        $response->assertStatus(429);
        RateLimiter::clear($rateLimitKey);
    }

    public function test_protected_landing_page_renders(): void
    {
        $response = $this->get('http://protected.ternis.link/');
        $response->assertStatus(200);
        $response->assertSee('protected.ternis.link', escape: false);
        $response->assertSee('Zero-Trust', escape: false);
        $response->assertSee('Bot Protection', escape: false);
    }

    public function test_api_can_create_and_update_bot_protected_link(): void
    {
        $apiKeyRaw = 'tl_'.Str::random(48);
        ApiKey::create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $apiKeyRaw),
            'key_prefix' => substr($apiKeyRaw, 0, 8),
            'api_version' => 1,
            'name' => 'Bot Test Key',
        ]);

        $headers = [
            'Authorization' => "Bearer {$apiKeyRaw}",
            'Accept' => 'application/json',
        ];

        // 1. Create with bot_protection = true
        $storeResponse = $this->postJson('http://links.t-api.de/v1/links', [
            'destination_url' => 'https://example.com/api-bot-target',
            'domain_id' => $this->domain->id,
            'slug' => 'apibotlink',
            'bot_protection' => true,
        ], $headers);

        $storeResponse->assertStatus(201);
        $storeResponse->assertJsonPath('bot_protection', true);

        $linkId = $storeResponse->json('id');
        $this->assertDatabaseHas('links', [
            'id' => $linkId,
            'bot_protection' => true,
        ]);

        // 2. Filter by bot_protection=1
        $listResponse = $this->getJson('http://links.t-api.de/v1/links?bot_protection=1', $headers);
        $listResponse->assertStatus(200);
        $this->assertCount(1, $listResponse->json('data'));

        // 3. Update to bot_protection = false
        $updateResponse = $this->putJson("http://links.t-api.de/v1/links/{$linkId}", [
            'bot_protection' => false,
        ], $headers);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('bot_protection', false);
        $this->assertDatabaseHas('links', [
            'id' => $linkId,
            'bot_protection' => false,
        ]);
    }

    public function test_livewire_forms_support_bot_protection(): void
    {
        $this->actingAs($this->user);

        // Test LinkForm
        Livewire::test(LinkForm::class)
            ->set('destination_url', 'https://example.com/livewire-bot-target')
            ->set('domain_id', $this->domain->id)
            ->set('slug', 'lwbotlink')
            ->set('bot_protection', true)
            ->call('create')
            ->assertHasNoErrors();

        $link = Link::where('slug', 'lwbotlink')->firstOrFail();
        $this->assertTrue($link->bot_protection);

        // Test LinkEditForm
        Livewire::test(LinkEditForm::class, ['link' => $link])
            ->assertSet('bot_protection', true)
            ->set('bot_protection', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($link->fresh()->bot_protection);
    }

    public function test_link_table_filters_by_bot_protected_status(): void
    {
        $this->actingAs($this->user);

        $protectedLink = Link::create([
            'slug' => 'protected-filter-link',
            'destination_url' => 'https://example.com/target1',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'bot_protection' => true,
        ]);

        $normalLink = Link::create([
            'slug' => 'normal-filter-link',
            'destination_url' => 'https://example.com/target2',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'bot_protection' => false,
        ]);

        Livewire::test(LinkTable::class)
            ->set('statusFilter', 'bot_protected')
            ->assertSee('protected-filter-link')
            ->assertDontSee('normal-filter-link');
    }
}
