<?php

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Livewire\Bio\VisualBuilder;
use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\Plan;
use App\Models\User;
use App\Services\BioService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class BioVisualTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Domain $domain;

    private string $rawApiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create([
            'plan_id' => Plan::where('name', 'business')->firstOrFail()->id,
        ]);
        $this->domain = Domain::create([
            'hostname' => 'bio.example.com',
            'user_id' => $this->user->id,
            'verification_token' => Str::random(32),
            'type' => DomainType::Partner,
            'is_active' => true,
            'verified_at' => now(),
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

    public function test_visual_route_renders_builder_with_preview(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, [
            'title' => 'Root', 'locale' => 'de', 'theme_color' => '#123456',
        ]);

        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/bio/{$page->id}/build")
            ->assertOk()
            ->assertSee('Live preview', escape: false)
            ->assertSee('Pages', escape: false);
    }

    public function test_visual_route_redirects_guests(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        $this->get("http://dash.ternis.link/bio/{$page->id}/build")
            ->assertRedirect('http://dash.ternis.link/login');
    }

    public function test_visual_route_404s_for_strangers(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Mine']);
        $stranger = User::factory()->create(['plan_id' => $this->user->plan_id]);

        $this->actingAs($stranger)
            ->get("http://dash.ternis.link/bio/{$page->id}/build")
            ->assertNotFound();
    }

    public function test_reorder_persists_new_sequence(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'One', 'kind' => 'link', 'destination_url' => 'https://example.com/1'],
            ['label' => 'Two', 'kind' => 'link', 'destination_url' => 'https://example.com/2'],
            ['label' => 'Three', 'kind' => 'link', 'destination_url' => 'https://example.com/3'],
        ], $this->user);

        $ids = $page->fresh()->buttons()->orderBy('sort_order')->pluck('id')->all();

        Livewire::actingAs($this->user)
            ->test(VisualBuilder::class, ['page' => $page])
            ->call('reorder', [$ids[2], $ids[0], $ids[1]])
            ->assertHasNoErrors();

        $this->assertSame(
            [$ids[2], $ids[0], $ids[1]],
            $page->fresh()->buttons()->orderBy('sort_order')->pluck('id')->all()
        );
    }

    public function test_reorder_rejects_unknown_ids(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'One', 'kind' => 'link', 'destination_url' => 'https://example.com/1'],
        ], $this->user);

        Livewire::actingAs($this->user)
            ->test(VisualBuilder::class, ['page' => $page])
            ->call('reorder', ['01JXXXXXXXXXXXXXXXXXXXXXXXXX'])
            ->assertHasErrors('buttons');
    }

    public function test_visual_builder_switches_sub_pages(): void
    {
        $bio = app(BioService::class);
        $root = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $sub = $bio->createPage($this->user, $this->domain, ['slug' => 'socials', 'title' => 'Socials'], $root);

        Livewire::actingAs($this->user)
            ->test(VisualBuilder::class, ['page' => $root])
            ->call('edit', $sub->id)
            ->assertSee('Socials', escape: false);
    }

    public function test_locale_and_theme_color_render(): void
    {
        app(BioService::class)->createPage($this->user, $this->domain, [
            'title' => 'Hallo', 'locale' => 'de', 'theme_color' => '#123456',
        ]);

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('<html lang="de">', escape: false)
            ->assertSee('<meta name="theme-color" content="#123456">', escape: false);
    }

    public function test_sub_page_has_home_button(): void
    {
        $bio = app(BioService::class);
        $root = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->createPage($this->user, $this->domain, ['slug' => 'socials', 'title' => 'Socials'], $root);

        $this->get('http://bio.example.com/socials')
            ->assertOk()
            ->assertSee('href="/"', escape: false)
            ->assertSee('Root', escape: false);

        // Root pages get no home button.
        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertDontSee('href="/"', escape: false);
    }

    public function test_api_updates_locale_and_theme_color(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        $this->putJson("http://links.t-api.de/v1/bio-pages/{$page->id}", [
            'locale' => 'fr',
            'theme_color' => '#abcdef',
        ], ['Authorization' => "Bearer {$this->rawApiKey}"])
            ->assertOk()
            ->assertJsonFragment(['locale' => 'fr', 'theme_color' => '#abcdef']);
    }
}
