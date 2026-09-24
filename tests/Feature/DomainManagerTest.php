<?php

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Livewire\Dashboard\DomainManager;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Plan;
use App\Models\User;
use App\Services\DomainVerificationService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DomainManagerTest extends TestCase
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

    private function ownDomain(User $user, string $hostname = 'links.example.com', bool $verified = false): Domain
    {
        return Domain::create([
            'hostname' => $hostname,
            'user_id' => $user->id,
            'verification_token' => Str::random(32),
            'type' => DomainType::Partner,
            'is_active' => true,
            'verified_at' => $verified ? now() : null,
        ]);
    }

    public function test_domains_page_renders_for_authenticated_user(): void
    {
        $user = $this->userOnPlan('family');

        $this->actingAs($user)
            ->get('http://dash.ternis.link/domains')
            ->assertStatus(200)
            ->assertSee('Domains')
            ->assertSee('Add Custom Domain');
    }

    public function test_guest_cannot_access_domains_page(): void
    {
        $this->get('http://dash.ternis.link/domains')
            ->assertRedirect('http://dash.ternis.link/login');
    }

    public function test_free_plan_sees_upgrade_notice_instead_of_form(): void
    {
        $user = $this->userOnPlan('free');

        Livewire::actingAs($user)
            ->test(DomainManager::class)
            ->assertSee('not included in your plan')
            ->assertDontSee('Add Domain');
    }

    public function test_eligible_plan_registers_domain_and_shows_instructions(): void
    {
        $user = $this->userOnPlan('family');

        Livewire::actingAs($user)
            ->test(DomainManager::class)
            ->set('hostname', 'Links.Example.COM ')
            ->call('addDomain')
            ->assertHasNoErrors()
            ->assertSee('_ternis-verify.links.example.com');

        $this->assertDatabaseHas('domains', [
            'hostname' => 'links.example.com',
            'user_id' => $user->id,
            'verified_at' => null,
        ]);
    }

    public function test_invalid_hostname_is_rejected(): void
    {
        $user = $this->userOnPlan('family');

        Livewire::actingAs($user)
            ->test(DomainManager::class)
            ->set('hostname', 'not a domain')
            ->call('addDomain')
            ->assertHasErrors('hostname');

        $this->assertDatabaseMissing('domains', ['hostname' => 'not a domain']);
    }

    public function test_reserved_hostname_is_rejected(): void
    {
        $user = $this->userOnPlan('family');

        Livewire::actingAs($user)
            ->test(DomainManager::class)
            ->set('hostname', 'href.nz')
            ->call('addDomain')
            ->assertHasErrors('hostname');

        $this->assertDatabaseMissing('domains', ['hostname' => 'href.nz', 'user_id' => $user->id]);
    }

    public function test_duplicate_hostname_is_rejected(): void
    {
        $user = $this->userOnPlan('family');
        $this->ownDomain($user);

        Livewire::actingAs($user)
            ->test(DomainManager::class)
            ->set('hostname', 'links.example.com')
            ->call('addDomain')
            ->assertHasErrors('hostname');
    }

    public function test_free_plan_cannot_register_domain(): void
    {
        $user = $this->userOnPlan('free');

        Livewire::actingAs($user)
            ->test(DomainManager::class)
            ->set('hostname', 'links.example.com')
            ->call('addDomain')
            ->assertHasErrors('hostname');

        $this->assertDatabaseMissing('domains', ['hostname' => 'links.example.com']);
    }

    public function test_verify_succeeds_with_matching_txt_record(): void
    {
        $user = $this->userOnPlan('family');
        $domain = $this->ownDomain($user);

        app()->singleton(DomainVerificationService::class, fn () => new DomainVerificationService(
            fn () => [$domain->verification_token]
        ));

        Livewire::actingAs($user)
            ->test(DomainManager::class)
            ->call('verifyDomain', $domain->id)
            ->assertHasNoErrors();

        $this->assertNotNull($domain->fresh()->verified_at);
    }

    public function test_verify_fails_without_dns_record(): void
    {
        $user = $this->userOnPlan('family');
        $domain = $this->ownDomain($user, 'nonexistent-invalid-domain-12345.com');

        Livewire::actingAs($user)
            ->test(DomainManager::class)
            ->call('verifyDomain', $domain->id)
            ->assertHasErrors("verify.{$domain->id}");

        $this->assertNull($domain->fresh()->verified_at);
    }

    public function test_remove_deactivates_own_domain_and_preserves_links(): void
    {
        $user = $this->userOnPlan('family');
        $domain = $this->ownDomain($user, verified: true);

        $link = Link::create([
            'slug' => 'keepme12345',
            'destination_url' => 'https://example.com',
            'domain_id' => $domain->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(DomainManager::class)
            ->call('removeDomain', $domain->id)
            ->assertHasNoErrors();

        $this->assertFalse($domain->fresh()->is_active);
        $this->assertDatabaseHas('links', ['id' => $link->id, 'slug' => 'keepme12345']);
    }

    public function test_cannot_verify_or_remove_foreign_or_system_domain(): void
    {
        $owner = $this->userOnPlan('family');
        $stranger = $this->userOnPlan('family');
        $domain = $this->ownDomain($owner);
        $system = Domain::where('hostname', 'href.nz')->firstOrFail();

        // Tampered IDs are a no-op with an error — nothing is touched.
        Livewire::actingAs($stranger)
            ->test(DomainManager::class)
            ->call('removeDomain', $domain->id)
            ->assertHasErrors('hostname');

        Livewire::actingAs($stranger)
            ->test(DomainManager::class)
            ->call('removeDomain', $system->id)
            ->assertHasErrors('hostname');

        Livewire::actingAs($stranger)
            ->test(DomainManager::class)
            ->call('verifyDomain', $domain->id)
            ->assertHasErrors("verify.{$domain->id}");

        $this->assertTrue($domain->fresh()->is_active);
        $this->assertTrue($system->fresh()->is_active);
        $this->assertNull($domain->fresh()->verified_at);
    }

    public function test_lists_only_own_and_system_domains(): void
    {
        $user = $this->userOnPlan('family');
        $other = $this->userOnPlan('family');
        $mine = $this->ownDomain($user, 'mine.example.com', verified: true);
        $theirs = $this->ownDomain($other, 'theirs.example.com', verified: true);

        Livewire::actingAs($user)
            ->test(DomainManager::class)
            ->assertSee('href.nz')
            ->assertSee($mine->hostname)
            ->assertDontSee($theirs->hostname);
    }
}
