<?php

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Enums\UserRole;
use App\Models\Domain;
use App\Models\Link;
use App\Models\SpamAppeal;
use App\Models\User;
use App\Notifications\SecurityAlert;
use App\Services\AiThreatDetectionService;
use App\Services\BotProtectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use TypeSafeAI\DTO\ChoiceAnswer;
use TypeSafeAI\DTO\ScoreAnswer;
use TypeSafeAI\SystemOneClient;
use TypeSafeAI\SystemOneRequest;
use TypeSafeAI\SystemOneResult;

class SpamAppealAndBotConfigTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin->value,
            'email' => 'admin@ternis.link',
        ]);

        $this->user = User::factory()->create([
            'role' => UserRole::User->value,
            'email' => 'user@ternis.link',
        ]);

        $this->domain = Domain::create([
            'hostname' => 'href.nz',
            'type' => DomainType::Public,
            'is_active' => true,
        ]);
    }

    public function test_link_can_be_configured_to_allow_anyone_including_bots(): void
    {
        $link = Link::create([
            'slug' => 'open-bot',
            'destination_url' => 'https://example.com/api/data',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'bot_protection' => true,
            'allow_bots' => true,
        ]);

        $this->assertTrue($link->allowsBots());

        $botService = app(BotProtectionService::class);
        $request = Request::create('https://href.nz/open-bot', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'curl/8.1.2 python-requests/2.31',
        ]);

        // When allow_bots is true, shouldChallenge must return false unconditionally
        $this->assertFalse($botService->shouldChallenge($link, $request));
    }

    public function test_link_without_allow_bots_is_challenged_when_bot_protection_is_active(): void
    {
        $link = Link::create([
            'slug' => 'protected-link',
            'destination_url' => 'https://example.com/secret',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'bot_protection' => true,
            'allow_bots' => false,
        ]);

        $this->assertFalse($link->allowsBots());

        $botService = app(BotProtectionService::class);
        $request = Request::create('https://href.nz/protected-link', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
        ]);

        $this->assertTrue($botService->shouldChallenge($link, $request));
    }

    public function test_user_receives_notification_when_ai_quarantines_link_as_spam_or_malware(): void
    {
        Notification::fake();

        $link = Link::create([
            'slug' => 'bad-download',
            'destination_url' => 'https://malicious-site.com/trojan.exe',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
            'tags' => ['initial'],
        ]);

        $mockClient = new class implements SystemOneClient
        {
            public function systemOne(SystemOneRequest $request): SystemOneResult
            {
                $result = new SystemOneResult;
                $choice = new ChoiceAnswer;
                $choice->choice = 'malware';
                $choice->confidence = 0.99;
                $choice->probabilities = ['malware' => 0.99];

                $scoreAnswer = new ScoreAnswer;
                $scoreAnswer->score = 4.0;
                $scoreAnswer->confidence = 0.99;
                $scoreAnswer->legend = ['a', 'b', 'c', 'd', 'e'];
                $scoreAnswer->probabilities = [4 => 1.0];

                $result->answers = [
                    'verdict' => $choice,
                    'threat_score' => $scoreAnswer,
                ];

                return $result;
            }

            public function evaluate(string|array|object $state, string $class, ?string $model = null): object
            {
                throw new \BadMethodCallException('Not implemented');
            }
        };

        $threatService = new AiThreatDetectionService($mockClient);
        $threatService->evaluateLink($link, [
            'creator_ip_hash' => hash('sha256', '127.0.0.1'),
        ]);

        // Since it's a suspicious binary extension (.exe), it gets quarantined
        $link->refresh();
        $this->assertFalse($link->is_active);
        $this->assertContains('quarantine', $link->tags);

        // Assert notification was sent to user
        Notification::assertSentTo(
            $this->user,
            SecurityAlert::class,
            function ($notification) {
                return str_contains($notification->title, 'Security Alert: Link Quarantined')
                    && str_contains($notification->lines[0], 'trojan.exe');
            }
        );
    }

    public function test_user_can_submit_spam_appeal(): void
    {
        $link = Link::create([
            'slug' => 'false-positive',
            'destination_url' => 'https://my-legit-tool.com/app',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => false,
            'tags' => ['quarantine', 'spam'],
        ]);

        $response = $this->actingAs($this->user)
            ->get("http://dash.ternis.link/appeals/create?link={$link->id}");

        $response->assertStatus(200);
        $response->assertSee('Spam &amp; Threat Appeal', escape: false);
        $response->assertSee('href.nz/false-positive');

        // Submit appeal
        $postResponse = $this->actingAs($this->user)
            ->post('http://dash.ternis.link/appeals', [
                'link_id' => $link->id,
                'contact_email' => 'user@ternis.link',
                'reason' => 'This is my personal developer portfolio and utility tool, not spam.',
            ]);

        $postResponse->assertRedirect(route('dashboard.links'));

        $this->assertDatabaseHas('spam_appeals', [
            'link_id' => $link->id,
            'user_id' => $this->user->id,
            'contact_email' => 'user@ternis.link',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_approve_spam_appeal_and_restore_link(): void
    {
        Notification::fake();

        $link = Link::create([
            'slug' => 'restored-link',
            'destination_url' => 'https://legit-site.com/docs',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => false,
            'tags' => ['quarantine', 'spam'],
        ]);

        $appeal = SpamAppeal::create([
            'link_id' => $link->id,
            'user_id' => $this->user->id,
            'contact_email' => 'user@ternis.link',
            'reason' => 'Legitimate open-source documentation.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)
            ->post("http://admin.ternis.link/appeals/{$appeal->id}/approve", [
                'admin_notes' => 'Verified as legitimate documentation.',
            ]);

        $response->assertRedirect();
        $appeal->refresh();
        $this->assertTrue($appeal->isApproved());
        $this->assertEquals($this->admin->id, $appeal->reviewed_by_id);

        $link->refresh();
        $this->assertTrue($link->is_active);
        $this->assertNotContains('quarantine', $link->tags);
        $this->assertContains('appeal_approved', $link->tags);

        Notification::assertSentTo(
            $this->user,
            SecurityAlert::class,
            function ($notification) {
                return str_contains($notification->title, 'Spam Appeal Approved');
            }
        );
    }

    public function test_admin_can_reject_spam_appeal(): void
    {
        $link = Link::create([
            'slug' => 'rejected-link',
            'destination_url' => 'https://actual-malware.com/bad',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => false,
            'tags' => ['quarantine', 'malware'],
        ]);

        $appeal = SpamAppeal::create([
            'link_id' => $link->id,
            'user_id' => $this->user->id,
            'contact_email' => 'user@ternis.link',
            'reason' => 'Please unblock me.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)
            ->post("http://admin.ternis.link/appeals/{$appeal->id}/reject", [
                'admin_notes' => 'Confirmed malicious distribution.',
            ]);

        $response->assertRedirect();
        $appeal->refresh();
        $this->assertTrue($appeal->isRejected());
        $this->assertEquals('Confirmed malicious distribution.', $appeal->admin_notes);

        $link->refresh();
        $this->assertFalse($link->is_active);
        $this->assertContains('quarantine', $link->tags);
    }
}
