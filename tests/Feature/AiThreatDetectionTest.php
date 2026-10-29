<?php

namespace Tests\Feature;

use App\Jobs\EvaluateLinkThreatJob;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use App\Services\AiThreatDetectionService;
use App\Services\LinkService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;
use TypeSafeAI\DTO\ChoiceAnswer;
use TypeSafeAI\DTO\ScoreAnswer;
use TypeSafeAI\SystemOneClient;
use TypeSafeAI\SystemOneRequest;
use TypeSafeAI\SystemOneResult;

class AiThreatDetectionTest extends TestCase
{
    use RefreshDatabase;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->domain = Domain::where('hostname', 'href.nz')->firstOrFail();
    }

    private function createLink(array $overrides = []): Link
    {
        static $counter = 1;
        $counter++;

        return Link::create(array_merge([
            'slug' => 'threat-link-'.$counter,
            'destination_url' => 'https://example.com/dest-'.$counter,
            'domain_id' => $this->domain->id,
            'is_active' => true,
        ], $overrides));
    }

    private function makeMockClient(string $verdict, float $score, float $confidence = 0.95): SystemOneClient
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
            'threat_score' => $scoreAnswer,
            'client_type' => $choice,
            'bot_confidence' => $scoreAnswer,
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

    public function test_evaluates_guest_links_and_new_users_correctly(): void
    {
        $service = app(AiThreatDetectionService::class);

        // Guest link
        $guestLink = $this->createLink([
            'domain_id' => $this->domain->id,
            'user_id' => null,
        ]);
        $this->assertTrue($service->shouldEvaluateLink($guestLink));

        // Brand new user with 1 link
        $newUser = User::factory()->create(['created_at' => now()->subDay()]);
        $newUserLink = $this->createLink([
            'domain_id' => $this->domain->id,
            'user_id' => $newUser->id,
        ]);
        $this->assertTrue($service->shouldEvaluateLink($newUserLink));

        // Established user (created 30 days ago with 10 links)
        $veteranUser = User::factory()->create(['created_at' => now()->subDays(30)]);
        for ($i = 0; $i < 10; $i++) {
            $this->createLink([
                'domain_id' => $this->domain->id,
                'user_id' => $veteranUser->id,
            ]);
        }
        $veteranLink = $this->createLink([
            'domain_id' => $this->domain->id,
            'user_id' => $veteranUser->id,
        ]);
        $this->assertFalse($service->shouldEvaluateLink($veteranLink));
    }

    public function test_link_creation_dispatches_evaluation_job_for_guests(): void
    {
        Queue::fake([EvaluateLinkThreatJob::class]);

        $linkService = app(LinkService::class);
        $link = $linkService->create(
            destinationUrl: 'https://example.com/some-target',
            domain: $this->domain,
            user: null,
            creatorIp: '198.51.100.25',
        );

        Queue::assertPushed(EvaluateLinkThreatJob::class, function ($job) use ($link) {
            return $job->linkId === $link->id;
        });
    }

    public function test_phishing_verdict_triggers_quarantine_action(): void
    {
        $link = $this->createLink([
            'domain_id' => $this->domain->id,
            'destination_url' => 'https://malicious-login-portal.example/signin',
            'is_active' => true,
            'tags' => ['marketing'],
        ]);

        // Score 3.8 / 4 = 95% -> >= 80 quarantine threshold
        $mockClient = $this->makeMockClient(verdict: 'phishing', score: 3.8, confidence: 0.98);
        $service = new AiThreatDetectionService($mockClient);

        $evaluation = $service->evaluateLink($link);

        $this->assertNotNull($evaluation);
        $this->assertEquals('quarantined', $evaluation->action_taken);
        $this->assertEquals('phishing', $evaluation->verdict);
        $this->assertEquals(95, $evaluation->threat_score);

        $link->refresh();
        $this->assertFalse($link->is_active);
        $this->assertContains('quarantine', $link->tags);
        $this->assertContains('phishing', $link->tags);
        $this->assertContains('marketing', $link->tags);

        $this->assertDatabaseHas('threat_evaluations', [
            'id' => $evaluation->id,
            'link_id' => $link->id,
            'action_taken' => 'quarantined',
            'verdict' => 'phishing',
        ]);
    }

    public function test_spam_verdict_triggers_bot_protection_action(): void
    {
        $link = $this->createLink([
            'domain_id' => $this->domain->id,
            'destination_url' => 'https://seo-link-farm.example/buy-now',
            'is_active' => true,
            'bot_protection' => false,
        ]);

        // Score 2.8 / 4 = 70% -> >= 60 bot_protect threshold
        $mockClient = $this->makeMockClient(verdict: 'spam', score: 2.8, confidence: 0.85);
        $service = new AiThreatDetectionService($mockClient);

        $evaluation = $service->evaluateLink($link);

        $this->assertNotNull($evaluation);
        $this->assertEquals('bot_protected', $evaluation->action_taken);
        $this->assertEquals('spam', $evaluation->verdict);
        $this->assertEquals(70, $evaluation->threat_score);

        $link->refresh();
        $this->assertTrue($link->is_active);
        $this->assertTrue((bool) $link->bot_protection);
    }

    public function test_benign_verdict_leaves_link_cleared(): void
    {
        $link = $this->createLink([
            'domain_id' => $this->domain->id,
            'destination_url' => 'https://github.com/ternis-dev',
            'is_active' => true,
            'bot_protection' => false,
        ]);

        // Score 0.2 / 4 = 5%
        $mockClient = $this->makeMockClient(verdict: 'safe', score: 0.2, confidence: 0.99);
        $service = new AiThreatDetectionService($mockClient);

        $evaluation = $service->evaluateLink($link);

        $this->assertNotNull($evaluation);
        $this->assertEquals('cleared', $evaluation->action_taken);
        $this->assertEquals('safe', $evaluation->verdict);

        $link->refresh();
        $this->assertTrue($link->is_active);
        $this->assertFalse((bool) $link->bot_protection);
    }

    public function test_high_burst_velocity_quarantines_attack_campaign(): void
    {
        $ipHash = hash('sha256', '203.0.113.88');
        RateLimiter::clear('threat:burst:'.$ipHash);

        // Simulate 10 rapid link creations to reach limit
        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit('threat:burst:'.$ipHash, 60);
        }

        $link = $this->createLink([
            'domain_id' => $this->domain->id,
            'creator_ip_hash' => $ipHash,
            'is_active' => true,
        ]);

        $service = new AiThreatDetectionService;
        $evaluation = $service->evaluateLink($link);

        $this->assertNotNull($evaluation);
        $this->assertEquals('spam_burst', $evaluation->evaluation_type);
        $this->assertEquals('quarantined', $evaluation->action_taken);

        $link->refresh();
        $this->assertFalse($link->is_active);
    }

    public function test_user_agent_evaluation_returns_classification(): void
    {
        $mockClient = $this->makeMockClient(verdict: 'exploit_scanner', score: 4.0, confidence: 0.99);
        $service = new AiThreatDetectionService($mockClient);

        $result = $service->evaluateUserAgent('sqlmap/1.7.2#stable', '/wp-login.php');

        $this->assertEquals('exploit_scanner', $result['verdict']);
        $this->assertEquals(100, $result['score']);
    }

    public function test_evaluation_job_executes_service_cleanly(): void
    {
        $link = $this->createLink([
            'domain_id' => $this->domain->id,
            'destination_url' => 'https://suspicious.example/target',
            'is_active' => true,
        ]);

        $mockClient = $this->makeMockClient(verdict: 'phishing', score: 3.6, confidence: 0.95);
        $service = new AiThreatDetectionService($mockClient);
        $this->app->instance(AiThreatDetectionService::class, $service);

        $job = new EvaluateLinkThreatJob($link->id, ['user_agent' => 'Mozilla/5.0']);
        $job->handle($service);

        $link->refresh();
        $this->assertFalse($link->is_active);
        $this->assertDatabaseHas('threat_evaluations', [
            'link_id' => $link->id,
            'verdict' => 'phishing',
            'action_taken' => 'quarantined',
        ]);
    }

    public function test_gracefully_degrades_when_no_api_key_configured(): void
    {
        config(['ai_security.api_key' => null]);
        $service = new AiThreatDetectionService;

        $link = $this->createLink([
            'domain_id' => $this->domain->id,
            'destination_url' => 'https://example.com/ok',
            'is_active' => true,
        ]);

        $evaluation = $service->evaluateLink($link);
        $this->assertNull($evaluation);

        $link->refresh();
        $this->assertTrue($link->is_active);
    }
}
