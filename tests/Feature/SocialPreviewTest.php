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

class SocialPreviewTest extends TestCase
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

    public function test_create_with_og_fields(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/links', [
            'destination_url' => 'https://example.com/launch',
            'domain_id' => $this->domain->id,
            'og_title' => 'Launch day',
            'og_description' => 'Our new thing',
            'og_image_url' => 'https://example.com/og.png',
        ], ['Authorization' => "Bearer {$this->rawApiKey}"]);

        $response->assertCreated();
        $response->assertJsonFragment(['og_title' => 'Launch day']);
        $this->assertDatabaseHas('links', ['og_title' => 'Launch day']);
    }

    public function test_update_og_fields_and_clear(): void
    {
        $link = Link::create([
            'slug' => 'ogupd123',
            'destination_url' => 'https://example.com/a',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $this->putJson("http://links.t-api.de/v1/links/{$link->id}", [
            'og_title' => 'Hello',
        ], ['Authorization' => "Bearer {$this->rawApiKey}"])
            ->assertOk()
            ->assertJsonFragment(['og_title' => 'Hello']);

        $this->putJson("http://links.t-api.de/v1/links/{$link->id}", [
            'og_title' => null,
        ], ['Authorization' => "Bearer {$this->rawApiKey}"])
            ->assertOk();
        $this->assertNull($link->fresh()->og_title);
    }

    public function test_guest_og_prohibited(): void
    {
        $this->postJson('http://links.t-api.de/v1/links/public', [
            'destination_url' => 'https://example.com/x',
            'og_title' => 'Nope',
        ])->assertStatus(422)->assertJsonValidationErrors(['og_title']);
    }

    public function test_crawler_gets_stub_human_gets_redirect(): void
    {
        $link = Link::create([
            'slug' => 'og1234',
            'destination_url' => 'https://example.com/target',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'og_title' => 'Hello',
            'og_description' => 'World',
            'og_image_url' => 'https://example.com/og.png',
            'is_active' => true,
        ]);

        $this->get('http://href.nz/og1234', ['User-Agent' => 'Slackbot-LinkExpanding 1.0'])
            ->assertStatus(200)
            ->assertSee('<meta property="og:title" content="Hello">', escape: false)
            ->assertSee('og:image', escape: false);

        $this->assertSame(0, $link->fresh()->click_count);

        $this->get('http://href.nz/og1234', ['User-Agent' => 'Mozilla/5.0'])
            ->assertStatus(302)
            ->assertRedirect('https://example.com/target');
    }

    public function test_debug_param_forces_stub(): void
    {
        Link::create([
            'slug' => 'ogdbg1',
            'destination_url' => 'https://example.com/t',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'og_title' => 'Debug',
            'is_active' => true,
        ]);

        $this->get('http://href.nz/ogdbg1?debug=og')
            ->assertStatus(200)
            ->assertSee('og:title', escape: false);
    }

    public function test_link_without_og_never_stubs(): void
    {
        Link::create([
            'slug' => 'plain12',
            'destination_url' => 'https://example.com/p',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $this->get('http://href.nz/plain12', ['User-Agent' => 'Twitterbot/1.0'])
            ->assertStatus(302);
    }

    public function test_intranet_image_rejected(): void
    {
        $service = app(\App\Services\LinkService::class);

        $this->expectException(\App\Exceptions\UnsafeUrlException::class);
        $service->create('https://example.com/a', $this->domain, $this->user, null, null, null, null, null, null, null, null, 'T', null, 'http://192.168.1.1/og.png');
    }
}
