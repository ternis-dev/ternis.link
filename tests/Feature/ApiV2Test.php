<?php

namespace Tests\Feature;

use App\Enums\ApiVersionStatus;
use App\Models\ApiKey;
use App\Models\ApiVersion;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiV2Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Domain $domain;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
        $this->user = User::factory()->create(['plan_id' => Plan::where('name', 'business')->firstOrFail()->id]);
        $this->domain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $this->headers = $this->keyFor($this->user);
    }

    private function keyFor(User $user): array
    {
        $token = 'tl_'.Str::random(48);
        ApiKey::create(['user_id' => $user->id, 'key_hash' => hash('sha256', $token),
            'key_prefix' => substr($token, 0, 8), 'api_version' => 1, 'name' => 'Test']);

        return ['Authorization' => 'Bearer '.$token];
    }

    private function payload(): array
    {
        return ['destination_url' => 'https://example.com/launch', 'domain_id' => $this->domain->id];
    }

    public function test_discovery_and_authentication_without_accept_header(): void
    {
        $this->get('http://links.t-api.de/v2/')->assertOk()->assertJsonPath('data.version', 2);
        $this->get('http://links.t-api.de/v2/me')->assertUnauthorized()
            ->assertJsonPath('error.code', 'authentication_required')->assertHeader('API-Version', '2');
        $this->get('http://links.t-api.de/v2/me', $this->headers)->assertOk()
            ->assertJsonPath('data.user_id', $this->user->id)
            ->assertJsonPath('data.auth_via', 'api-key')
            ->assertJsonPath('data.daily_link_quota.limit', null);
    }

    public function test_link_lifecycle_and_pagination(): void
    {
        $created = $this->postJson('http://links.t-api.de/v2/links', $this->payload(), $this->headers)
            ->assertCreated()->assertJsonPath('error', null)->assertJsonStructure(['data' => ['id', 'short_url']]);
        $id = $created->json('data.id');
        $this->assertDatabaseHas('links', ['id' => $id, 'api_key_id' => $created->json('data.api_key.id')]);
        $this->getJson('http://links.t-api.de/v2/links', $this->headers)->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)->assertJsonPath('meta.pagination.next_page', null);
        $this->getJson("http://links.t-api.de/v2/links/{$id}", $this->headers)->assertOk();
        $this->patchJson("http://links.t-api.de/v2/links/{$id}", ['description' => 'Updated'], $this->headers)
            ->assertOk()->assertJsonPath('data.description', 'Updated');
        $this->getJson("http://links.t-api.de/v2/links/{$id}/clicks/summary", $this->headers)
            ->assertOk()->assertJsonPath('data.total_clicks', 0);
        $this->deleteJson("http://links.t-api.de/v2/links/{$id}", [], $this->headers)
            ->assertOk()->assertJsonPath('data', null)->assertJsonPath('error', null);
        $this->assertDatabaseHas('links', ['id' => $id, 'is_active' => false]);
    }

    public function test_validation_not_found_and_forbidden_have_envelopes(): void
    {
        $this->postJson('http://links.t-api.de/v2/links', [], $this->headers)
            ->assertUnprocessable()->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['fields' => ['destination_url']]]);
        $this->get('http://links.t-api.de/v2/unknown')->assertNotFound()->assertJsonPath('error.code', 'not_found');
        $this->get('http://links.t-api.de/v2/links/'.Str::ulid(), $this->headers)
            ->assertNotFound()->assertJsonPath('error.code', 'not_found');
        $link = Link::create(['slug' => 'other-owner', 'destination_url' => 'https://example.com', 'user_id' => User::factory()->create()->id, 'domain_id' => $this->domain->id]);
        $this->getJson('http://links.t-api.de/v2/links/'.$link->id, $this->headers)
            ->assertForbidden()->assertJsonPath('error.code', 'forbidden');
        $this->getJson('http://href.nz/v2/me', $this->headers)->assertNotFound();
    }

    public function test_quota_is_account_wide_and_has_reset_metadata(): void
    {
        $plan = Plan::create(['name' => 'one', 'max_links_per_day' => 1, 'min_slug_length' => 6, 'rate_limit_per_minute' => 60]);
        $this->user->update(['plan_id' => $plan->id]);
        $this->postJson('http://links.t-api.de/v2/links', $this->payload(), $this->headers)->assertCreated();
        $secondKey = $this->keyFor($this->user);
        $this->postJson('http://links.t-api.de/v2/links', $this->payload(), $secondKey)
            ->assertStatus(429)->assertHeader('Retry-After')
            ->assertJsonPath('error.code', 'daily_link_limit_reached')
            ->assertJsonPath('error.quota.scope', 'account')->assertJsonPath('error.quota.remaining', 0);
        $this->getJson('http://links.t-api.de/v2/me', $secondKey)->assertOk()->assertJsonPath('data.daily_link_quota.used', 1);
        $this->postJson('http://links.t-api.de/v1/links', $this->payload(), $this->headers)
            ->assertUnprocessable()->assertJsonValidationErrors('destination_url');
        $this->travel(1)->days();
        $this->postJson('http://links.t-api.de/v2/links', $this->payload(), $this->headers)->assertCreated();
    }

    public function test_public_v1_honors_credentials_and_rejects_bad_credentials(): void
    {
        $this->postJson('http://links.t-api.de/v1/links/public', $this->payload(), $this->headers)
            ->assertCreated()->assertJsonPath('user_id', $this->user->id)->assertJsonStructure(['api_key' => ['id']]);
        $this->postJson('http://links.t-api.de/v1/links/public', $this->payload(), ['Authorization' => 'Bearer tl_bad'])
            ->assertUnauthorized();
        $this->postJson('http://links.t-api.de/v1/links/public', $this->payload(), ['Authorization' => 'Basic bad'])
            ->assertUnauthorized();
        $this->postJson('http://links.t-api.de/v1/links/public', $this->payload())
            ->assertCreated()->assertJsonPath('user_id', null);
    }

    public function test_unlimited_plan_still_validates_domain_and_retirement_works(): void
    {
        $this->domain->update(['is_active' => false]);
        $this->postJson('http://links.t-api.de/v2/links', $this->payload(), $this->headers)
            ->assertUnprocessable()->assertJsonStructure(['error' => ['fields' => ['domain_id']]]);
        ApiVersion::where('version', 2)->update(['status' => ApiVersionStatus::Retired]);
        Cache::flush();
        $this->getJson('http://links.t-api.de/v2/me')->assertStatus(410)
            ->assertJsonPath('error.code', 'api_version_retired')->assertHeader('API-Version', '2');
    }

    public function test_exhausted_guest_quota_does_not_apply_to_authenticated_public_creation(): void
    {
        $this->postJson('http://links.t-api.de/v1/links/public', $this->payload())->assertCreated();
        $guest = Link::whereNull('user_id')->firstOrFail();
        $rows = [];
        for ($i = 0; $i < 49; $i++) {
            $rows[] = ['id' => (string) Str::ulid(), 'slug' => 'guest-'.$i,
                'destination_url' => 'https://example.com', 'domain_id' => $this->domain->id,
                'creator_ip_hash' => $guest->creator_ip_hash, 'created_at' => now(), 'updated_at' => now()];
        }
        Link::insert($rows);
        $this->postJson('http://links.t-api.de/v1/links/public', $this->payload())
            ->assertUnprocessable();
        $this->postJson('http://links.t-api.de/v1/links/public', $this->payload(), $this->headers)
            ->assertCreated()->assertJsonPath('user_id', $this->user->id);
    }

    public function test_minute_throttle_is_wrapped_and_logged(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->get('http://links.t-api.de/v2/me', $this->headers)->assertOk();
        }
        $this->get('http://links.t-api.de/v2/me', $this->headers)->assertStatus(429)
            ->assertJsonPath('error.code', 'rate_limited')->assertHeader('Retry-After');
        $this->assertDatabaseHas('api_request_logs', ['path' => '/v2/me', 'status' => 429]);
    }

    public function test_v2_domains_exclude_foreign_domains(): void
    {
        $foreign = Domain::create(['hostname' => 'foreign.example.com', 'user_id' => User::factory()->create()->id,
            'type' => 'public', 'is_active' => true, 'verified_at' => now()]);
        $domains = $this->getJson('http://links.t-api.de/v2/domains', $this->headers)->assertOk()->json('data');
        $this->assertNotContains($foreign->id, array_column($domains, 'id'));
        $this->postJson('http://links.t-api.de/v2/links', [
            'destination_url' => 'https://example.com', 'domain_id' => $foreign->id,
        ], $this->headers)->assertUnprocessable()->assertJsonStructure(['error' => ['fields' => ['domain_id']]]);
    }

    public function test_v2_stats_are_public_and_aggregate_only(): void
    {
        Cache::flush();
        Domain::create(['hostname' => 'foreign.example.com', 'user_id' => $this->user->id,
            'type' => 'public', 'is_active' => true, 'verified_at' => now()]);

        $this->getJson('http://links.t-api.de/v2/stats')->assertOk()
            ->assertJsonStructure(['data' => ['total_links', 'total_clicks', 'clicks_today', 'qr_codes']]);
        $this->getJson('http://links.t-api.de/v2/stats/heatmaps')->assertOk()
            ->assertJsonStructure(['data' => ['hourly', 'calendar']]);
        $hosts = array_column($this->getJson('http://links.t-api.de/v2/stats/domains')->assertOk()->json('data'), 'hostname');
        $this->assertContains('href.nz', $hosts);
        $this->assertNotContains('foreign.example.com', $hosts);

        foreach (['creations', 'clicks', 'qr'] as $series) {
            $rows = $this->getJson("http://links.t-api.de/v2/stats/{$series}")->assertOk()->json('data');
            $this->assertCount(30, $rows);
            $this->assertSame(['date', 'count'], array_keys($rows[0]));
        }
    }

    public function test_v2_discovery_lists_scan_endpoint(): void
    {
        $this->getJson('http://links.t-api.de/v2/')
            ->assertOk()
            ->assertJsonPath('data.endpoints.scan', '/v2/scan');
    }

    public function test_v2_scan_requires_url(): void
    {
        $this->postJson('http://links.t-api.de/v2/scan', [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['fields' => ['url']]]);

        $this->getJson('http://links.t-api.de/v2/scan')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_v2_scan_inspects_url_safely(): void
    {
        $response = $this->postJson('http://links.t-api.de/v2/scan', [
            'url' => 'https://example.com/safe-page',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.url', 'https://example.com/safe-page')
            ->assertJsonPath('data.host', 'example.com')
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonStructure(['data' => ['category', 'threat_score', 'confidence', 'action_recommended']]);

        // GET query works identically
        $getResponse = $this->getJson('http://links.t-api.de/v2/scan?url='.urlencode('https://example.com/safe-page'));
        $getResponse->assertOk()
            ->assertJsonPath('data.cached', true);
    }
}
