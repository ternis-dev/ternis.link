<?php

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Models\ApiKey;
use App\Models\BioPage;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Plan;
use App\Models\User;
use App\Services\BioService;
use App\Services\LinkService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BioPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Domain $domain;

    private string $rawApiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $business = Plan::where('name', 'business')->firstOrFail();
        $this->user = User::factory()->create(['plan_id' => $business->id]);

        $this->domain = Domain::create([
            'hostname' => 'bio.example.com',
            'user_id' => $this->user->id,
            'type' => DomainType::Partner,
            'is_active' => true,
            'verified_at' => now(),
            'verification_token' => Str::random(32),
        ]);

        $this->rawApiKey = 'tl_'.Str::random(48);
        ApiKey::create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $this->rawApiKey),
            'key_prefix' => substr($this->rawApiKey, 0, 8),
            'api_version' => 1,
            'name' => 'Test Key',
        ]);
    }

    private function headers(): array
    {
        return ['Authorization' => "Bearer {$this->rawApiKey}"];
    }

    public function test_system_domain_rejected(): void
    {
        $sys = Domain::where('hostname', 'href.nz')->firstOrFail();

        $this->postJson('http://links.t-api.de/v1/bio-pages', [
            'domain_id' => $sys->id,
            'title' => 'Nope',
        ], $this->headers())->assertStatus(422);
    }

    public function test_create_root_renders_at_domain_root(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/bio-pages', [
            'domain_id' => $this->domain->id,
            'title' => 'My links',
            'bio' => 'All my things',
        ], $this->headers());

        $response->assertCreated();
        $pageId = $response->json('id');

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('My links', escape: false)
            ->assertSee('All my things', escape: false);

        $page = BioPage::findOrFail($pageId);
        $this->assertSame(1, $page->fresh()->view_count);
    }

    public function test_crawler_gets_no_view_count(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Bots']);

        $this->get('http://bio.example.com/', ['User-Agent' => 'Slackbot-LinkExpanding 1.0'])->assertOk();
        $this->assertSame(0, $page->fresh()->view_count);
    }

    public function test_sub_page_and_slug_collision(): void
    {
        $bio = app(BioService::class);
        $root = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);

        Link::create([
            'slug' => 'shop',
            'destination_url' => 'https://example.com/shop',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        // Bio sub can't steal a link slug.
        $this->postJson('http://links.t-api.de/v1/bio-pages', [
            'domain_id' => $this->domain->id,
            'parent_id' => $root->id,
            'slug' => 'shop',
            'title' => 'Shop',
        ], $this->headers())->assertStatus(422);

        $sub = $bio->createPage($this->user, $this->domain, ['slug' => 'socials', 'title' => 'Socials'], $root);

        $this->get('http://bio.example.com/socials')->assertOk()->assertSee('Socials', escape: false);

        // Link can't steal a bio sub slug either.
        $this->expectException(ValidationException::class);
        app(LinkService::class)->create('https://example.com/x', $this->domain, $this->user, 'socials');
    }

    public function test_button_tap_tracks_and_redirects(): void
    {
        $bio = app(BioService::class);
        $root = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($root, [
            ['label' => 'Shop', 'kind' => 'link', 'destination_url' => 'https://example.com/shop'],
            ['label' => '—', 'kind' => 'divider'],
        ], $this->user);

        $button = $root->fresh()->buttons()->where('kind', 'link')->firstOrFail();

        $this->get('http://bio.example.com/t/'.$button->id)
            ->assertRedirect('https://example.com/shop');

        $this->assertSame(1, $button->fresh()->tap_count);

        $stats = $this->getJson("http://links.t-api.de/v1/bio-pages/{$root->id}/stats", $this->headers());
        $stats->assertOk();
        $stats->assertJsonFragment(['label' => 'Shop', 'taps' => 1]);
    }

    public function test_stats_ctr_and_export(): void
    {
        $bio = app(BioService::class);
        $root = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($root, [
            ['label' => 'A', 'kind' => 'link', 'destination_url' => 'https://example.com/a'],
        ], $this->user);

        $this->get('http://bio.example.com/');
        $button = $root->fresh()->buttons()->firstOrFail();
        $this->get('http://bio.example.com/t/'.$button->id);

        $stats = $this->getJson("http://links.t-api.de/v1/bio-pages/{$root->id}/stats?days=7", $this->headers());
        $stats->assertOk()->assertJsonFragment(['views' => 1, 'taps' => 1, 'ctr' => 100.0]);

        $this->get("http://links.t-api.de/v1/bio-pages/{$root->id}/events/export", $this->headers())
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
