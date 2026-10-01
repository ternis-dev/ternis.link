<?php

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Livewire\Bio\PageAnalytics;
use App\Livewire\Bio\PageBuilder;
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
}
