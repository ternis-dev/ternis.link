<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\LinkForm;
use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\LinkGroupItem;
use App\Models\User;
use App\Services\BotProtectionService;
use App\Services\CustomCaptchaService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;
use Tests\TestCase;

class CustomCaptchaAndProtectionConfigTest extends TestCase
{
    use RefreshDatabase;

    private Domain $domain;

    private User $user;

    private BotProtectionService $botProtection;

    private CustomCaptchaService $captchaService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->domain = Domain::where('hostname', 'href.nz')->first();
        $this->user = User::factory()->create();
        $this->botProtection = app(BotProtectionService::class);
        $this->captchaService = app(CustomCaptchaService::class);
    }

    public function test_custom_captcha_service_generates_and_verifies_successfully(): void
    {
        $challenge = $this->captchaService->generate();

        $this->assertArrayHasKey('svg', $challenge);
        $this->assertArrayHasKey('token', $challenge);
        $this->assertArrayHasKey('question', $challenge);
        $this->assertStringContainsString('<svg', $challenge['svg']);

        // Decrypt payload to discover the answer for test verification
        $decrypted = json_decode(Crypt::decryptString($challenge['token']), true);
        $this->assertIsArray($decrypted);
        $this->assertArrayHasKey('hash', $decrypted);

        // Find the arithmetic answer from the question string (e.g. "7 + 4 = ?")
        preg_match('/(\d+)\s*([\+\-×*])\s*(\d+)/u', $challenge['question'], $matches);
        $this->assertCount(4, $matches);

        $a = (int) $matches[1];
        $op = $matches[2];
        $b = (int) $matches[3];

        $answer = match ($op) {
            '+' => $a + $b,
            '-' => $a - $b,
            '×' => $a * $b,
        };

        // Verification with correct answer
        $this->assertTrue($this->captchaService->verify($challenge['token'], (string) $answer));

        // Replay attempt must be rejected (single-use token nonce)
        $this->assertFalse($this->captchaService->verify($challenge['token'], (string) $answer));

        // Wrong answer must be rejected
        $challenge2 = $this->captchaService->generate();
        $this->assertFalse($this->captchaService->verify($challenge2['token'], '999999'));

        // Tampered token must be rejected
        $this->assertFalse($this->captchaService->verify('tampered.token.invalid', (string) $answer));
    }

    public function test_link_inherits_bot_protection_level_from_group(): void
    {
        $group = LinkGroup::create([
            'user_id' => $this->user->id,
            'name' => 'Protected Marketing Group',
            'bot_protection' => true,
            'bot_protection_level' => 'custom_captcha',
        ]);

        $link = Link::create([
            'slug' => 'grouped-link',
            'destination_url' => 'https://example.com/dest',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'bot_protection' => false,
            'bot_protection_level' => null,
        ]);

        LinkGroupItem::create([
            'link_group_id' => $group->id,
            'link_id' => $link->id,
            'sort_order' => 1,
        ]);

        $this->assertTrue($link->hasBotProtection());
        $this->assertSame('custom_captcha', $link->getBotProtectionLevel());
    }

    public function test_link_explicit_level_overrides_group_policy(): void
    {
        $group = LinkGroup::create([
            'user_id' => $this->user->id,
            'name' => 'Strict Group',
            'bot_protection' => true,
            'bot_protection_level' => 'strict',
        ]);

        $link = Link::create([
            'slug' => 'override-link',
            'destination_url' => 'https://example.com/dest',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'bot_protection' => false,
            'bot_protection_level' => 'off',
        ]);

        LinkGroupItem::create([
            'link_group_id' => $group->id,
            'link_id' => $link->id,
            'sort_order' => 1,
        ]);

        $this->assertSame('off', $link->getBotProtectionLevel());
    }

    public function test_protected_gateway_renders_custom_captcha_for_custom_captcha_level(): void
    {
        $link = Link::create([
            'slug' => 'privacy-protected',
            'destination_url' => 'https://example.com/target',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'bot_protection' => true,
            'bot_protection_level' => 'custom_captcha',
        ]);

        $rid = $this->botProtection->generateRequestId();
        $ts = time();
        $sig = $this->botProtection->signChallenge($link->id, $rid, $ts);

        $response = $this->get("http://protected.ternis.link/challenge?link={$link->id}&rid={$rid}&ts={$ts}&sig={$sig}");

        $response->assertStatus(200);
        $response->assertSee('Privacy CAPTCHA');
        $response->assertSee('name="custom_captcha_answer"', escape: false);
        $response->assertSee('name="custom_captcha_token"', escape: false);
    }

    public function test_verify_custom_captcha_sets_human_cookie_and_redirects(): void
    {
        $link = Link::create([
            'slug' => 'captcha-test',
            'destination_url' => 'https://example.com/solved-target',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'bot_protection' => true,
            'bot_protection_level' => 'custom_captcha',
        ]);

        $rid = $this->botProtection->generateRequestId();
        $ts = time();
        $sig = $this->botProtection->signChallenge($link->id, $rid, $ts);

        $challenge = $this->captchaService->generate();
        preg_match('/(\d+)\s*([\+\-×*])\s*(\d+)/u', $challenge['question'], $matches);
        $this->assertCount(4, $matches);
        $a = (int) $matches[1];
        $op = $matches[2];
        $b = (int) $matches[3];
        $answer = match ($op) {
            '+' => $a + $b,
            '-' => $a - $b,
            '×', '*' => $a * $b,
        };

        $response = $this->post('http://protected.ternis.link/verify', [
            'link' => $link->id,
            'rid' => $rid,
            'ts' => $ts,
            'sig' => $sig,
            'custom_captcha_token' => $challenge['token'],
            'custom_captcha_answer' => (string) $answer,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('https://example.com/solved-target');
        $response->assertCookie('tl_human_verified');
    }

    public function test_legal_page_redirects_suspicious_user_agent_to_gateway(): void
    {
        $response = $this->withHeader('User-Agent', 'curl/8.4.0')
            ->get('http://ternis.link/pages/legal/privacy');

        $response->assertStatus(302);
        $this->assertStringContainsString('protected.ternis.link/user-agent', $response->headers->get('Location'));
        $this->assertStringContainsString('dest=%2Fpages%2Flegal%2Fprivacy', $response->headers->get('Location'));
    }

    public function test_legal_page_allows_clean_browser_immediately(): void
    {
        $response = $this->withHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36')
            ->get('http://ternis.link/pages/legal/privacy');

        $response->assertStatus(200);
        $response->assertSee('Privacy Policy');
    }

    public function test_verify_dest_redirects_back_to_legal_page_with_cookie(): void
    {
        $dest = '/pages/legal/privacy';
        $rid = $this->botProtection->generateRequestId();
        $ts = time();
        $sig = $this->botProtection->signChallenge('dest:'.$dest, $rid, $ts);

        $challenge = $this->captchaService->generate();
        preg_match('/(\d+)\s*([\+\-×*])\s*(\d+)/u', $challenge['question'], $matches);
        $this->assertCount(4, $matches);
        $a = (int) $matches[1];
        $op = $matches[2];
        $b = (int) $matches[3];
        $answer = match ($op) {
            '+' => $a + $b,
            '-' => $a - $b,
            '×', '*' => $a * $b,
        };

        $response = $this->post('http://protected.ternis.link/verify', [
            'dest' => $dest,
            'rid' => $rid,
            'ts' => $ts,
            'sig' => $sig,
            'custom_captcha_token' => $challenge['token'],
            'custom_captcha_answer' => (string) $answer,
        ]);

        $response->assertStatus(302);
        $this->assertStringContainsString('/pages/legal/privacy', $response->headers->get('Location'));
        $response->assertCookie('tl_human_verified');
    }

    public function test_link_form_creates_link_with_configured_protection_level(): void
    {
        $this->actingAs($this->user);

        Livewire::test(LinkForm::class)
            ->set('destination_url', 'https://example.com/custom-protected')
            ->set('domain_id', $this->domain->id)
            ->set('bot_protection_level', 'custom_captcha')
            ->call('create')
            ->assertHasNoErrors();

        $link = Link::where('destination_url', 'https://example.com/custom-protected')->first();
        $this->assertNotNull($link);
        $this->assertTrue($link->bot_protection);
        $this->assertSame('custom_captcha', $link->bot_protection_level);
    }
}
