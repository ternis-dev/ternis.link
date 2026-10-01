<?php

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Livewire\Bio\PageBuilder;
use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\Plan;
use App\Models\User;
use App\Services\BioService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class BioActionsTest extends TestCase
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

    private function headers(): array
    {
        return ['Authorization' => "Bearer {$this->rawApiKey}"];
    }

    public function test_subpage_button_redirects_and_tracks(): void
    {
        $bio = app(BioService::class);
        $root = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $sub = $bio->createPage($this->user, $this->domain, ['slug' => 'socials', 'title' => 'Socials'], $root);
        $bio->syncButtons($root, [
            ['label' => 'Socials', 'kind' => 'link', 'action' => 'subpage', 'target_page_id' => $sub->id],
        ], $this->user);

        $button = $root->fresh()->buttons()->firstOrFail();

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('Socials', escape: false);

        $this->get("http://bio.example.com/t/{$button->id}")
            ->assertRedirect('https://bio.example.com/socials');

        $this->assertSame(1, $button->fresh()->tap_count);
    }

    public function test_modal_button_renders_dialog_and_tracks_opens(): void
    {
        $bio = app(BioService::class);
        $root = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($root, [
            ['label' => 'About', 'kind' => 'link', 'action' => 'modal', 'modal_title' => 'Hi there', 'modal_body' => 'Some details'],
        ], $this->user);

        $button = $root->fresh()->buttons()->firstOrFail();

        $this->get('http://bio.example.com/')
            ->assertOk()
            ->assertSee('<dialog', escape: false)
            ->assertSee('Hi there', escape: false);

        // Crafted GET on a modal tap lands back on the page, untracked.
        $this->get("http://bio.example.com/t/{$button->id}")
            ->assertRedirect('https://bio.example.com');
        $this->assertSame(0, $button->fresh()->tap_count);

        // Client-side open beacon tracks + returns a gif.
        $pixel = $this->get("http://bio.example.com/t/{$button->id}/open.gif");
        $pixel->assertOk()->assertHeader('Content-Type', 'image/gif');
        $this->assertSame(1, $button->fresh()->tap_count);
    }

    public function test_api_rejects_foreign_modal_and_target(): void
    {
        $bio = app(BioService::class);
        $root = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);

        // Modal without title.
        $this->putJson("http://links.t-api.de/v1/bio-pages/{$root->id}/buttons", [
            'buttons' => [['label' => 'X', 'kind' => 'link', 'action' => 'modal']],
        ], $this->headers())->assertStatus(422);

        // Target outside the family.
        $other = User::factory()->create(['plan_id' => $this->user->plan_id]);
        $foreignDomain = Domain::create([
            'hostname' => 'other.example.com', 'user_id' => $other->id,
            'verification_token' => Str::random(32), 'type' => DomainType::Partner,
            'is_active' => true, 'verified_at' => now(),
        ]);
        $foreign = $bio->createPage($other, $foreignDomain, ['title' => 'Foreign']);

        $this->putJson("http://links.t-api.de/v1/bio-pages/{$root->id}/buttons", [
            'buttons' => [['label' => 'X', 'kind' => 'link', 'action' => 'subpage', 'target_page_id' => $foreign->id]],
        ], $this->headers())->assertStatus(422);

        $this->assertSame(0, $root->fresh()->buttons()->count());
    }

    public function test_signed_draft_renders_unpublished_without_tracking(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, [
            'title' => 'Secret launch',
            'published_at' => now()->addDay()->toDateTimeString(),
        ]);

        // No signature → 403, page stays hidden publicly.
        $this->get("http://bio.example.com/draft/{$page->id}")->assertForbidden();
        $this->get('http://bio.example.com/')->assertDontSee('Secret launch', escape: false);

        $url = URL::temporarySignedRoute('bio.draft', now()->addMinutes(30), ['page' => $page->id]);
        $path = parse_url($url, PHP_URL_PATH).'?'.parse_url($url, PHP_URL_QUERY);

        $this->get('http://bio.example.com'.$path)
            ->assertOk()
            ->assertSee('Secret launch', escape: false)
            ->assertSee('Draft preview', escape: false)
            ->assertSee('noindex', escape: false);

        $this->assertSame(0, $page->fresh()->view_count);
    }

    public function test_builder_adds_action_buttons_and_mints_draft_link(): void
    {
        $bio = app(BioService::class);
        $root = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $sub = $bio->createPage($this->user, $this->domain, ['slug' => 'socials', 'title' => 'Socials'], $root);

        $component = Livewire::actingAs($this->user)
            ->test(PageBuilder::class)
            ->call('selectPage', $root->id)
            ->set('newLabel', 'Socials')
            ->set('newKind', 'link')
            ->set('newAction', 'subpage')
            ->set('newTargetPage', $sub->id)
            ->call('addButton')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('bio_buttons', [
            'bio_page_id' => $root->id,
            'action' => 'subpage',
            'target_page_id' => $sub->id,
        ]);

        $component->call('makeDraftLink')->assertHasNoErrors();

        $url = $component->get('draftUrl');
        $this->assertStringContainsString('/draft/'.$root->id, (string) $url);
        $this->assertStringContainsString('signature=', (string) $url);

        // Builder shows the live phone preview.
        $component->assertSee('Live preview', escape: false);
    }

    public function test_stale_tap_targets_404(): void
    {
        $bio = app(BioService::class);
        $root = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $sub = $bio->createPage($this->user, $this->domain, ['slug' => 'gone', 'title' => 'Gone'], $root);
        $bio->syncButtons($root, [
            ['label' => 'Gone', 'kind' => 'link', 'action' => 'subpage', 'target_page_id' => $sub->id],
        ], $this->user);

        $button = $root->fresh()->buttons()->firstOrFail();
        $sub->update(['is_active' => false]);

        $this->get("http://bio.example.com/t/{$button->id}")->assertNotFound();
    }
}
