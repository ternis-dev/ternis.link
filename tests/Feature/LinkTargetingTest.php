<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\LinkTargeting;
use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkTarget;
use App\Models\User;
use App\Services\LinkService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LinkTargetingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Domain $domain;

    private string $rawApiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
        $this->domain = Domain::where('hostname', 'clicked.at')->firstOrFail();

        $this->rawApiKey = 'tl_'.Str::random(48);
        ApiKey::create([
            'user_id' => $this->user->id,
            'key_hash' => hash('sha256', $this->rawApiKey),
            'key_prefix' => substr($this->rawApiKey, 0, 8),
            'api_version' => 1,
            'name' => 'Test Key',
        ]);
    }

    private function link(): Link
    {
        return Link::create([
            'slug' => 'target01',
            'destination_url' => 'https://example.com/fallback',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
    }

    public function test_edit_page_shows_targeting_section(): void
    {
        $link = $this->link();

        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$link->id}/edit")
            ->assertOk()
            ->assertSee('Targeting rules', escape: false);
    }

    public function test_add_rule_via_dashboard(): void
    {
        $link = $this->link();

        Livewire::actingAs($this->user)
            ->test(LinkTargeting::class, ['link' => $link])
            ->set('label', 'US mobile')
            ->set('destination_url', 'https://example.com/us')
            ->set('country_codes', 'us, de')
            ->set('device', 'mobile')
            ->set('weight', 50)
            ->call('addTarget')
            ->assertHasNoErrors();

        $target = $link->fresh()->targets()->firstOrFail();
        $this->assertSame(['US', 'DE'], $target->country_codes);
        $this->assertSame('mobile', $target->device);
        $this->assertSame(50, $target->weight);
    }

    public function test_add_rule_rejects_junk_and_bad_country(): void
    {
        $link = $this->link();

        Livewire::actingAs($this->user)
            ->test(LinkTargeting::class, ['link' => $link])
            ->set('destination_url', 'https://phpinfo.php')
            ->call('addTarget')
            ->assertHasErrors('destination_url');

        $this->assertSame(0, $link->fresh()->targets()->count());
    }

    public function test_edit_toggle_remove_rule(): void
    {
        $link = $this->link();
        app(LinkService::class)->syncTargets($link, [
            ['label' => 'A', 'destination_url' => 'https://example.com/a', 'weight' => 100],
        ], $this->user);
        $id = $link->fresh()->targets()->firstOrFail()->id;

        $component = Livewire::actingAs($this->user)
            ->test(LinkTargeting::class, ['link' => $link])
            ->call('startEdit', $id)
            ->set('weight', 25)
            ->call('updateTarget')
            ->assertHasNoErrors();

        $this->assertSame(25, LinkTarget::find($id)->weight);

        $component->call('toggleTarget', $id)->assertHasNoErrors();
        $this->assertFalse((bool) LinkTarget::find($id)->is_active);

        $component->call('removeTarget', $id)->assertHasNoErrors();
        $this->assertSame(0, $link->fresh()->targets()->count());
    }

    public function test_edits_preserve_ids_and_click_counts(): void
    {
        $link = $this->link();
        app(LinkService::class)->syncTargets($link, [
            ['label' => 'A', 'destination_url' => 'https://example.com/a'],
        ], $this->user);
        $target = $link->fresh()->targets()->firstOrFail();
        $target->increment('click_count');

        Livewire::actingAs($this->user)
            ->test(LinkTargeting::class, ['link' => $link])
            ->call('startEdit', $target->id)
            ->set('weight', 10)
            ->call('updateTarget')
            ->assertHasNoErrors();

        $fresh = LinkTarget::find($target->id);
        $this->assertNotNull($fresh);
        $this->assertSame(1, $fresh->click_count);
        $this->assertSame(10, $fresh->weight);
    }

    public function test_stranger_cannot_touch_foreign_rules(): void
    {
        $link = $this->link();
        $stranger = User::factory()->create();

        // The dashboard route itself 404s for non-owners (no leak),
        // so the component never boots on foreign links.
        $this->actingAs($stranger)
            ->get("http://dash.ternis.link/links/{$link->id}/edit")
            ->assertNotFound();

        $this->assertSame(0, $link->fresh()->targets()->count());
    }

    public function test_api_rejects_unknown_target_ids(): void
    {
        $link = $this->link();

        $this->putJson("http://links.t-api.de/v1/links/{$link->id}", [
            'targets' => [
                ['id' => '01JXXXXXXXXXXXXXXXXXXXXXXXXX', 'destination_url' => 'https://example.com/x'],
            ],
        ], ['Authorization' => "Bearer {$this->rawApiKey}"])
            ->assertStatus(422);
    }

    public function test_api_update_preserves_target_identity(): void
    {
        $link = $this->link();
        app(LinkService::class)->syncTargets($link, [
            ['label' => 'A', 'destination_url' => 'https://example.com/a'],
        ], $this->user);
        $id = $link->fresh()->targets()->firstOrFail()->id;

        $this->putJson("http://links.t-api.de/v1/links/{$link->id}", [
            'targets' => [
                ['id' => $id, 'label' => 'A2', 'destination_url' => 'https://example.com/a'],
            ],
        ], ['Authorization' => "Bearer {$this->rawApiKey}"])
            ->assertOk();

        $this->assertSame('A2', LinkTarget::find($id)->label);
    }
}
