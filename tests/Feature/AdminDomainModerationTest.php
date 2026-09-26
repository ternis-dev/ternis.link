<?php

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Livewire\Admin\DomainModeration;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDomainModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->admin = User::factory()->admin()->create();
        $this->user = User::factory()->create([
            'plan_id' => Plan::where('name', 'family')->firstOrFail()->id,
        ]);
    }

    private function ownDomain(User $user, string $hostname = 'links.example.com', bool $verified = false, bool $active = true): Domain
    {
        return Domain::create([
            'hostname' => $hostname,
            'user_id' => $user->id,
            'verification_token' => Str::random(32),
            'type' => DomainType::Partner,
            'is_active' => $active,
            'verified_at' => $verified ? now() : null,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('http://admin.ternis.link/domains')
            ->assertRedirect('http://admin.ternis.link/login');
    }

    public function test_non_admin_gets_forbidden(): void
    {
        $this->actingAs($this->user)
            ->get('http://admin.ternis.link/domains')
            ->assertForbidden();
    }

    public function test_admin_can_view_domains_page(): void
    {
        $domain = $this->ownDomain($this->user, 'links.example.com', verified: true);

        $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/domains')
            ->assertStatus(200)
            ->assertSee('Domain Moderation')
            ->assertSee('href.nz')
            ->assertSee($domain->hostname);
    }

    public function test_lists_system_and_user_domains_with_search(): void
    {
        $mine = $this->ownDomain($this->user, 'abuse.example.com', verified: true);

        Livewire::actingAs($this->admin)
            ->test(DomainModeration::class)
            ->assertSee('href.nz')
            ->assertSee($mine->hostname)
            ->assertSee($this->user->email)
            ->set('search', 'abuse.example')
            ->assertSee($mine->hostname)
            ->assertDontSee('go.ternis.net');
    }

    public function test_status_filters(): void
    {
        $verified = $this->ownDomain($this->user, 'ok.example.com', verified: true);
        $pending = $this->ownDomain($this->user, 'pending.example.com');
        $disabled = $this->ownDomain($this->user, 'bad.example.com', verified: true, active: false);

        Livewire::actingAs($this->admin)
            ->test(DomainModeration::class)
            ->set('status', 'pending')
            ->assertSee($pending->hostname)
            ->assertDontSee($verified->hostname)
            ->assertDontSee($disabled->hostname)
            ->set('status', 'disabled')
            ->assertSee($disabled->hostname)
            ->assertDontSee($pending->hostname)
            ->set('status', 'system')
            ->assertSee('href.nz')
            ->assertDontSee($verified->hostname);
    }

    public function test_deactivate_and_reactivate_preserves_links(): void
    {
        $domain = $this->ownDomain($this->user, 'abuse.example.com', verified: true);

        $link = Link::create([
            'slug' => 'spam12345',
            'destination_url' => 'https://example.com/spam',
            'domain_id' => $domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(DomainModeration::class)
            ->call('deactivate', $domain->id)
            ->assertHasNoErrors()
            ->call('reactivate', $domain->id)
            ->assertHasNoErrors();

        $this->assertTrue($domain->fresh()->is_active);
        $this->assertDatabaseHas('links', ['id' => $link->id, 'slug' => 'spam12345']);
    }

    public function test_system_domain_is_protected(): void
    {
        $system = Domain::where('hostname', 'href.nz')->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(DomainModeration::class)
            ->assertSee('Protected')
            ->call('deactivate', $system->id)
            ->assertHasErrors('domain');

        $this->assertTrue($system->fresh()->is_active);
    }

    public function test_non_admin_cannot_moderate_via_component(): void
    {
        $domain = $this->ownDomain($this->user, 'abuse.example.com', verified: true);

        Livewire::actingAs($this->user)
            ->test(DomainModeration::class)
            ->assertForbidden();

        $this->assertTrue($domain->fresh()->is_active);
    }
}
