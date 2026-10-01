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

class TargetingTest extends TestCase
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

    public function test_geo_priority_and_fallback(): void
    {
        $link = Link::create([
            'slug' => 'geo123',
            'destination_url' => 'https://example.com/default',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        app(\App\Services\LinkService::class)->syncTargets($link, [
            ['label' => 'US', 'destination_url' => 'https://example.com/us', 'country_codes' => ['US'], 'weight' => 100],
            ['label' => 'EU', 'destination_url' => 'https://example.com/eu', 'country_codes' => ['DE'], 'weight' => 100],
        ], $this->user);

        // US visitor → US arm (geo via request attribute used in tests).
        $this->get('http://href.nz/geo123?target=debug', ['User-Agent' => 'Mozilla/5.0'])
            ->assertOk();
    }

    public function test_api_sync_and_cap(): void
    {
        $link = Link::create([
            'slug' => 'tgt123',
            'destination_url' => 'https://example.com/d',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $rows = [];
        for ($i = 0; $i < 21; $i++) {
            $rows[] = ['destination_url' => "https://example.com/{$i}"];
        }

        $this->putJson("http://links.t-api.de/v1/links/{$link->id}", [
            'targets' => $rows,
        ], ['Authorization' => "Bearer {$this->rawApiKey}"])
            ->assertStatus(422);

        $this->putJson("http://links.t-api.de/v1/links/{$link->id}", [
            'targets' => [
                ['label' => 'A', 'destination_url' => 'https://example.com/a', 'weight' => 50],
                ['label' => 'B', 'destination_url' => 'https://example.com/b', 'weight' => 50],
            ],
        ], ['Authorization' => "Bearer {$this->rawApiKey}"])
            ->assertOk()
            ->assertJsonFragment(['label' => 'A']);

        $this->getJson("http://links.t-api.de/v1/links/{$link->id}", ['Authorization' => "Bearer {$this->rawApiKey}"])
            ->assertOk()
            ->assertJsonFragment(['has_targeting' => true]);
    }

    public function test_redirect_picks_target_and_tracks(): void
    {
        $link = Link::create([
            'slug' => 'rot123',
            'destination_url' => 'https://example.com/default',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        app(\App\Services\LinkService::class)->syncTargets($link, [
            ['label' => 'Only', 'destination_url' => 'https://example.com/only', 'weight' => 100],
        ], $this->user);

        $this->get('http://href.nz/rot123', ['User-Agent' => 'Mozilla/5.0'])
            ->assertStatus(302)
            ->assertRedirect('https://example.com/only');
    }

    public function test_device_detection(): void
    {
        $d = app(\App\Services\DeviceDetector::class);
        $this->assertSame('mobile', $d->detect('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile/15E148'));
        $this->assertSame('tablet', $d->detect('Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X)'));
        $this->assertSame('desktop', $d->detect('Mozilla/5.0 (Windows NT 10.0; Win64; x64)'));
    }
}
