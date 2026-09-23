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

class ApiPolishTest extends TestCase
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
        $this->domain = Domain::where('hostname', 'href.nz')->firstOrFail();

        $this->rawApiKey = 'tl_'.Str::random(48);
        ApiKey::create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $this->rawApiKey),
            'key_prefix' => substr($this->rawApiKey, 0, 8),
            'api_version' => 1,
            'name' => 'Polish Key',
        ]);

        Cache::forget('api_version:1');
        Cache::forget('api_version:latest');
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->rawApiKey}"];
    }

    public function test_version_root_returns_metadata_with_headers(): void
    {
        $response = $this->getJson('http://links.t-api.de/v1/');

        $response->assertStatus(200);
        $response->assertJsonStructure(['version', 'status', 'latest_version', 'endpoints', 'docs']);
        $response->assertJson(['version' => 1, 'status' => 'active']);
        $response->assertHeader('API-Version', '1');
        $response->assertHeader('API-Latest-Version');
    }

    public function test_version_root_404s_on_non_api_host(): void
    {
        $this->getJson('http://href.nz/v1/')->assertStatus(404);
    }

    public function test_landing_redirect_target_resolves(): void
    {
        // links.t-api.de/ redirects to /v1/ — that target must not 404.
        $this->get('http://links.t-api.de/')->assertRedirect('/v1/');
        $this->getJson('http://links.t-api.de/v1/')->assertStatus(200);
    }

    public function test_unauthenticated_error_shape_uses_message(): void
    {
        $response = $this->getJson('http://links.t-api.de/v1/links');

        $response->assertStatus(401);
        $response->assertJsonStructure(['message']);
        $response->assertJsonMissing(['error']);
    }

    public function test_forbidden_error_shape_uses_message(): void
    {
        $other = User::factory()->create();
        $link = Link::create([
            'slug' => 'notyours123',
            'destination_url' => 'https://example.com',
            'domain_id' => $this->domain->id,
            'user_id' => $other->id,
            'is_active' => true,
        ]);

        $response = $this->getJson(
            "http://links.t-api.de/v1/links/{$link->id}",
            $this->authHeaders()
        );

        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'You do not own this link.']);
    }

    public function test_authenticated_responses_carry_version_headers(): void
    {
        $response = $this->getJson('http://links.t-api.de/v1/links', $this->authHeaders());

        $response->assertStatus(200);
        $response->assertHeader('API-Version', '1');
        $response->assertHeader('API-Latest-Version');
    }

    public function test_deprecated_version_adds_sunset_headers(): void
    {
        ApiVersion::where('version', 1)->update([
            'status' => ApiVersionStatus::Deprecated,
            'deprecated_at' => now()->toDateString(),
        ]);
        Cache::forget('api_version:1');
        Cache::forget('api_version:latest');

        $response = $this->getJson('http://links.t-api.de/v1/', $this->authHeaders());

        $response->assertStatus(200);
        $response->assertHeader('Deprecation', 'true');
        $response->assertHeader('Sunset');
        $response->assertJson(['status' => 'deprecated']);
    }

    public function test_retired_version_returns_410_before_auth(): void
    {
        ApiVersion::create([
            'version' => 2,
            'status' => ApiVersionStatus::Active,
        ]);
        ApiVersion::where('version', 1)->update(['status' => ApiVersionStatus::Retired]);
        Cache::forget('api_version:1');
        Cache::forget('api_version:latest');

        // No auth header — version gate runs before auth.
        $response = $this->getJson('http://links.t-api.de/v1/links');

        $response->assertStatus(410);
        $response->assertJsonStructure(['message', 'version', 'latest_version']);
        $response->assertHeader('API-Version', '1');
    }

    public function test_plan_rate_limit_429_carries_retry_after(): void
    {
        $plan = Plan::create([
            'name' => 'test-polish-rate',
            'min_slug_length' => 8,
            'custom_subdomain' => false,
            'rate_limit_per_minute' => 2,
            'max_links_per_day' => null,
        ]);
        $user = User::factory()->create(['plan_id' => $plan->id]);
        $raw = 'tl_'.Str::random(48);
        ApiKey::create([
            'user_id' => $user->id,
            'key_hash' => hash('sha256', $raw),
            'key_prefix' => substr($raw, 0, 8),
            'api_version' => 1,
            'name' => 'Rate Key',
        ]);
        $headers = ['Authorization' => "Bearer {$raw}"];

        $payload = fn () => [
            'destination_url' => 'https://example.com/'.Str::random(8),
            'domain_id' => $this->domain->id,
        ];

        $this->postJson('http://links.t-api.de/v1/links', $payload(), $headers)->assertStatus(201);
        $this->postJson('http://links.t-api.de/v1/links', $payload(), $headers)->assertStatus(201);

        $response = $this->postJson('http://links.t-api.de/v1/links', $payload(), $headers);

        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    }
}
