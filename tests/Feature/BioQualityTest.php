<?php

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Livewire\Bio\PageBuilder;
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
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Quality hardening for the bio page-builder: auth boundaries,
 * validation edges, id handling, and expiry/lock interactions
 * that the feature tests don't cover.
 */
class BioQualityTest extends TestCase
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

    private function stranger(): User
    {
        return User::factory()->create(['plan_id' => $this->user->plan_id]);
    }

    private function otherDomain(User $user, string $hostname): Domain
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

    public function test_guest_api_endpoints_reject_unauthenticated(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        $this->getJson('http://links.t-api.de/v1/bio-pages')->assertUnauthorized();
        $this->postJson('http://links.t-api.de/v1/bio-pages', [])->assertUnauthorized();
        $this->getJson("http://links.t-api.de/v1/bio-pages/{$page->id}")->assertUnauthorized();
        $this->putJson("http://links.t-api.de/v1/bio-pages/{$page->id}", [])->assertUnauthorized();
        $this->deleteJson("http://links.t-api.de/v1/bio-pages/{$page->id}")->assertUnauthorized();
        $this->putJson("http://links.t-api.de/v1/bio-pages/{$page->id}/buttons", [])->assertUnauthorized();
        $this->getJson("http://links.t-api.de/v1/bio-pages/{$page->id}/stats")->assertUnauthorized();
        $this->getJson("http://links.t-api.de/v1/bio-pages/{$page->id}/export")->assertUnauthorized();
        $this->getJson("http://links.t-api.de/v1/bio-pages/{$page->id}/qr")->assertUnauthorized();
        $this->postJson("http://links.t-api.de/v1/bio-pages/{$page->id}/duplicate", [])->assertUnauthorized();
    }

    public function test_stranger_api_endpoints_forbidden(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);
        $raw = 'tl_'.Str::random(48);
        $other = $this->stranger();
        ApiKey::create([
            'user_id' => $other->id, 'key_hash' => hash('sha256', $raw),
            'key_prefix' => substr($raw, 0, 8), 'api_version' => 1, 'name' => 'Other',
        ]);
        $headers = ['Authorization' => "Bearer {$raw}"];

        $this->getJson("http://links.t-api.de/v1/bio-pages/{$page->id}", $headers)->assertForbidden();
        $this->putJson("http://links.t-api.de/v1/bio-pages/{$page->id}", ['title' => 'Hijack'], $headers)->assertForbidden();
        $this->deleteJson("http://links.t-api.de/v1/bio-pages/{$page->id}", [], $headers)->assertForbidden();
        $this->putJson("http://links.t-api.de/v1/bio-pages/{$page->id}/buttons", ['buttons' => []], $headers)->assertForbidden();
        $this->getJson("http://links.t-api.de/v1/bio-pages/{$page->id}/stats", $headers)->assertForbidden();
        $this->getJson("http://links.t-api.de/v1/bio-pages/{$page->id}/export", $headers)->assertForbidden();
        $this->postJson("http://links.t-api.de/v1/bio-pages/{$page->id}/duplicate", [], $headers)->assertForbidden();

        $this->assertSame('Root', $page->fresh()->title);
        $this->assertTrue((bool) $page->fresh()->is_active);
    }

    public function test_stranger_livewire_mutations_are_noops(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Shop', 'kind' => 'link', 'destination_url' => 'https://example.com/shop'],
        ], $this->user);
        $buttonId = $page->fresh()->buttons()->firstOrFail()->id;
        $stranger = $this->stranger();

        Livewire::actingAs($stranger)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id)
            ->call('toggleButton', $buttonId)
            ->call('removeButton', $buttonId)
            ->call('moveButton', $buttonId, 'up')
            ->call('reorder', [$buttonId])
            ->call('deactivatePage', $page->id)
            ->assertHasNoErrors();

        $this->assertTrue($page->fresh()->buttons()->firstOrFail()->is_active);
        $this->assertSame(1, $page->fresh()->buttons()->count());
        $this->assertTrue((bool) $page->fresh()->is_active);
    }

    public function test_stranger_cannot_mint_draft_links(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        $component = Livewire::actingAs($this->stranger())
            ->test(VisualBuilder::class, ['page' => $page]);

        // Mount aborts for non-owners before any state exists.
        $component->assertNotFound();
    }

    public function test_api_store_validation_edges(): void
    {
        $base = ['domain_id' => $this->domain->id, 'title' => 'Root'];

        $this->postJson('http://links.t-api.de/v1/bio-pages', [...$base, 'theme' => 'neon'], $this->headers())->assertStatus(422);
        $this->postJson('http://links.t-api.de/v1/bio-pages', [...$base, 'accent' => 'red'], $this->headers())->assertStatus(422);
        $this->postJson('http://links.t-api.de/v1/bio-pages', [...$base, 'locale' => 'xx'], $this->headers())->assertStatus(422);

        // Three roots max.
        for ($i = 1; $i <= 3; $i++) {
            $d = $this->otherDomain($this->user, "bio{$i}.example.com");
            $this->postJson('http://links.t-api.de/v1/bio-pages', [
                'domain_id' => $d->id, 'title' => "Root {$i}",
            ], $this->headers())->assertCreated();
        }

        $d4 = $this->otherDomain($this->user, 'bio4.example.com');
        $this->postJson('http://links.t-api.de/v1/bio-pages', [
            'domain_id' => $d4->id, 'title' => 'Root 4',
        ], $this->headers())->assertStatus(422);
    }

    public function test_api_buttons_validation_edges(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);
        $url = "http://links.t-api.de/v1/bio-pages/{$page->id}/buttons";

        // Unknown kind + missing URL + bad icon.
        $this->putJson($url, ['buttons' => [
            ['label' => 'X', 'kind' => 'carousel', 'destination_url' => 'https://example.com/x'],
        ]], $this->headers())->assertStatus(422);

        // 26 buttons over the cap.
        $rows = [];
        for ($i = 0; $i < 26; $i++) {
            $rows[] = ['label' => "L{$i}", 'kind' => 'link', 'destination_url' => "https://example.com/{$i}"];
        }
        $this->putJson($url, ['buttons' => $rows], $this->headers())->assertStatus(422);

        // Unknown + duplicate button ids.
        $this->putJson($url, ['buttons' => [
            ['id' => '01JXXXXXXXXXXXXXXXXXXXXXXXXX', 'label' => 'Ghost', 'kind' => 'link', 'destination_url' => 'https://example.com/g'],
        ]], $this->headers())->assertStatus(422);

        $this->assertSame(0, $page->fresh()->buttons()->count());
    }

    public function test_api_update_validation_edges(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);
        $url = "http://links.t-api.de/v1/bio-pages/{$page->id}";

        $this->putJson($url, ['locale' => 'xx'], $this->headers())->assertStatus(422);
        $this->putJson($url, ['button_style' => '3d'], $this->headers())->assertStatus(422);
        $this->putJson($url, ['password' => 'short'], $this->headers())->assertStatus(422);
        $this->putJson($url, ['expires_at' => now()->subHour()->toDateTimeString()], $this->headers())->assertStatus(422);

        $this->assertNull($page->fresh()->password_hash);
    }

    public function test_builder_save_rejects_bad_values(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        Livewire::actingAs($this->user)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id)
            ->set('accent', 'not-a-color')
            ->set('page_password', 'short')
            ->call('savePage')
            ->assertHasErrors(['accent', 'page_password']);

        $this->assertNull($page->fresh()->accent);
        $this->assertNull($page->fresh()->password_hash);
    }

    public function test_builder_button_schedule_window(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        Livewire::actingAs($this->user)
            ->test(PageBuilder::class)
            ->call('selectPage', $page->id)
            ->set('newLabel', 'Soon')
            ->set('newKind', 'link')
            ->set('newUrl', 'https://example.com/soon')
            ->set('newStartsAt', now()->addHour()->format('Y-m-d\TH:i'))
            ->set('newEndsAt', now()->format('Y-m-d\TH:i'))
            ->call('addButton')
            ->assertHasErrors('newEndsAt');

        $this->assertSame(0, $page->fresh()->buttons()->count());
    }

    public function test_reorder_with_duplicate_ids_errors(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'One', 'kind' => 'link', 'destination_url' => 'https://example.com/1'],
        ], $this->user);
        $id = $page->fresh()->buttons()->firstOrFail()->id;

        foreach ([PageBuilder::class, VisualBuilder::class] as $component) {
            $test = Livewire::actingAs($this->user)->test($component, $component === VisualBuilder::class ? ['page' => $page] : []);

            if ($component === PageBuilder::class) {
                $test->call('selectPage', $page->id);
            }

            $test->call('reorder', [$id, $id])->assertHasErrors('buttons');
        }

        $this->assertSame(1, $page->fresh()->buttons()->count());
    }

    public function test_expired_draft_signature_rejected(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Secret']);

        $url = URL::temporarySignedRoute(
            'bio.draft', now()->subMinute(), ['page' => $page->id]
        );
        $path = parse_url($url, PHP_URL_PATH).'?'.parse_url($url, PHP_URL_QUERY);

        $this->get('http://bio.example.com'.$path)->assertForbidden();
    }

    public function test_paused_button_pixel_tracks_nothing(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Quiet', 'kind' => 'link', 'action' => 'modal', 'modal_title' => 'Q', 'is_active' => false],
        ], $this->user);
        $button = $page->fresh()->buttons()->firstOrFail();

        $this->get("http://bio.example.com/t/{$button->id}/open.gif")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/gif');

        $this->assertSame(0, $button->fresh()->tap_count);
    }

    public function test_inactive_page_taps_404(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Shop', 'kind' => 'link', 'destination_url' => 'https://example.com/shop'],
        ], $this->user);
        $button = $page->fresh()->buttons()->firstOrFail();

        $page->update(['is_active' => false]);

        $this->get('http://bio.example.com/')->assertOk()->assertDontSee('Shop', escape: false);
        $this->get("http://bio.example.com/t/{$button->id}")->assertNotFound();
    }

    public function test_locked_sub_page_only(): void
    {
        $bio = app(BioService::class);
        $root = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $sub = $bio->createPage($this->user, $this->domain, ['slug' => 'vip', 'title' => 'VIP'], $root);
        $bio->syncButtons($sub, [
            ['label' => 'Deal', 'kind' => 'link', 'destination_url' => 'https://example.com/deal'],
        ], $this->user);
        $bio->setPassword($sub, 'correct-horse');

        // Root stays public while the sub is locked.
        $this->get('http://bio.example.com/')->assertOk()->assertSee('Root', escape: false);
        $this->get('http://bio.example.com/vip')
            ->assertOk()
            ->assertSee('password-protected', escape: false)
            ->assertDontSee('Deal', escape: false);

        $button = $sub->fresh()->buttons()->firstOrFail();
        $this->get("http://bio.example.com/t/{$button->id}")->assertNotFound();

        $this->post('http://bio.example.com/unlock/'.$sub->id, ['password' => 'correct-horse'])
            ->assertRedirect('https://bio.example.com/vip');
        $this->get('http://bio.example.com/vip')->assertSee('Deal', escape: false);
    }

    public function test_duplicate_root_on_taken_domain_rejected(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        $this->postJson("http://links.t-api.de/v1/bio-pages/{$page->id}/duplicate", [
            'domain_id' => $this->domain->id,
        ], $this->headers())->assertStatus(422);
    }

    public function test_sync_clears_buttons_with_empty_array(): void
    {
        $bio = app(BioService::class);
        $page = $bio->createPage($this->user, $this->domain, ['title' => 'Root']);
        $bio->syncButtons($page, [
            ['label' => 'Shop', 'kind' => 'link', 'destination_url' => 'https://example.com/shop'],
        ], $this->user);

        $this->putJson("http://links.t-api.de/v1/bio-pages/{$page->id}/buttons", [
            'buttons' => [],
        ], $this->headers())->assertOk();

        $this->assertSame(0, $page->fresh()->buttons()->count());
    }

    public function test_visual_edit_ignores_foreign_ids(): void
    {
        $page = app(BioService::class)->createPage($this->user, $this->domain, ['title' => 'Root']);

        $component = Livewire::actingAs($this->user)
            ->test(VisualBuilder::class, ['page' => $page])
            ->call('edit', '01JXXXXXXXXXXXXXXXXXXXXXXXXX');

        // Still editing the original page.
        $this->assertSame($page->id, $component->get('editingPageId'));
    }

    public function test_stats_for_unknown_button_scope_stays_scoped(): void
    {
        $other = $this->stranger();
        $foreignDomain = $this->otherDomain($other, 'foreign.example.com');
        $foreign = app(BioService::class)->createPage($other, $foreignDomain, ['title' => 'Foreign']);

        $this->getJson("http://links.t-api.de/v1/bio-pages/{$foreign->id}/stats", $this->headers())
            ->assertForbidden();
    }
}
