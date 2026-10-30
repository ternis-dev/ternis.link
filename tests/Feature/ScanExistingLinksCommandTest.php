<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
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

class ScanExistingLinksCommandTest extends TestCase
{
    use RefreshDatabase;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
        $this->domain = Domain::where('hostname', 'href.nz')->firstOrFail();
    }

    private function makeMockClient(string $verdict = 'phishing', float $score = 3.8, float $confidence = 0.95): SystemOneClient
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
        $scoreAnswer->probabilities = [0 => 0.0, 4 => 1.0];

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

    private function createLink(array $overrides = []): Link
    {
        static $counter = 100;
        $counter++;

        return Link::create(array_merge([
            'slug' => 'test-scan-'.$counter,
            'destination_url' => 'https://example.com/dest-'.$counter,
            'domain_id' => $this->domain->id,
            'is_active' => true,
        ], $overrides));
    }

    public function test_dry_run_evaluates_links_without_modifying_link_state_or_saving_evaluations(): void
    {
        $service = new AiThreatDetectionService($this->makeMockClient('malware', 4.0));
        $this->app->instance(AiThreatDetectionService::class, $service);

        $link = $this->createLink([
            'destination_url' => 'https://example.com/payload.exe',
            'is_active' => true,
        ]);

        $this->artisan('security:scan-existing', ['--dry-run' => true, '--limit' => 10])
            ->expectsOutputToContain('Dry run complete: 1 link(s) inspected (1 would quarantine')
            ->assertExitCode(0);

        $link->refresh();
        $this->assertTrue($link->is_active);
        $this->assertSame(0, ThreatEvaluation::count());
    }

    public function test_scan_existing_quarantines_malicious_links_and_records_threat_evaluations(): void
    {
        $service = new AiThreatDetectionService($this->makeMockClient('malware', 4.0));
        $this->app->instance(AiThreatDetectionService::class, $service);

        $link = $this->createLink([
            'destination_url' => 'https://example.com/trojan.exe',
            'is_active' => true,
        ]);

        $this->artisan('security:scan-existing', ['--limit' => 5, '--delay-ms' => 0])
            ->expectsOutputToContain('Scan complete: 1 link(s) evaluated (1 quarantined')
            ->assertExitCode(0);

        $link->refresh();
        $this->assertFalse($link->is_active);
        $this->assertContains('quarantine', $link->tags ?? []);
        $this->assertSame(1, ThreatEvaluation::count());

        $this->assertDatabaseHas('threat_evaluations', [
            'link_id' => $link->id,
            'verdict' => 'malware',
            'action_taken' => 'quarantined',
            'evaluation_type' => 'batch_scan',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => ActivityLog::SYSTEM_THREAT_SCAN_COMPLETED,
        ]);
    }

    public function test_scan_existing_skips_admin_api_keys_with_bypass(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $adminKey = ApiKey::create([
            'user_id' => $admin->id,
            'name' => 'Exempt Admin Key',
            'key_hash' => hash('sha256', 'tl_test123'),
            'key_prefix' => 'tl_test',
            'api_version' => 1,
            'bypass_ai_check' => true,
            'is_active' => true,
        ]);

        $service = new AiThreatDetectionService($this->makeMockClient('malware', 4.0));
        $this->app->instance(AiThreatDetectionService::class, $service);

        $link = $this->createLink([
            'user_id' => $admin->id,
            'api_key_id' => $adminKey->id,
            'destination_url' => 'https://example.com/admin-safe-payload',
            'is_active' => true,
        ]);

        $this->artisan('security:scan-existing', ['--limit' => 5, '--delay-ms' => 0])
            ->expectsOutputToContain('1 bypassed')
            ->assertExitCode(0);

        $link->refresh();
        $this->assertTrue($link->is_active);
        $this->assertSame(0, ThreatEvaluation::count());
    }

    public function test_unscanned_only_filters_out_already_evaluated_links(): void
    {
        $service = new AiThreatDetectionService($this->makeMockClient('safe', 0.2));
        $this->app->instance(AiThreatDetectionService::class, $service);

        $alreadyScanned = $this->createLink(['destination_url' => 'https://example.com/already-scanned']);
        ThreatEvaluation::create([
            'link_id' => $alreadyScanned->id,
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
            'destination_url' => $alreadyScanned->destination_url,
            'destination_host' => 'example.com',
            'evaluation_type' => 'link_creation',
            'verdict' => 'safe',
            'threat_score' => 5,
            'confidence' => 0.99,
            'action_taken' => 'cleared',
        ]);

        $freshLink = $this->createLink(['destination_url' => 'https://example.com/fresh-unscanned']);

        $this->artisan('security:scan-existing', ['--unscanned-only' => true, '--delay-ms' => 0])
            ->expectsOutputToContain('Scan complete: 1 link(s) evaluated')
            ->assertExitCode(0);

        $this->assertSame(2, ThreatEvaluation::count());
        $this->assertDatabaseHas('threat_evaluations', ['link_id' => $freshLink->id]);
    }

    public function test_guests_only_filters_out_user_links(): void
    {
        $user = User::factory()->create();
        $service = new AiThreatDetectionService($this->makeMockClient('safe', 0.2));
        $this->app->instance(AiThreatDetectionService::class, $service);

        $userLink = $this->createLink(['user_id' => $user->id]);
        $guestLink = $this->createLink(['user_id' => null]);

        $this->artisan('security:scan-existing', ['--guests-only' => true, '--delay-ms' => 0])
            ->expectsOutputToContain('Scan complete: 1 link(s) evaluated')
            ->assertExitCode(0);

        $this->assertDatabaseHas('threat_evaluations', ['link_id' => $guestLink->id]);
        $this->assertDatabaseMissing('threat_evaluations', ['link_id' => $userLink->id]);
    }

    public function test_suspicious_only_filters_out_benign_urls(): void
    {
        $service = new AiThreatDetectionService($this->makeMockClient('phishing', 3.8));
        $this->app->instance(AiThreatDetectionService::class, $service);

        $benign = $this->createLink(['destination_url' => 'https://example.com/benign-page']);
        $suspicious = $this->createLink(['destination_url' => 'https://example.com/security-update']);

        $this->artisan('security:scan-existing', ['--suspicious-only' => true, '--delay-ms' => 0])
            ->expectsOutputToContain('Scan complete: 1 link(s) evaluated')
            ->assertExitCode(0);

        $this->assertDatabaseHas('threat_evaluations', ['link_id' => $suspicious->id]);
        $this->assertDatabaseMissing('threat_evaluations', ['link_id' => $benign->id]);
    }

    public function test_fails_if_ai_security_is_disabled_unless_force_passed(): void
    {
        config(['ai_security.enabled' => false]);

        $this->artisan('security:scan-existing')
            ->expectsOutputToContain('AI Threat Detection is disabled in configuration')
            ->assertExitCode(1);

        $service = new AiThreatDetectionService($this->makeMockClient('safe', 0.2));
        $this->app->instance(AiThreatDetectionService::class, $service);

        $this->artisan('security:scan-existing', ['--force' => true, '--dry-run' => true])
            ->assertExitCode(0);
    }
}
