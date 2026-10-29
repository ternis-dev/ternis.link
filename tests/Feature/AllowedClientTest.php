<?php

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Enums\UserRole;
use App\Livewire\Admin\AllowedClientModeration;
use App\Livewire\Admin\ErrorEncounterTable;
use App\Models\AllowedClient;
use App\Models\Domain;
use App\Models\ErrorEncounter;
use App\Models\Link;
use App\Models\User;
use App\Services\LinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class AllowedClientTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    private Domain $publicDomain;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        RateLimiter::clear('public-links');
        RateLimiter::clear('api');

        $this->admin = User::factory()->create([
            'email' => 'admin@ternis.link',
            'role' => UserRole::Admin,
        ]);

        $this->user = User::factory()->create([
            'email' => 'user@ternis.link',
            'role' => UserRole::User,
        ]);

        $this->publicDomain = Domain::create([
            'hostname' => 'href.nz',
            'type' => DomainType::Public,
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_access_allowed_clients_page(): void
    {
        $this->actingAs($this->user)
            ->get('http://admin.ternis.link/allowed-clients')
            ->assertForbidden();
    }

    public function test_admin_can_access_allowed_clients_page(): void
    {
        $this->actingAs($this->admin)
            ->get('http://admin.ternis.link/allowed-clients')
            ->assertOk()
            ->assertSee('Allowed Pairs & Rate Limits');
    }

    public function test_admin_can_create_allowed_pair(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AllowedClientModeration::class)
            ->call('openCreateModal')
            ->set('name', 'Twins on Ice Fallback')
            ->set('ip_address', '198.51.100.22')
            ->set('user_agent', 'twins-on-ice-fallback/1.0')
            ->set('notes', 'Migration fallback worker')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('allowed_clients', [
            'name' => 'Twins on Ice Fallback',
            'ip_address' => '198.51.100.22',
            'user_agent' => 'twins-on-ice-fallback/1.0',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_ip_only_rule(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AllowedClientModeration::class)
            ->call('openCreateModal')
            ->set('name', 'Internal Gateway IP')
            ->set('ip_address', '203.0.113.0/24')
            ->set('user_agent', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('allowed_clients', [
            'name' => 'Internal Gateway IP',
            'ip_address' => '203.0.113.0/24',
            'user_agent' => null,
        ]);
    }

    public function test_validation_fails_if_both_ip_and_user_agent_are_empty(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AllowedClientModeration::class)
            ->call('openCreateModal')
            ->set('name', 'Empty rule')
            ->set('ip_address', '')
            ->set('user_agent', '')
            ->call('save')
            ->assertHasErrors(['ip_address', 'user_agent']);
    }

    public function test_admin_can_toggle_and_delete_rule(): void
    {
        $client = AllowedClient::create([
            'name' => 'Test Rule',
            'ip_address' => '1.2.3.4',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(AllowedClientModeration::class)
            ->call('toggleActive', $client->id);

        $this->assertFalse($client->fresh()->is_active);

        Livewire::actingAs($this->admin)
            ->test(AllowedClientModeration::class)
            ->call('delete', $client->id);

        $this->assertDatabaseMissing('allowed_clients', ['id' => $client->id]);
    }

    public function test_allowed_client_matching_logic(): void
    {
        // 1. Pair rule: both must match
        $pair = AllowedClient::create([
            'ip_address' => '198.51.100.5',
            'user_agent' => 'twins-on-ice-fallback/1.0',
            'is_active' => true,
        ]);

        $this->assertTrue($pair->matchesPair('198.51.100.5', 'twins-on-ice-fallback/1.0'));
        $this->assertFalse($pair->matchesPair('198.51.100.6', 'twins-on-ice-fallback/1.0')); // IP mismatch
        $this->assertFalse($pair->matchesPair('198.51.100.5', 'other-agent/2.0')); // UA mismatch

        // 2. IP only rule (with CIDR)
        $ipOnly = AllowedClient::create([
            'ip_address' => '10.0.0.0/16',
            'is_active' => true,
        ]);

        $this->assertTrue($ipOnly->matchesPair('10.0.1.20', 'any-agent'));
        $this->assertFalse($ipOnly->matchesPair('11.0.0.1', 'any-agent'));

        // 3. User-Agent only rule (with wildcard pattern)
        $uaOnly = AllowedClient::create([
            'user_agent' => 'twins-on-ice*',
            'is_active' => true,
        ]);

        $this->assertTrue($uaOnly->matchesPair('203.0.113.99', 'twins-on-ice-fallback/1.0'));
        $this->assertTrue($uaOnly->matchesPair('203.0.113.99', 'twins-on-ice/2.0'));
        $this->assertFalse($uaOnly->matchesPair('203.0.113.99', 'curl/7.68.0'));
    }

    public function test_public_api_bypasses_throttle_for_allowed_pair(): void
    {
        AllowedClient::create([
            'name' => 'Twins on Ice Fallback',
            'ip_address' => '198.51.100.77',
            'user_agent' => 'twins-on-ice-fallback/1.0',
            'is_active' => true,
        ]);

        // Non-allowed client gets throttled on 11th request
        for ($i = 0; $i < 10; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.99'])
                ->postJson('http://links.t-api.de/v1/links/public', [
                    'destination_url' => 'https://example.com/regular-'.$i,
                ], [
                    'User-Agent' => 'RegularBrowser/1.0',
                ])
                ->assertStatus(201);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.99'])
            ->postJson('http://links.t-api.de/v1/links/public', [
                'destination_url' => 'https://example.com/regular-11',
            ], [
                'User-Agent' => 'RegularBrowser/1.0',
            ])
            ->assertStatus(429);

        // Allowed client (same pair) can make > 10 requests without 429
        for ($i = 0; $i < 15; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.77'])
                ->postJson('http://links.t-api.de/v1/links/public', [
                    'destination_url' => 'https://example.com/allowed-'.$i,
                ], [
                    'User-Agent' => 'twins-on-ice-fallback/1.0',
                ])
                ->assertStatus(201);
        }
    }

    public function test_public_api_bypasses_throttle_for_ip_only_rule(): void
    {
        AllowedClient::create([
            'name' => 'Internal Worker IP',
            'ip_address' => '198.51.100.55',
            'is_active' => true,
        ]);

        for ($i = 0; $i < 15; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.55'])
                ->postJson('http://links.t-api.de/v1/links/public', [
                    'destination_url' => 'https://example.com/ip-allowed-'.$i,
                ], [
                    'User-Agent' => 'AnyCustomBot/'.$i,
                ])
                ->assertStatus(201);
        }
    }

    public function test_public_api_bypasses_throttle_for_user_agent_only_rule(): void
    {
        AllowedClient::create([
            'name' => 'Wildcard UA Rule',
            'user_agent' => 'twins-on-ice*',
            'is_active' => true,
        ]);

        for ($i = 0; $i < 15; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.66'])
                ->postJson('http://links.t-api.de/v1/links/public', [
                    'destination_url' => 'https://example.com/ua-allowed-'.$i,
                ], [
                    'User-Agent' => 'twins-on-ice-fallback/1.0',
                ])
                ->assertStatus(201);
        }
    }

    public function test_allowed_client_bypasses_guest_daily_quota(): void
    {
        AllowedClient::create([
            'name' => 'Twins Fallback',
            'ip_address' => '198.51.100.88',
            'user_agent' => 'twins-on-ice-fallback/1.0',
            'is_active' => true,
        ]);

        $service = app(LinkService::class);

        // Seed 50 links for this IP
        $ipHash = hash('sha256', '198.51.100.88');
        for ($i = 0; $i < LinkService::ANONYMOUS_DAILY_LIMIT; $i++) {
            Link::create([
                'slug' => 'seed-'.$i,
                'destination_url' => 'https://example.com/seed-'.$i,
                'domain_id' => $this->publicDomain->id,
                'creator_ip_hash' => $ipHash,
                'is_active' => true,
            ]);
        }

        // 51st creation by the allowed client succeeds through the service & API
        $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.88'])
            ->postJson('http://links.t-api.de/v1/links/public', [
                'destination_url' => 'https://example.com/seed-51',
            ], [
                'User-Agent' => 'twins-on-ice-fallback/1.0',
            ]);

        $response->assertStatus(201);
    }

    public function test_admin_can_allow_client_from_error_encounter_table(): void
    {
        $encounter = ErrorEncounter::create([
            'http_code' => 429,
            'error_message' => 'Too Many Attempts.',
            'exception_class' => 'Illuminate\\Http\\Exceptions\\ThrottleRequestsException',
            'method' => 'POST',
            'host' => 'links.t-api.de',
            'path' => '/v1/links/public',
            'user_agent' => 'twins-on-ice-fallback/1.0',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ErrorEncounterTable::class)
            ->call('allowClient', $encounter->id)
            ->assertDispatched('notify');

        $this->assertDatabaseHas('allowed_clients', [
            'user_agent' => 'twins-on-ice-fallback/1.0',
            'is_active' => true,
        ]);
    }
}
