<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $rawApiKey;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
        $this->domain = Domain::where('hostname', 'href.nz')->first();

        $this->rawApiKey = 'tl_'.Str::random(48);
        ApiKey::create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $this->rawApiKey),
            'key_prefix' => substr($this->rawApiKey, 0, 8),
            'api_version' => 1,
            'name' => 'Test Key',
        ]);
    }

    public function test_api_requires_authentication(): void
    {
        $response = $this->getJson('http://links.t-api.de/v1/links');

        $response->assertStatus(401);
    }

    public function test_api_authenticates_with_bearer_api_key(): void
    {
        $response = $this->getJson('http://links.t-api.de/v1/links', [
            'Authorization' => "Bearer {$this->rawApiKey}",
        ]);

        $response->assertStatus(200);
    }

    public function test_create_link_via_api(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/links', [
            'destination_url' => 'https://example.com/api-test',
            'domain_id' => $this->domain->id,
            'slug' => 'api_slug_test',
        ], [
            'Authorization' => "Bearer {$this->rawApiKey}",
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'slug' => 'api_slug_test',
            'destination_url' => 'https://example.com/api-test',
        ]);

        $this->assertDatabaseHas('links', [
            'slug' => 'api_slug_test',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_show_link_via_api(): void
    {
        $link = Link::create([
            'slug' => 'showme123',
            'destination_url' => 'https://example.com',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $response = $this->getJson("http://links.t-api.de/v1/links/{$link->id}", [
            'Authorization' => "Bearer {$this->rawApiKey}",
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['slug' => 'showme123']);
    }

    public function test_update_link_via_api(): void
    {
        $link = Link::create([
            'slug' => 'updateme123',
            'destination_url' => 'https://example.com',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $response = $this->putJson("http://links.t-api.de/v1/links/{$link->id}", [
            'destination_url' => 'https://example.org/updated',
        ], [
            'Authorization' => "Bearer {$this->rawApiKey}",
        ]);

        $response->assertStatus(200);
        $this->assertEquals('https://example.org/updated', $link->fresh()->destination_url);
    }

    public function test_deactivate_link_via_api(): void
    {
        $link = Link::create([
            'slug' => 'deleteme123',
            'destination_url' => 'https://example.com',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $response = $this->deleteJson("http://links.t-api.de/v1/links/{$link->id}", [], [
            'Authorization' => "Bearer {$this->rawApiKey}",
        ]);

        $response->assertStatus(204);
        $this->assertFalse((bool) $link->fresh()->is_active);
    }

    public function test_clicks_and_summary_endpoint(): void
    {
        $link = Link::create([
            'slug' => 'analyticstest',
            'destination_url' => 'https://example.com',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $response = $this->getJson("http://links.t-api.de/v1/links/{$link->id}/clicks/summary", [
            'Authorization' => "Bearer {$this->rawApiKey}",
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_clicks',
            'unique_visitors',
            'top_referrers',
            'top_countries',
            'clicks_by_day',
        ]);
    }
}
