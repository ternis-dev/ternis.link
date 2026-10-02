<?php

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Enums\UserRole;
use App\Livewire\Admin\BioModeration;
use App\Livewire\Bio\PageBuilder;
use App\Livewire\Bio\VisualBuilder;
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

class BioRoundFourTest extends TestCase
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

    private function admin(): User
    {
        return User::factory()->create([
            'plan_id' => $this->user->plan_id,
            'role' => UserRole::Admin,
        ]);
    }

    public function test_rsvp_records_once_per_visitor(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Party']);
        $bio->syncButtons($page, [
            ['label' => 'Summer fest', 'sublabel' => 'Aug 1, park', 'kind' => 'rsvp'],
        ], $this->user);

        $button = $page->fresh()->buttons()->firstOrFail();

        $html = $this->get('http://bio.example.com/')->getContent();
        $this->assertStringContainsString('0 going', $html);

        $this->get("http://bio.example.com/t/{$button->id}/rsvp")
            ->assertRedirect('https://bio.example.com/?rsvpd=1');

        // Second visit from the same IP is not double-counted.
        $this->get("http://bio.example.com/t/{$button->id}/rsvp")
            ->assertRedirect('https://bio.example.com/?rsvpd=1');

        $this->assertSame(1, $button->fresh()->rsvp_count);
        $this->get('http://bio.example.com/')
            ->assertSee('1 going', escape: false)
            ->assertSee("You're in", escape: false);
    }

    public function test_rsvp_rejects_locked_and_dead_pages(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Party']);
        $bio->syncButtons($page, [
            ['label' => 'VIP', 'kind' => 'rsvp'],
        ], $this->user);
        $button = $page->fresh()->buttons()->firstOrFail();

        $bio->setPassword($page, 'correct-horse');
        $this->get("http://bio.example.com/t/{$button->id}/rsvp")->assertNotFound();

        $page->update(['password_hash' => null, 'is_active' => false]);
        $this->get("http://bio.example.com/t/{$button->id}/rsvp")->assertNotFound();

        $this->assertSame(0, $button->fresh()->rsvp_count);
    }

    public function test_builder_edits_button_in_place(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Old', 'kind' => 'link', 'destination_url' => 'https://example.com/old'],
        ], $this->user);
        $button = $page->fresh()->buttons()->firstOrFail();

        Livewire::actingAs($this->user)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id)
            ->call('startEditButton', $button->id)
            ->set('editLabel', 'New')
            ->set('editUrl', 'https://example.com/new')
            ->set('editBadge', 'HOT')
            ->call('updateButton')
            ->assertHasNoErrors();

        $fresh = $button->fresh();
        $this->assertSame('New', $fresh->label);
        $this->assertSame('https://example.com/new', $fresh->destination_url);
        $this->assertSame('HOT', $fresh->badge);
        $this->assertSame($button->id, $fresh->id);
    }

    public function test_builder_edit_rejects_bad_url_and_unknown_button(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Shop', 'kind' => 'link', 'destination_url' => 'https://example.com/shop'],
        ], $this->user);
        $button = $page->fresh()->buttons()->firstOrFail();

        Livewire::actingAs($this->user)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id)
            ->call('startEditButton', '01JXXXXXXXXXXXXXXXXXXXXXXXXX')
            ->call('updateButton')
            ->assertHasNoErrors();

        $this->assertSame('Shop', $button->fresh()->label);

        Livewire::actingAs($this->user)
            ->test(VisualBuilder::class, ['page' => $page])
            ->call('startEditButton', $button->id)
            ->set('editUrl', 'not-a-url')
            ->call('updateButton')
            ->assertHasErrors('editUrl');

        $this->assertSame('https://example.com/shop', $button->fresh()->destination_url);
    }

    public function test_admin_moderates_bio_pages(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Spammy']);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('http://admin.ternis.link/bio')
            ->assertOk()
            ->assertSee('Spammy', escape: false);

        Livewire::actingAs($admin)
            ->test(BioModeration::class)
            ->call('deactivate', $page->id)
            ->assertHasNoErrors();

        $this->assertFalse($page->fresh()->is_active);
        $this->get('http://bio.example.com/')->assertOk()->assertDontSee('Spammy', escape: false);

        Livewire::actingAs($admin)
            ->test(BioModeration::class)
            ->call('remove', $page->id)
            ->assertHasNoErrors();

        $this->assertTrue($page->fresh()->is_removed);

        Livewire::actingAs($admin)
            ->test(BioModeration::class)
            ->call('restore', $page->id)
            ->call('reactivate', $page->id)
            ->assertHasNoErrors();

        $this->assertFalse($page->fresh()->is_removed);
        $this->assertTrue((bool) $page->fresh()->is_active);
    }

    public function test_non_admin_cannot_moderate(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        $this->actingAs($this->user)
            ->get('http://admin.ternis.link/bio')
            ->assertForbidden();
    }

    public function test_expired_cleanup_command(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, [
            'title' => 'Old',
            'expires_at' => now()->addHour()->toDateTimeString(),
        ]);

        $this->artisan('bio:deactivate-expired')->assertSuccessful();
        $this->assertTrue((bool) $page->fresh()->is_active);

        $page->update(['expires_at' => now()->subMinute()]);

        $this->artisan('bio:deactivate-expired')->assertSuccessful();

        $this->assertFalse($page->fresh()->is_active);
        $this->get('http://bio.example.com/')->assertOk()->assertDontSee('Old', escape: false);
    }
}
