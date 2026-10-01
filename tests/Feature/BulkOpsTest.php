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

class BulkOpsTest extends TestCase
{
    use RefreshDatabase;

    private Domain $domain;

    private User $user;

    private string $rawApiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
        $this->domain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $this->user = User::factory()->create();

        $this->rawApiKey = 'tl_'.Str::random(48);
        ApiKey::create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $this->rawApiKey),
            'key_prefix' => substr($this->rawApiKey, 0, 8),
            'api_version' => 1,
            'name' => 'Test Key',
        ]);
    }

    public function test_import_and_status(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/links/import', [
            'rows' => [
                ['destination_url' => 'https://example.com/a', 'domain_id' => $this->domain->id],
                ['destination_url' => 'not-a-url', 'domain_id' => $this->domain->id],
            ],
        ], ['Authorization' => "Bearer {$this->rawApiKey}"]);

        // Second row fails request validation → 422 whole batch (fail fast on shape).
        $response->assertStatus(422);

        $ok = $this->postJson('http://links.t-api.de/v1/links/import', [
            'rows' => [
                ['destination_url' => 'https://example.com/a', 'domain_id' => $this->domain->id],
                ['destination_url' => 'https://example.com/b', 'domain_id' => $this->domain->id],
            ],
        ], ['Authorization' => "Bearer {$this->rawApiKey}"]);

        $ok->assertStatus(202);
        $opId = $ok->json('id');

        $this->getJson("http://links.t-api.de/v1/bulk-operations/{$opId}", ['Authorization' => "Bearer {$this->rawApiKey}"])
            ->assertOk();
    }

    public function test_bulk_deactivate_partial(): void
    {
        $owned = Link::create([
            'slug' => 'bulk01',
            'destination_url' => 'https://example.com/1',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $response = $this->postJson('http://links.t-api.de/v1/links/bulk', [
            'ids' => [$owned->id, '01JXXXXXXXXXXXXXXXXXXXXXXXXX'],
            'action' => 'deactivate',
        ], ['Authorization' => "Bearer {$this->rawApiKey}"]);

        $response->assertStatus(202);
    }

    public function test_concurrent_guard(): void
    {
        \App\Models\BulkOperation::create([
            'user_id' => $this->user->id,
            'type' => 'import',
            'status' => 'pending',
            'total' => 1,
        ]);

        $this->postJson('http://links.t-api.de/v1/links/import', [
            'rows' => [['destination_url' => 'https://example.com/b', 'domain_id' => $this->domain->id]],
        ], ['Authorization' => "Bearer {$this->rawApiKey}"])->assertStatus(409);
    }
}
