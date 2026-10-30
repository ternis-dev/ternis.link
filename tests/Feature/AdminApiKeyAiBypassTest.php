<?php

namespace Tests\Feature;

use App\Jobs\EvaluateLinkThreatJob;
use App\Livewire\Dashboard\ApiKeyManager;
use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\Link;
use App\Models\ThreatEvaluation;
use App\Models\User;
use App\Services\AiThreatDetectionService;
use App\Services\LinkService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AdminApiKeyAiBypassTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $regularUser;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->admin = User::factory()->admin()->create();
        $this->regularUser = User::factory()->create();
        $this->domain = Domain::where('hostname', 'href.nz')->firstOrFail();
    }

    private function headersFor(User $user): array
    {
        $raw = 'tl_'.Str::random(48);
        ApiKey::create([
            'user_id' => $user->id,
            'key_hash' => hash('sha256', $raw),
            'key_prefix' => substr($raw, 0, 8),
            'api_version' => 1,
            'name' => 'Auth Key',
        ]);

        return ['Authorization' => "Bearer {$raw}"];
    }

    public function test_admin_can_create_api_key_with_ai_bypass_via_api(): void
    {
        $headers = $this->headersFor($this->admin);

        $response = $this->postJson('http://links.t-api.de/v1/api-keys', [
            'name' => 'Admin Bot Key',
            'bypass_ai_check' => true,
        ], $headers);

        $response->assertStatus(201);
        $this->assertTrue($response->json('bypass_ai_check'));

        $key = ApiKey::where('name', 'Admin Bot Key')->firstOrFail();
        $this->assertTrue($key->bypass_ai_check);
        $this->assertTrue($key->shouldBypassAi());
    }

    public function test_regular_user_cannot_set_ai_bypass_on_api_key(): void
    {
        $headers = $this->headersFor($this->regularUser);

        $response = $this->postJson('http://links.t-api.de/v1/api-keys', [
            'name' => 'Regular User Key',
            'bypass_ai_check' => true,
        ], $headers);

        $response->assertStatus(201);
        $this->assertFalse($response->json('bypass_ai_check'));

        $key = ApiKey::where('name', 'Regular User Key')->firstOrFail();
        $this->assertFalse($key->bypass_ai_check);
        $this->assertFalse($key->shouldBypassAi());
    }

    public function test_admin_can_update_ai_bypass_via_api(): void
    {
        $headers = $this->headersFor($this->admin);

        $key = ApiKey::create([
            'user_id' => $this->admin->id,
            'key_hash' => ApiKey::hashToken('tl_testkey12345678901234567890123456789012345678901234'),
            'key_prefix' => 'tl_testk',
            'api_version' => 2,
            'name' => 'Initial Key',
            'bypass_ai_check' => false,
        ]);

        $this->patchJson("http://links.t-api.de/v1/api-keys/{$key->id}", [
            'bypass_ai_check' => true,
        ], $headers)->assertOk();

        $this->assertTrue($key->fresh()->bypass_ai_check);
        $this->assertTrue($key->fresh()->shouldBypassAi());
    }

    public function test_regular_user_cannot_update_ai_bypass(): void
    {
        $headers = $this->headersFor($this->regularUser);

        $key = ApiKey::create([
            'user_id' => $this->regularUser->id,
            'key_hash' => ApiKey::hashToken('tl_regular12345678901234567890123456789012345678901234'),
            'key_prefix' => 'tl_regul',
            'api_version' => 2,
            'name' => 'Regular Key',
            'bypass_ai_check' => false,
        ]);

        $this->patchJson("http://links.t-api.de/v1/api-keys/{$key->id}", [
            'bypass_ai_check' => true,
        ], $headers)->assertOk();

        $this->assertFalse($key->fresh()->bypass_ai_check);
    }

    public function test_non_admin_key_with_flag_in_db_still_does_not_bypass(): void
    {
        $key = ApiKey::create([
            'user_id' => $this->regularUser->id,
            'key_hash' => ApiKey::hashToken('tl_regularflagged1234567890123456789012345678901234567'),
            'key_prefix' => 'tl_regfl',
            'api_version' => 2,
            'name' => 'Tampered Key',
            'bypass_ai_check' => true,
        ]);

        $this->assertFalse($key->shouldBypassAi());
    }

    public function test_link_created_with_admin_bypass_key_skips_ai_evaluation_queue(): void
    {
        Queue::fake([EvaluateLinkThreatJob::class]);

        $bypassKey = ApiKey::create([
            'user_id' => $this->admin->id,
            'key_hash' => ApiKey::hashToken('tl_adminbypass1234567890123456789012345678901234567890'),
            'key_prefix' => 'tl_admin',
            'api_version' => 2,
            'name' => 'Admin Bypass Key',
            'bypass_ai_check' => true,
        ]);

        $linkService = app(LinkService::class);

        $link = $linkService->create(
            destinationUrl: 'https://example.com/security-update',
            domain: $this->domain,
            user: $this->admin,
            apiKey: $bypassKey,
        );

        Queue::assertNotPushed(EvaluateLinkThreatJob::class);

        $threatService = app(AiThreatDetectionService::class);
        $this->assertFalse($threatService->shouldEvaluateLink($link));
        $this->assertNull($threatService->evaluateLink($link));
        $this->assertSame(0, ThreatEvaluation::where('link_id', $link->id)->count());
    }

    public function test_link_created_with_normal_key_still_evaluates(): void
    {
        Queue::fake([EvaluateLinkThreatJob::class]);

        $normalKey = ApiKey::create([
            'user_id' => $this->admin->id,
            'key_hash' => ApiKey::hashToken('tl_adminnormal1234567890123456789012345678901234567890'),
            'key_prefix' => 'tl_admnr',
            'api_version' => 2,
            'name' => 'Admin Normal Key',
            'bypass_ai_check' => false,
        ]);

        $linkService = app(LinkService::class);

        $link = $linkService->create(
            destinationUrl: 'https://example.com/security-update',
            domain: $this->domain,
            user: $this->admin,
            apiKey: $normalKey,
        );

        Queue::assertPushed(EvaluateLinkThreatJob::class, function ($job) use ($link) {
            return $job->linkId === $link->id;
        });
    }

    public function test_evaluate_link_threat_job_skips_if_link_has_bypass_key(): void
    {
        $bypassKey = ApiKey::create([
            'user_id' => $this->admin->id,
            'key_hash' => ApiKey::hashToken('tl_jobtestbypass12345678901234567890123456789012345678'),
            'key_prefix' => 'tl_jobtp',
            'api_version' => 2,
            'name' => 'Job Test Key',
            'bypass_ai_check' => true,
        ]);

        $link = Link::create([
            'slug' => 'bypassjob1',
            'destination_url' => 'https://example.com/target',
            'domain_id' => $this->domain->id,
            'user_id' => $this->admin->id,
            'api_key_id' => $bypassKey->id,
            'is_active' => true,
        ]);

        $threatMock = \Mockery::mock(AiThreatDetectionService::class);
        $threatMock->shouldNotReceive('evaluateLink');

        $job = new EvaluateLinkThreatJob($link->id);
        $job->handle($threatMock);
    }

    public function test_admin_can_toggle_ai_bypass_in_livewire_manager(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ApiKeyManager::class)
            ->set('keyName', 'Livewire Admin Key')
            ->set('newKeyBypassAi', true)
            ->call('createKey')
            ->assertHasNoErrors();

        $key = ApiKey::where('name', 'Livewire Admin Key')->firstOrFail();
        $this->assertTrue($key->bypass_ai_check);

        // Edit in settings modal
        Livewire::actingAs($this->admin)
            ->test(ApiKeyManager::class)
            ->call('openSettings', $key->id)
            ->assertSet('settingsBypassAi', true)
            ->set('settingsBypassAi', false)
            ->call('saveSettings')
            ->assertHasNoErrors();

        $this->assertFalse($key->fresh()->bypass_ai_check);
    }
}
