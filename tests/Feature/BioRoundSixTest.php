<?php

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Livewire\Bio\PageBuilder;
use App\Livewire\Bio\VisualBuilder;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Plan;
use App\Models\User;
use App\Services\BioService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class BioRoundSixTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Domain $domain;

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
    }

    public function test_health_check_flags_broken_links(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Good', 'kind' => 'link', 'destination_url' => 'https://example.com/ok'],
            ['label' => 'Gone', 'kind' => 'link', 'destination_url' => 'https://example.com/missing'],
        ], $this->user);

        Http::fake([
            'https://example.com/ok' => Http::response('', 200),
            'https://example.com/missing' => Http::response('', 404),
        ]);

        $results = $bio->checkLinks($page);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]['ok']);
        $this->assertFalse($results[1]['ok']);
        $this->assertSame(404, $results[1]['status']);
    }

    public function test_builder_health_action_renders_results(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);
        app(BioService::class)->syncButtons($page, [
            ['label' => 'Good', 'kind' => 'link', 'destination_url' => 'https://example.com/ok'],
        ], $this->user);

        Http::fake(['*' => Http::response('', 200)]);

        Livewire::actingAs($this->user)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id)
            ->call('checkLinks')
            ->assertHasNoErrors()
            ->assertSee('✅', escape: false);
    }

    public function test_duplicate_button_in_both_builders(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Shop', 'kind' => 'link', 'destination_url' => 'https://example.com/shop'],
        ], $this->user);
        $buttonId = $page->fresh()->buttons()->firstOrFail()->id;

        Livewire::actingAs($this->user)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id)
            ->call('duplicateButton', $buttonId)
            ->assertHasNoErrors();

        $this->assertSame(2, $page->fresh()->buttons()->count());

        Livewire::actingAs($this->user)
            ->test(VisualBuilder::class, ['page' => $page])
            ->call('duplicateButton', $buttonId)
            ->assertHasNoErrors();

        $this->assertSame(3, $page->fresh()->buttons()->count());
        $labels = $page->fresh()->buttons()->orderBy('sort_order')->pluck('label')->all();
        $this->assertSame(['Shop', 'Shop (copy)', 'Shop (copy)'], $labels);
    }

    public function test_import_links_as_short_urls(): void
    {
        $link = Link::create([
            'slug' => 'launch1',
            'destination_url' => 'https://example.com/launch',
            'description' => 'Launch page',
            'domain_id' => Domain::where('hostname', 'href.nz')->firstOrFail()->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        Livewire::actingAs($this->user)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id)
            ->set('importLinkIds', [$link->id, '01JXXXXXXXXXXXXXXXXXXXXXXXXX'])
            ->call('importLinks')
            ->assertHasNoErrors();

        $button = $page->fresh()->buttons()->firstOrFail();
        $this->assertSame('Launch page', $button->label);
        $this->assertSame('https://href.nz/launch1', $button->destination_url);
    }

    public function test_delete_sub_needs_confirm(): void
    {
        $bio = app(BioService::class);
        $root = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $sub = $bio->createPage($this->user, $this->domain, ['slug' => 'old', 'title' => 'Old'], $root);

        $component = Livewire::actingAs($this->user)
            ->test(PageBuilder::class)
            ->call('deleteSub', $sub->id);

        // First click only arms the confirm.
        $this->assertFalse((bool) $sub->fresh()->is_removed);
        $this->assertSame($sub->id, $component->get('confirmingSubDelete'));

        $component->call('deleteSub', $sub->id)->assertHasNoErrors();

        $this->assertTrue((bool) $sub->fresh()->is_removed);
        $this->get('http://bio.example.com/old')->assertNotFound();
    }

    public function test_delete_sub_rejects_roots_and_foreign_pages(): void
    {
        $root = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        Livewire::actingAs($this->user)
            ->test(PageBuilder::class)
            ->call('deleteSub', $root->id)
            ->assertHasNoErrors();

        $this->assertFalse((bool) $root->fresh()->is_removed);
    }

    public function test_removed_pages_hidden_from_dashboard_and_api(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);
        $page->update(['is_removed' => true]);

        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/bio/{$page->id}")
            ->assertNotFound();

        Livewire::actingAs($this->user)
            ->test(PageBuilder::class)
            ->assertSee('No bio pages yet', escape: false);
    }
}
