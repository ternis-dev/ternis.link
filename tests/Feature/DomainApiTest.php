<?php

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Livewire\Dashboard\LinkForm;
use App\Models\ApiKey;
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

class DomainApiTest extends TestCase
{
    use RefreshDatabase;

    private Domain $systemDomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->systemDomain = Domain::where('hostname', 'href.nz')->first();
    }

    private function headersFor(User $user): array
    {
        $raw = 'tl_'.Str::random(48);
        ApiKey::create([
            'user_id' => $user->id,
            'key_hash' => hash('sha256', $raw),
            'key_prefix' => substr($raw, 0, 8),
            'api_version' => 1,
            'name' => 'Test Key',
        ]);

        return [
            'Authorization' => "Bearer {$raw}",
        ];
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

    public function test_api_requires_authentication(): void
    {
        $this->getJson('http://links.t-api.de/v1/domains')
            ->assertStatus(401);
    }

    public function test_list_shows_system_and_own_domains_only(): void
    {
        $user = $this->userOnPlan('family');
        $other = $this->userOnPlan('family');
        $mine = $this->ownDomain($user, 'links.example.com', verified: true);
        $theirs = $this->ownDomain($other, 'other.example.com', verified: true);

        $response = $this->getJson('http://links.t-api.de/v1/domains', $this->headersFor($user));

        $response->assertStatus(200);
        $response->assertJsonFragment(['hostname' => 'href.nz']);
        $response->assertJsonFragment(['hostname' => $mine->hostname]);
        $response->assertJsonMissing(['hostname' => $theirs->hostname]);
    }

    public function test_free_plan_cannot_register_custom_domain(): void
    {
        $user = $this->userOnPlan('free');

        $this->postJson('http://links.t-api.de/v1/domains', ['hostname' => 'links.example.com'], $this->headersFor($user))
            ->assertStatus(403);

        $this->assertDatabaseMissing('domains', ['hostname' => 'links.example.com']);
    }

    public function test_eligible_plan_registers_unverified_domain_with_instructions(): void
    {
        $user = $this->userOnPlan('family');

        $response = $this->postJson('http://links.t-api.de/v1/domains', ['hostname' => 'Links.Example.COM '], $this->headersFor($user));

        $response->assertStatus(201);
        $response->assertJsonPath('hostname', 'links.example.com');
        $response->assertJsonPath('type', 'partner');
        $response->assertJsonStructure(['verification' => ['type', 'host', 'value']]);
        $response->assertJsonPath('verification.host', '_ternis-verify.links.example.com');

        $this->assertDatabaseHas('domains', [
            'hostname' => 'links.example.com',
            'user_id' => $user->id,
            'verified_at' => null,
        ]);
    }

    public function test_invalid_hostnames_are_rejected(): void
    {
        $user = $this->userOnPlan('family');
        $headers = $this->headersFor($user);

        foreach (['https://example.com/x', 'not a domain', 'no-tld-here', str_repeat('a', 64).'.com'] as $bad) {
            $this->postJson('http://links.t-api.de/v1/domains', ['hostname' => $bad], $headers)
                ->assertStatus(422);
        }
    }

    public function test_reserved_hostnames_are_rejected(): void
    {
        $user = $this->userOnPlan('family');
        $headers = $this->headersFor($user);

        foreach (['href.nz', 'api.ternis.link', 'evil.dash.ternis.link', 'intranet.local'] as $reserved) {
            $this->postJson('http://links.t-api.de/v1/domains', ['hostname' => $reserved], $headers)
                ->assertStatus(422);
        }
    }

    public function test_duplicate_hostname_is_rejected(): void
    {
        $user = $this->userOnPlan('family');
        $this->ownDomain($user);

        $this->postJson('http://links.t-api.de/v1/domains', ['hostname' => 'links.example.com'], $this->headersFor($user))
            ->assertStatus(422)
            ->assertJsonValidationErrors('hostname');
    }

    public function test_show_includes_instructions_until_verified(): void
    {
        $user = $this->userOnPlan('family');
        $headers = $this->headersFor($user);
        $domain = $this->ownDomain($user);

        $this->getJson("http://links.t-api.de/v1/domains/{$domain->id}", $headers)
            ->assertStatus(200)
            ->assertJsonStructure(['verification' => ['type', 'host', 'value']]);

        $domain->markVerified();

        $this->getJson("http://links.t-api.de/v1/domains/{$domain->id}", $headers)
            ->assertStatus(200)
            ->assertJsonMissing(['verification' => []]);
    }

    public function test_non_owner_cannot_view_or_delete_domain(): void
    {
        $owner = $this->userOnPlan('family');
        $stranger = $this->userOnPlan('family');
        $domain = $this->ownDomain($owner);

        $this->getJson("http://links.t-api.de/v1/domains/{$domain->id}", $this->headersFor($stranger))
            ->assertStatus(403);
        $this->deleteJson("http://links.t-api.de/v1/domains/{$domain->id}", [], $this->headersFor($stranger))
            ->assertStatus(403);
    }

    public function test_admin_can_view_foreign_domain(): void
    {
        $owner = $this->userOnPlan('family');
        $admin = User::factory()->admin()->create([
            'plan_id' => Plan::where('name', 'business')->firstOrFail()->id,
        ]);
        $domain = $this->ownDomain($owner);

        $this->getJson("http://links.t-api.de/v1/domains/{$domain->id}", $this->headersFor($admin))
            ->assertStatus(200);
    }

    public function test_verify_fails_without_dns_record(): void
    {
        $user = $this->userOnPlan('family');
        $domain = $this->ownDomain($user, 'nonexistent-invalid-domain-12345.com');

        // Real DNS lookup finds nothing for this name.
        $this->postJson("http://links.t-api.de/v1/domains/{$domain->id}/verify", [], $this->headersFor($user))
            ->assertStatus(422)
            ->assertJsonPath('verified', false)
            ->assertJsonStructure(['verification' => ['type', 'host', 'value']]);

        $this->assertNull($domain->fresh()->verified_at);
    }

    public function test_verify_succeeds_with_matching_txt_record(): void
    {
        $user = $this->userOnPlan('family');
        $domain = $this->ownDomain($user);

        app()->singleton(DomainVerificationService::class, fn () => new DomainVerificationService(
            fn () => [$domain->verification_token]
        ));

        $this->postJson("http://links.t-api.de/v1/domains/{$domain->id}/verify", [], $this->headersFor($user))
            ->assertStatus(200)
            ->assertJsonPath('verified', true);

        $this->assertNotNull($domain->fresh()->verified_at);
    }

    public function test_link_creation_requires_verified_domain(): void
    {
        $user = $this->userOnPlan('family');
        $headers = $this->headersFor($user);
        $domain = $this->ownDomain($user);

        $payload = [
            'destination_url' => 'https://example.com/unverified',
            'domain_id' => $domain->id,
            'slug' => 'verified-slug-1',
        ];

        $this->postJson('http://links.t-api.de/v1/links', $payload, $headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors('domain_id');

        $domain->markVerified();

        $this->postJson('http://links.t-api.de/v1/links', $payload, $headers)
            ->assertStatus(201);
    }

    public function test_link_creation_rejects_inactive_domain(): void
    {
        $user = $this->userOnPlan('family');
        $domain = $this->ownDomain($user, verified: true);
        $domain->update(['is_active' => false]);

        $this->postJson('http://links.t-api.de/v1/links', [
            'destination_url' => 'https://example.com/inactive',
            'domain_id' => $domain->id,
            'slug' => 'inactive-domain-1',
        ], $this->headersFor($user))->assertStatus(422)->assertJsonValidationErrors('domain_id');
    }

    public function test_delete_deactivates_own_domain_but_not_system_domain(): void
    {
        $user = $this->userOnPlan('family');
        $headers = $this->headersFor($user);
        $domain = $this->ownDomain($user, verified: true);

        $link = Link::create([
            'slug' => 'keepme12345',
            'destination_url' => 'https://example.com',
            'domain_id' => $domain->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $this->deleteJson("http://links.t-api.de/v1/domains/{$domain->id}", [], $headers)
            ->assertStatus(204);

        $this->assertFalse($domain->fresh()->is_active);
        // Links and analytics are preserved.
        $this->assertDatabaseHas('links', ['id' => $link->id, 'slug' => 'keepme12345']);

        $this->deleteJson("http://links.t-api.de/v1/domains/{$this->systemDomain->id}", [], $headers)
            ->assertStatus(403);
    }

    public function test_livewire_form_lists_only_usable_domains(): void
    {
        $user = $this->userOnPlan('family');
        $verified = $this->ownDomain($user, 'go.example.com', verified: true);
        $unverified = $this->ownDomain($user, 'pending.example.com');

        Livewire::actingAs($user)
            ->test(LinkForm::class)
            ->assertSee($this->systemDomain->hostname)
            ->assertSee($verified->hostname)
            ->assertDontSee($unverified->hostname);
    }
}
