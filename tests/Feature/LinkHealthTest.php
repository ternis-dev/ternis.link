<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\SecurityAlert;
use App\Services\LinkHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class LinkHealthTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\ApiVersionSeeder::class);

        Plan::create([
            'name' => 'pro',
            'max_links_per_day' => 1000,
            'min_slug_length' => 4,
        ]);

        $this->user = User::factory()->create([
            'role' => 'user',
            'plan_id' => Plan::where('name', 'pro')->first()->id,
        ]);

        $this->domain = Domain::create([
            'hostname' => 'href.nz',
            'type' => 'public',
            'is_active' => true,
        ]);
    }

    public function test_health_service_probes_healthy_destination(): void
    {
        Http::fake([
            'https://example.com/ok' => Http::response('OK', 200),
        ]);

        $link = Link::create([
            'user_id' => $this->user->id,
            'domain_id' => $this->domain->id,
            'slug' => 'test-healthy',
            'destination_url' => 'https://example.com/ok',
            'is_active' => true,
            'health_check_enabled' => true,
        ]);

        $service = app(LinkHealthService::class);
        $result = $service->check($link);

        $this->assertEquals('healthy', $result['status']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertNull($result['error']);
        $this->assertGreaterThanOrEqual(0, $result['response_time_ms']);

        $link->refresh();
        $this->assertTrue($link->isHealthy());
        $this->assertEquals('healthy', $link->health_status);
        $this->assertEquals(200, $link->health_status_code);
        $this->assertNotNull($link->last_health_check_at);
    }

    public function test_health_service_detects_degraded_status(): void
    {
        Http::fake([
            'https://example.com/degraded' => Http::response('Service Unavailable', 503),
        ]);

        $link = Link::create([
            'user_id' => $this->user->id,
            'domain_id' => $this->domain->id,
            'slug' => 'test-degraded',
            'destination_url' => 'https://example.com/degraded',
            'is_active' => true,
            'health_check_enabled' => true,
        ]);

        $service = app(LinkHealthService::class);
        $result = $service->check($link);

        $this->assertEquals('degraded', $result['status']);
        $this->assertEquals(503, $result['status_code']);

        $link->refresh();
        $this->assertTrue($link->isDegraded());
        $this->assertEquals('degraded', $link->health_status);
    }

    public function test_health_service_detects_unreachable_and_notifies_owner(): void
    {
        Notification::fake();
        Cache::flush();

        Http::fake([
            'https://example.com/down' => Http::response('Not Found', 404),
        ]);

        $link = Link::create([
            'user_id' => $this->user->id,
            'domain_id' => $this->domain->id,
            'slug' => 'test-down',
            'destination_url' => 'https://example.com/down',
            'is_active' => true,
            'health_check_enabled' => true,
        ]);

        $service = app(LinkHealthService::class);
        $result = $service->check($link);

        $this->assertEquals('unreachable', $result['status']);
        $this->assertEquals(404, $result['status_code']);

        $link->refresh();
        $this->assertTrue($link->isUnreachable());
        $this->assertEquals('unreachable', $link->health_status);

        Notification::assertSentTo($this->user, SecurityAlert::class, function ($notification) {
            return str_contains($notification->title, 'Destination Offline');
        });

        // Test notification throttling (second check within throttle window does not re-notify)
        Notification::fake();
        $service->check($link);
        Notification::assertNothingSent();
    }

    public function test_check_link_health_artisan_command(): void
    {
        Http::fake([
            'https://example.com/link1' => Http::response('OK', 200),
            'https://example.com/link2' => Http::response('Error', 500),
            'https://example.com/link-ignored' => Http::response('OK', 200),
        ]);

        $optedIn = Link::create([
            'user_id' => $this->user->id,
            'domain_id' => $this->domain->id,
            'slug' => 'health-1',
            'destination_url' => 'https://example.com/link1',
            'is_active' => true,
            'health_check_enabled' => true,
        ]);

        $degradedLink = Link::create([
            'user_id' => $this->user->id,
            'domain_id' => $this->domain->id,
            'slug' => 'health-2',
            'destination_url' => 'https://example.com/link2',
            'is_active' => true,
            'health_check_enabled' => true,
        ]);

        $optedOut = Link::create([
            'user_id' => $this->user->id,
            'domain_id' => $this->domain->id,
            'slug' => 'health-ignored',
            'destination_url' => 'https://example.com/link-ignored',
            'is_active' => true,
            'health_check_enabled' => false,
        ]);

        $this->artisan('links:check-health', ['--limit' => 10, '--delay-ms' => 0])
            ->expectsOutputToContain('Health check run finished: 2 processed')
            ->assertSuccessful();

        $optedIn->refresh();
        $this->assertEquals('healthy', $optedIn->health_status);

        $degradedLink->refresh();
        $this->assertEquals('degraded', $degradedLink->health_status);

        $optedOut->refresh();
        $this->assertNull($optedOut->last_health_check_at);
    }

    public function test_link_table_livewire_can_trigger_health_check_on_demand(): void
    {
        Http::fake([
            'https://example.com/on-demand' => Http::response('OK', 200),
        ]);

        $link = Link::create([
            'user_id' => $this->user->id,
            'domain_id' => $this->domain->id,
            'slug' => 'on-demand-slug',
            'destination_url' => 'https://example.com/on-demand',
            'is_active' => true,
            'health_check_enabled' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Dashboard\LinkTable::class)
            ->call('checkHealth', $link->id)
            ->assertDispatched('notify');

        $link->refresh();
        $this->assertTrue($link->health_check_enabled);
        $this->assertEquals('healthy', $link->health_status);
        $this->assertEquals(200, $link->health_status_code);
    }

    public function test_api_can_store_and_update_health_check_setting(): void
    {
        $rawKey = 'tl_'.\Illuminate\Support\Str::random(48);
        \App\Models\ApiKey::create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $rawKey),
            'key_prefix' => substr($rawKey, 0, 8),
            'api_version' => 1,
            'name' => 'Health Key',
        ]);

        $apiHost = config('domains.api_v1_host', 'links.t-api.de');

        $response = $this->postJson("http://{$apiHost}/v1/links", [
            'destination_url' => 'https://example.com/api-health',
            'domain_id' => $this->domain->id,
            'health_check_enabled' => true,
        ], [
            'Authorization' => "Bearer {$rawKey}",
        ]);

        $response->assertCreated();
        $response->assertJsonPath('health_check_enabled', true);

        $linkId = $response->json('id');
        $link = Link::findOrFail($linkId);
        $this->assertTrue($link->health_check_enabled);

        // Update to disable
        $updateResponse = $this->putJson("http://{$apiHost}/v1/links/{$linkId}", [
            'health_check_enabled' => false,
        ], [
            'Authorization' => "Bearer {$rawKey}",
        ]);

        $updateResponse->assertOk();
        $link->refresh();
        $this->assertFalse($link->health_check_enabled);
    }
}
