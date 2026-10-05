<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\ApiKeyManager;
use App\Livewire\Dashboard\LinkTable;
use App\Models\ActivityLog;
use App\Models\ApiKey;
use App\Models\ApiRequestLog;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ApiKeyLinksTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Domain $domain;

    private string $rawApiKey;

    private ApiKey $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
        $this->domain = Domain::where('hostname', 'href.nz')->first();

        $this->rawApiKey = 'tl_'.Str::random(48);
        $this->apiKey = ApiKey::create([
            'user_id' => $this->user->id,
            'key_hash' => ApiKey::hashToken($this->rawApiKey),
            'key_prefix' => substr($this->rawApiKey, 0, 8),
            'api_version' => 1,
            'name' => 'Test Key',
        ]);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->rawApiKey}"];
    }

    public function test_api_link_creation_attributes_api_key_and_logs_activity(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/links', [
            'destination_url' => 'https://example.com/key-attributed',
            'domain_id' => $this->domain->id,
            'slug' => 'keyattr1',
        ], $this->authHeaders());

        $response->assertStatus(201);

        $link = Link::where('slug', 'keyattr1')->firstOrFail();
        $this->assertEquals($this->apiKey->id, $link->api_key_id);

        $entry = ActivityLog::where('action', ActivityLog::LINK_CREATED)
            ->where('subject_id', $link->id)
            ->firstOrFail();
        $this->assertEquals('api', $entry->metadata['via']);
        $this->assertEquals($this->apiKey->id, $entry->metadata['api_key_id']);
        $this->assertEquals('Test Key', $entry->metadata['api_key_name']);
        $this->assertEquals($this->apiKey->key_prefix, $entry->metadata['api_key_prefix']);
    }

    public function test_every_api_request_is_logged(): void
    {
        $this->assertSame(0, ApiRequestLog::count());

        $this->getJson('http://links.t-api.de/v1/links', $this->authHeaders())->assertOk();
        $this->postJson('http://links.t-api.de/v1/links', [
            'destination_url' => 'https://example.com/logged',
            'domain_id' => $this->domain->id,
        ], $this->authHeaders())->assertCreated();
        // Failed auth attempts leave a trace too (no user/key attached).
        $this->getJson('http://links.t-api.de/v1/links')->assertUnauthorized();

        $this->assertSame(3, ApiRequestLog::count());

        $authed = ApiRequestLog::where('method', 'GET')->where('path', '/v1/links')->firstOrFail();
        $this->assertEquals($this->user->id, $authed->user_id);
        $this->assertEquals($this->apiKey->id, $authed->api_key_id);
        $this->assertEquals(200, $authed->status);
        $this->assertNotNull($authed->ip_hash);
        // No query strings, no tokens, no raw IPs on the row.
        $this->assertStringNotContainsString('?', $authed->path);
        $this->assertStringNotContainsString('tl_', json_encode($authed->toArray()));

        $created = ApiRequestLog::where('method', 'POST')->firstOrFail();
        $this->assertEquals(201, $created->status);

        $rejected = ApiRequestLog::where('status', 401)->firstOrFail();
        $this->assertNull($rejected->user_id);
        $this->assertNull($rejected->api_key_id);
    }

    public function test_links_index_filters_by_api_key(): void
    {
        $keyed = Link::create([
            'slug' => 'keyed123', 'destination_url' => 'https://example.com/a',
            'domain_id' => $this->domain->id, 'user_id' => $this->user->id,
            'api_key_id' => $this->apiKey->id, 'is_active' => true,
        ]);
        $plain = Link::create([
            'slug' => 'plain123', 'destination_url' => 'https://example.com/b',
            'domain_id' => $this->domain->id, 'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $byKey = $this->getJson(
            "http://links.t-api.de/v1/links?api_key_id={$this->apiKey->id}",
            $this->authHeaders()
        )->assertOk()->json('data');
        $this->assertEquals([$keyed->id], array_column($byKey, 'id'));

        $dashboardOnly = $this->getJson(
            'http://links.t-api.de/v1/links?api_key_id=none',
            $this->authHeaders()
        )->assertOk()->json('data');
        $this->assertEquals([$plain->id], array_column($dashboardOnly, 'id'));

        // A foreign key is not enumerable through the filter.
        $other = User::factory()->create();
        $raw = 'tl_'.Str::random(48);
        $foreign = ApiKey::create([
            'user_id' => $other->id, 'key_hash' => ApiKey::hashToken($raw),
            'key_prefix' => substr($raw, 0, 8), 'api_version' => 1, 'name' => 'Foreign',
        ]);
        $this->getJson(
            "http://links.t-api.de/v1/links?api_key_id={$foreign->id}",
            $this->authHeaders()
        )->assertForbidden();
    }

    public function test_hidden_key_links_leave_main_dashboard_for_per_key_page(): void
    {
        $this->apiKey->update(['show_on_dashboard' => false]);

        $hidden = Link::create([
            'slug' => 'hidden123', 'destination_url' => 'https://example.com/h',
            'domain_id' => $this->domain->id, 'user_id' => $this->user->id,
            'api_key_id' => $this->apiKey->id, 'is_active' => true,
        ]);
        $shown = Link::create([
            'slug' => 'shown123', 'destination_url' => 'https://example.com/s',
            'domain_id' => $this->domain->id, 'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        // Default dashboard table hides the key's links…
        $default = Livewire::actingAs($this->user)->test(LinkTable::class);
        $default->assertSee($shown->slug)->assertDontSee($hidden->slug);

        // …the explicit filter (and the locked per-key page) shows them.
        Livewire::actingAs($this->user)->test(LinkTable::class)
            ->set('apiKeyFilter', $this->apiKey->id)
            ->assertSee($hidden->slug);
        Livewire::actingAs($this->user)->test(LinkTable::class, ['apiKeyId' => $this->apiKey->id])
            ->assertSee($hidden->slug);

        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/api-keys/{$this->apiKey->id}")
            ->assertOk()
            ->assertSee($this->apiKey->name)
            ->assertSee($hidden->slug);
    }

    public function test_link_pages_keep_per_key_back_link(): void
    {
        $dashDomain = Domain::where('hostname', 'clicked.at')->firstOrFail();
        $link = Link::create([
            'slug' => 'backlink1', 'destination_url' => 'https://example.com',
            'domain_id' => $dashDomain->id, 'user_id' => $this->user->id,
            'api_key_id' => $this->apiKey->id, 'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$link->id}?from_api_key={$this->apiKey->id}")
            ->assertOk()
            ->assertSee('Back to API key links');

        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$link->id}")
            ->assertOk()
            ->assertSee('Back to Links');

        // A foreign key never changes the back-link target.
        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$link->id}?from_api_key=01K9999999999999999999999")
            ->assertOk()
            ->assertSee('Back to Links');
    }

    public function test_api_key_visibility_toggle_via_api_and_dashboard(): void
    {
        $this->patchJson("http://links.t-api.de/v1/api-keys/{$this->apiKey->id}", [
            'show_on_dashboard' => false,
        ], $this->authHeaders())->assertOk()->assertJson(['show_on_dashboard' => false]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => ActivityLog::API_KEY_UPDATED,
            'subject_id' => $this->apiKey->id,
        ]);

        Livewire::actingAs($this->user)->test(ApiKeyManager::class)
            ->call('toggleVisibility', $this->apiKey->id)
            ->assertHasNoErrors();

        $this->assertTrue($this->apiKey->fresh()->show_on_dashboard);
    }

    public function test_privacy_policy_covers_api_logging(): void
    {
        $policy = file_get_contents(resource_path('legal/privacy.md'));
        $this->assertStringContainsString('API request log', $policy);
        $this->assertStringContainsString('not on individual request', $policy);
        $this->assertStringContainsString('attribution', strtolower($policy));
    }
}
