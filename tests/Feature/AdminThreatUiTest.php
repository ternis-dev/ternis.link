<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\Link;
use App\Models\ThreatEvaluation;
use App\Models\User;
use App\Services\AiThreatDetectionService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use TypeSafeAI\DTO\ChoiceAnswer;
use TypeSafeAI\DTO\ScoreAnswer;
use TypeSafeAI\SystemOneClient;
use TypeSafeAI\SystemOneRequest;
use TypeSafeAI\SystemOneResult;

class AdminThreatUiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->regularUser = User::factory()->create([
            'role' => 'user',
        ]);
    }

    public function test_threat_console_requires_admin_role(): void
    {
        $this->actingAs($this->regularUser)
            ->get('http://admin.ternis.link/threats')
            ->assertForbidden();
    }

    public function test_admin_can_view_threat_console_and_evaluations(): void
    {
        $domain = Domain::where('hostname', 'href.nz')->firstOrFail();

        $link = Link::create([
            'slug' => 'bad-malware-link',
            'destination_url' => 'https://malicious-test-bundle.com/trojan.exe',
            'domain_id' => $domain->id,
            'is_active' => false,
            'tags' => ['quarantine', 'malware'],
        ]);

        ThreatEvaluation::create([
            'link_id' => $link->id,
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'evaluation_type' => 'link_creation',
            'destination_url' => $link->destination_url,
            'destination_host' => 'malicious-test-bundle.com',
            'verdict' => 'malware',
            'threat_score' => 95,
            'confidence' => 1.0,
            'action_taken' => 'quarantined',
            'reasoning' => 'Confirmed trojan payload',
            'metadata' => [
                'probabilities' => ['malware' => 1.0, 'safe' => 0.0],
                'raw_score' => 3.9,
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/threats')
            ->assertOk();

        $response->assertSee('AI Threat Inspector');
        $response->assertSee('TypeSafe AI Sandbox');
        $response->assertSee('https://malicious-test-bundle.com/trojan.exe');
        $response->assertSee('quarantined');
        $response->assertSee('95/100');
    }

    public function test_admin_sandbox_logs_scans_even_when_provider_is_unavailable(): void
    {
        config(['ai_security.api_key' => null]);
        $this->actingAs($this->admin)->post('http://admin.ternis.link/threats/test', [
            'test_url' => 'https://example.com/admin-scan',
        ])->assertRedirect();
        $this->assertDatabaseHas('threat_evaluations', [
            'evaluation_type' => 'admin_url_scan',
            'destination_url' => 'https://example.com/admin-scan',
            'user_id' => $this->admin->id,
            'verdict' => 'unverified',
            'action_taken' => 'scanned',
        ]);
    }

    public function test_suspicious_exe_url_is_always_evaluated_and_quarantined(): void
    {
        $threatService = app(AiThreatDetectionService::class);
        $this->assertTrue($threatService->isSuspiciousUrl('https://not-a-scam.com/download.exe'));
        $this->assertTrue($threatService->isSuspiciousUrl('https://update-installer.org/payload.scr'));

        $domain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $link = Link::create([
            'slug' => 'test-download-exe',
            'destination_url' => 'https://not-a-scam.com/download.exe',
            'domain_id' => $domain->id,
            'is_active' => true,
        ]);

        $this->assertTrue($threatService->shouldEvaluateLink($link));
    }

    private function makeMockClient(string $verdict = 'safe', float $score = 0.0, float $confidence = 0.99): SystemOneClient
    {
        $result = new SystemOneResult;

        $choice = new ChoiceAnswer;
        $choice->choice = $verdict;
        $choice->confidence = $confidence;
        $choice->probabilities = [$verdict => $confidence];

        $scoreAnswer = new ScoreAnswer;
        $scoreAnswer->score = $score;
        $scoreAnswer->confidence = $confidence;
        $scoreAnswer->legend = [
            'Benign, legitimate website',
            'Mildly suspicious or low reputation',
            'Borderline or suspected promotional spam',
            'High probability of phishing or scam',
            'Critical confirmed malware or credential harvester',
        ];
        $scoreAnswer->probabilities = [0 => 1.0, 4 => 0.0];

        $result->answers = [
            'verdict' => $choice,
            'category' => $choice,
            'threat_score' => $scoreAnswer,
        ];

        return new class($result) implements SystemOneClient
        {
            public function __construct(private SystemOneResult $result) {}

            public function systemOne(SystemOneRequest $request): SystemOneResult
            {
                return $this->result;
            }

            public function evaluate(string|array|object $state, string $class, ?string $model = null): object
            {
                throw new \BadMethodCallException('evaluate not implemented in mock');
            }
        };
    }

    public function test_admin_can_unquarantine_link_from_threats_console(): void
    {
        $domain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $link = Link::create([
            'slug' => 'quarantined-link',
            'destination_url' => 'https://example.com/restorable',
            'domain_id' => $domain->id,
            'is_active' => false,
            'tags' => ['quarantine', 'malware'],
        ]);

        $response = $this->actingAs($this->admin)
            ->post("http://admin.ternis.link/threats/links/{$link->id}/unquarantine")
            ->assertRedirect();

        $response->assertSessionHas('success');

        $link->refresh();
        $this->assertTrue($link->is_active);
        $this->assertNotContains('quarantine', $link->tags ?? []);
        $this->assertNotContains('malware', $link->tags ?? []);
        $this->assertContains('admin_cleared', $link->tags ?? []);
    }

    public function test_admin_can_rescan_link_from_threats_console(): void
    {
        $domain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $link = Link::create([
            'slug' => 'rescan-target',
            'destination_url' => 'https://example.com/rescannable',
            'domain_id' => $domain->id,
            'is_active' => true,
        ]);

        $service = new AiThreatDetectionService($this->makeMockClient('safe', 0.0));
        $this->app->instance(AiThreatDetectionService::class, $service);

        $response = $this->actingAs($this->admin)
            ->post("http://admin.ternis.link/threats/links/{$link->id}/rescan")
            ->assertRedirect();

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('threat_evaluations', [
            'link_id' => $link->id,
            'evaluation_type' => 'admin_rescan',
            'verdict' => 'safe',
            'action_taken' => 'cleared',
        ]);
    }

    public function test_admin_can_filter_threats_by_bypassed_admin_keys(): void
    {
        $domain = Domain::where('hostname', 'href.nz')->firstOrFail();

        $bypassKey = ApiKey::create([
            'user_id' => $this->admin->id,
            'name' => 'Bypass Admin Key',
            'key_hash' => hash('sha256', 'tl_bypass123'),
            'key_prefix' => 'tl_bypa',
            'api_version' => 1,
            'bypass_ai_check' => true,
            'is_active' => true,
        ]);

        $bypassedLink = Link::create([
            'slug' => 'bypassed-slug',
            'destination_url' => 'https://example.com/admin-exempt-url',
            'domain_id' => $domain->id,
            'user_id' => $this->admin->id,
            'api_key_id' => $bypassKey->id,
            'is_active' => true,
        ]);

        ThreatEvaluation::create([
            'link_id' => $bypassedLink->id,
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'evaluation_type' => 'batch_scan',
            'destination_url' => $bypassedLink->destination_url,
            'destination_host' => 'example.com',
            'verdict' => 'safe',
            'threat_score' => 0,
            'confidence' => 0.99,
            'action_taken' => 'cleared',
        ]);

        $normalLink = Link::create([
            'slug' => 'normal-slug',
            'destination_url' => 'https://example.com/normal-url',
            'domain_id' => $domain->id,
            'is_active' => true,
        ]);

        ThreatEvaluation::create([
            'link_id' => $normalLink->id,
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'evaluation_type' => 'link_creation',
            'destination_url' => $normalLink->destination_url,
            'destination_host' => 'example.com',
            'verdict' => 'phishing',
            'threat_score' => 85,
            'confidence' => 0.95,
            'action_taken' => 'quarantined',
        ]);

        $response = $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/threats?bypassed=1')
            ->assertOk();

        $response->assertSee('https://example.com/admin-exempt-url');
        $response->assertDontSee('https://example.com/normal-url');
    }

    public function test_regular_user_cannot_unquarantine_or_rescan(): void
    {
        $domain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $link = Link::create([
            'slug' => 'unauth-target',
            'destination_url' => 'https://example.com/target',
            'domain_id' => $domain->id,
            'is_active' => false,
        ]);

        $this->actingAs($this->regularUser)
            ->post("http://admin.ternis.link/threats/links/{$link->id}/unquarantine")
            ->assertForbidden();

        $this->actingAs($this->regularUser)
            ->post("http://admin.ternis.link/threats/links/{$link->id}/rescan")
            ->assertForbidden();
    }
}
