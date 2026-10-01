<?php

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Livewire\Bio\PageAnalytics;
use App\Livewire\Bio\PageBuilder;
use App\Models\BioButton;
use App\Models\Domain;
use App\Models\Link;
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

class BioBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }

    private function userOnPlan(string $planName): User
    {
        return User::factory()->create([
            'plan_id' => Plan::where('name', $planName)->firstOrFail()->id,
        ]);
    }

    private function ownDomain(User $user, string $hostname = 'bio.example.com'): Domain
    {
        return Domain::create([
            'hostname' => $hostname,
            'user_id' => $user->id,
            'verification_token' => Str::random(32),
            'type' => DomainType::Partner,
            'is_active' => true,
            'verified_at' => now(),
        ]);
    }

    public function test_bio_index_renders_for_authenticated_user(): void
    {
        $user = $this->userOnPlan('business');

        $this->actingAs($user)
            ->get('http://dash.ternis.link/bio')
            ->assertOk()
            ->assertSee('Bio Pages', escape: false);
    }

    public function test_guest_cannot_access_bio_pages(): void
    {
        $this->get('http://dash.ternis.link/bio')
            ->assertRedirect('http://dash.ternis.link/login');
    }

    public function test_builder_creates_root_page(): void
    {
        $user = $this->userOnPlan('business');
        $domain = $this->ownDomain($user);

        Livewire::actingAs($user)
            ->test(PageBuilder::class)
            ->set('title', 'My links')
            ->set('bio', 'All my things')
            ->call('createRoot')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('bio_pages', [
            'domain_id' => $domain->id,
            'title' => 'My links',
            'parent_id' => null,
        ]);
    }

    public function test_builder_rejects_unverified_domain(): void
    {
        $user = $this->userOnPlan('business');
        $domain = Domain::create([
            'hostname' => 'pending.example.com',
            'user_id' => $user->id,
            'verification_token' => Str::random(32),
            'type' => DomainType::Partner,
            'is_active' => true,
            'verified_at' => null,
        ]);

        Livewire::actingAs($user)
            ->test(PageBuilder::class)
            ->set('domain_id', $domain->id)
            ->set('title', 'Nope')
            ->call('createRoot')
            ->assertHasErrors('domain_id');

        $this->assertDatabaseMissing('bio_pages', ['domain_id' => $domain->id]);
    }

    public function test_builder_adds_and_removes_buttons(): void
    {
        $user = $this->userOnPlan('business');
        $domain = $this->ownDomain($user);

        $page = app(BioService::class)->createPage($user, $domain, ['title' => 'Root']);

        $builder = Livewire::actingAs($user)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id)
            ->set('newLabel', 'Shop')
            ->set('newUrl', 'https://example.com/shop')
            ->call('addButton')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('bio_buttons', [
            'bio_page_id' => $page->id,
            'label' => 'Shop',
        ]);

        $buttonId = $page->fresh()->buttons()->firstOrFail()->id;

        $builder->call('removeButton', $buttonId)->assertHasNoErrors();

        $this->assertDatabaseMissing('bio_buttons', ['id' => $buttonId]);
    }

    public function test_builder_rejects_junk_button_url(): void
    {
        $user = $this->userOnPlan('business');
        $domain = $this->ownDomain($user);

        $page = app(BioService::class)->createPage($user, $domain, ['title' => 'Root']);

        Livewire::actingAs($user)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id)
            ->set('newLabel', 'Probe')
            ->set('newUrl', 'https://phpinfo.php')
            ->call('addButton')
            ->assertHasErrors('newUrl');

        $this->assertSame(0, $page->fresh()->buttons()->count());
    }

    public function test_stranger_cannot_open_or_edit_foreign_page(): void
    {
        $owner = $this->userOnPlan('business');
        $stranger = $this->userOnPlan('business');
        $domain = $this->ownDomain($owner);

        $page = app(BioService::class)->createPage($owner, $domain, ['title' => 'Mine']);

        // Dashboard detail 404s for non-owners (no existence leak).
        $this->actingAs($stranger)
            ->get("http://dash.ternis.link/bio/{$page->id}")
            ->assertNotFound();

        // Builder silently ignores foreign ids.
        $component = Livewire::actingAs($stranger)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id);

        $this->assertNull($component->get('editingPageId'));
    }

    public function test_show_page_renders_analytics(): void
    {
        $user = $this->userOnPlan('business');
        $domain = $this->ownDomain($user);

        $page = app(BioService::class)->createPage($user, $domain, ['title' => 'Root']);

        $this->actingAs($user)
            ->get("http://dash.ternis.link/bio/{$page->id}")
            ->assertOk()
            ->assertSee('Root', escape: false)
            ->assertSee('Taps per button', escape: false);

        Livewire::actingAs($user)
            ->test(PageAnalytics::class, ['page' => $page])
            ->assertSee('Views', escape: false)
            ->assertSee('CTR', escape: false);
    }

    public function test_stats_page_links_into_builder_preselected(): void
    {
        $user = $this->userOnPlan('business');
        $domain = $this->ownDomain($user);

        $page = app(BioService::class)->createPage($user, $domain, ['title' => 'Root']);

        $this->actingAs($user)
            ->get("http://dash.ternis.link/bio/{$page->id}")
            ->assertOk()
            ->assertSee('Open in page-builder', escape: false)
            ->assertSee(route('dashboard.bio', ['edit' => $page->id]), escape: false);

        // Deep-link pre-selects the page in the builder.
        $this->actingAs($user)
            ->get("http://dash.ternis.link/bio?edit={$page->id}")
            ->assertOk()
            ->assertSee('Editing: Root', escape: false);
    }

    public function test_builder_explains_missing_domain(): void
    {
        $user = $this->userOnPlan('business');

        Livewire::actingAs($user)
            ->test(PageBuilder::class)
            ->assertSee('verified custom domain', escape: false)
            ->assertSee(route('dashboard.domains'), escape: false);
    }

    public function test_domains_page_links_to_builder(): void
    {
        $user = $this->userOnPlan('business');

        $this->actingAs($user)
            ->get('http://dash.ternis.link/domains')
            ->assertOk()
            ->assertSee('Open the page-builder', escape: false);
    }

    public function test_deactivate_keeps_analytics(): void
    {
        $user = $this->userOnPlan('business');
        $domain = $this->ownDomain($user);

        $page = app(BioService::class)->createPage($user, $domain, ['title' => 'Root']);

        Livewire::actingAs($user)
            ->test(PageBuilder::class)
            ->call('deactivatePage', $page->id)
            ->assertHasNoErrors();

        $this->assertFalse($page->fresh()->is_active);
        $this->assertDatabaseHas('bio_pages', ['id' => $page->id]);
    }

    public function test_builder_creates_sub_page(): void
    {
        $user = $this->userOnPlan('business');
        $domain = $this->ownDomain($user);

        $root = app(BioService::class)->createPage($user, $domain, ['title' => 'Root']);

        Livewire::actingAs($user)
            ->test(PageBuilder::class)
            ->call('selectPage', $root->id)
            ->set('slug', 'socials')
            ->set('subTitle', 'Socials')
            ->call('createSub')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('bio_pages', [
            'parent_id' => $root->id,
            'slug' => 'socials',
            'title' => 'Socials',
        ]);

        $this->get('http://bio.example.com/socials')
            ->assertOk()
            ->assertSee('Socials', escape: false);
    }

    public function test_builder_rejects_colliding_sub_slug(): void
    {
        $user = $this->userOnPlan('business');
        $domain = $this->ownDomain($user);

        $root = app(BioService::class)->createPage($user, $domain, ['title' => 'Root']);

        Link::create([
            'slug' => 'shop',
            'destination_url' => 'https://example.com/shop',
            'domain_id' => $domain->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(PageBuilder::class)
            ->call('selectPage', $root->id)
            ->set('slug', 'shop')
            ->set('subTitle', 'Shop')
            ->call('createSub')
            ->assertHasErrors('slug');
    }

    public function test_toggle_pauses_button_and_hides_it_publicly(): void
    {
        $user = $this->userOnPlan('business');
        $domain = $this->ownDomain($user);

        $bio = app(BioService::class);
        $page = $bio->createPage($user, $domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Shop', 'kind' => 'link', 'destination_url' => 'https://example.com/shop'],
        ], $user);

        $buttonId = $page->fresh()->buttons()->firstOrFail()->id;

        Livewire::actingAs($user)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id)
            ->call('toggleButton', $buttonId)
            ->assertHasNoErrors();

        $this->assertFalse(BioButton::find($buttonId)->is_active);

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertDontSee('Shop', escape: false);

        $this->get("http://bio.example.com/t/{$buttonId}")->assertNotFound();
    }

    public function test_move_button_reorders(): void
    {
        $user = $this->userOnPlan('business');
        $domain = $this->ownDomain($user);

        $bio = app(BioService::class);
        $page = $bio->createPage($user, $domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'First', 'kind' => 'link', 'destination_url' => 'https://example.com/1'],
            ['label' => 'Second', 'kind' => 'link', 'destination_url' => 'https://example.com/2'],
        ], $user);

        $ids = $page->fresh()->buttons()->orderBy('sort_order')->pluck('id')->all();

        Livewire::actingAs($user)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id)
            ->call('moveButton', $ids[1], 'up')
            ->assertHasNoErrors();

        $this->assertSame(
            [$ids[1], $ids[0]],
            $page->fresh()->buttons()->orderBy('sort_order')->pluck('id')->all()
        );
    }

    public function test_button_edits_preserve_ids_and_tap_counts(): void
    {
        $user = $this->userOnPlan('business');
        $domain = $this->ownDomain($user);

        $bio = app(BioService::class);
        $page = $bio->createPage($user, $domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Shop', 'kind' => 'link', 'destination_url' => 'https://example.com/shop'],
        ], $user);

        $button = $page->fresh()->buttons()->firstOrFail();

        // A real tap, then a pause/resume round-trip through the builder.
        $this->get("http://bio.example.com/t/{$button->id}")->assertRedirect();

        Livewire::actingAs($user)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id)
            ->call('toggleButton', $button->id)
            ->call('toggleButton', $button->id)
            ->assertHasNoErrors();

        $this->assertSame(1, $button->fresh()->tap_count);
        $this->assertSame(1, $page->fresh()->buttons()->count());
        $this->assertSame(
            $button->id,
            $page->fresh()->buttons()->firstOrFail()->id
        );
    }

    public function test_adding_button_preserves_schedules(): void
    {
        $user = $this->userOnPlan('business');
        $domain = $this->ownDomain($user);

        $bio = app(BioService::class);
        $page = $bio->createPage($user, $domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Timed', 'kind' => 'link', 'destination_url' => 'https://example.com/t', 'starts_at' => now()->addDay()->toDateTimeString()],
        ], $user);

        Livewire::actingAs($user)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id)
            ->set('newLabel', 'Fresh')
            ->set('newUrl', 'https://example.com/f')
            ->call('addButton')
            ->assertHasNoErrors();

        $timed = $page->fresh()->buttons()->where('label', 'Timed')->firstOrFail();
        $this->assertNotNull($timed->starts_at);

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertDontSee('Timed', escape: false)
            ->assertSee('Fresh', escape: false);

        $this->get("http://bio.example.com/t/{$timed->id}")->assertNotFound();
    }

    public function test_future_published_at_hides_page(): void
    {
        $user = $this->userOnPlan('business');
        $domain = $this->ownDomain($user);

        $page = app(BioService::class)->createPage($user, $domain, [
            'title' => 'Soon',
            'published_at' => now()->addDay()->toDateTimeString(),
        ]);

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertDontSee('Soon', escape: false);

        $page->update(['published_at' => now()->subHour()]);

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('Soon', escape: false);
    }
}
