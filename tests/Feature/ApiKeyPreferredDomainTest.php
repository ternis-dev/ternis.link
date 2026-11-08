<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\ApiKeyManager;
use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ApiKeyPreferredDomainTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Domain $hrefDomain;

    private Domain $meinlinkDomain;

    private Domain $ternisDomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
        $this->hrefDomain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $this->meinlinkDomain = Domain::where('hostname', 'meinlink.at')->firstOrFail();
        $this->ternisDomain = Domain::where('hostname', 'ternis.link')->firstOrFail();
    }

    private function createApiKey(array $attributes = []): array
    {
        $raw = 'tl_'.Str::random(48);
        $key = ApiKey::create(array_merge([
            'user_id' => $this->user->id,
            'key_hash' => ApiKey::hashToken($raw),
            'key_prefix' => substr($raw, 0, 8),
            'api_version' => 1,
            'name' => 'Test Key',
        ], $attributes));

        return [$key, ['Authorization' => "Bearer {$raw}"]];
    }

    public function test_link_shortening_uses_preferred_domain_when_domain_id_is_omitted(): void
    {
        [$apiKey, $headers] = $this->createApiKey([
            'preferred_domain_id' => $this->meinlinkDomain->id,
        ]);

        $response = $this->postJson('http://links.t-api.de/v1/links', [
            'destination_url' => 'https://example.com/preferred-test',
        ], $headers);

        $response->assertCreated();
        $createdId = $response->json('id');
        $link = Link::findOrFail($createdId);

        $this->assertSame($this->meinlinkDomain->id, $link->domain_id);
        $this->assertSame('meinlink.at', $link->domain->hostname);
        $this->assertSame($apiKey->id, $link->api_key_id);
    }

    public function test_link_shortening_falls_back_to_href_nz_when_api_key_has_no_preferred_domain(): void
    {
        // Even if user's account default is ternis.link, API key without preferred domain falls back to href.nz
        $this->user->update(['default_domain_id' => $this->ternisDomain->id]);

        [$apiKey, $headers] = $this->createApiKey([
            'preferred_domain_id' => null,
        ]);

        $response = $this->postJson('http://links.t-api.de/v1/links', [
            'destination_url' => 'https://example.com/fallback-test',
        ], $headers);

        $response->assertCreated();
        $createdId = $response->json('id');
        $link = Link::findOrFail($createdId);

        $this->assertSame($this->hrefDomain->id, $link->domain_id);
        $this->assertSame('href.nz', $link->domain->hostname);
    }

    public function test_explicit_domain_id_overrides_api_key_preferred_domain(): void
    {
        [$apiKey, $headers] = $this->createApiKey([
            'preferred_domain_id' => $this->meinlinkDomain->id,
        ]);

        $response = $this->postJson('http://links.t-api.de/v1/links', [
            'destination_url' => 'https://example.com/override-test',
            'domain_id' => $this->hrefDomain->id,
        ], $headers);

        $response->assertCreated();
        $link = Link::findOrFail($response->json('id'));

        $this->assertSame($this->hrefDomain->id, $link->domain_id);
    }

    public function test_api_key_can_be_created_and_updated_with_preferred_domain_via_api(): void
    {
        [$authKey, $headers] = $this->createApiKey();

        // Create key with preferred domain
        $res = $this->postJson('http://links.t-api.de/v1/api-keys', [
            'name' => 'Meinlink Automated Key',
            'preferred_domain_id' => $this->meinlinkDomain->id,
        ], $headers);

        $res->assertCreated();
        $newKeyId = $res->json('id');
        $this->assertSame($this->meinlinkDomain->id, $res->json('preferred_domain_id'));

        $keyModel = ApiKey::findOrFail($newKeyId);
        $this->assertSame($this->meinlinkDomain->id, $keyModel->preferred_domain_id);

        // Update key preferred domain
        $patchRes = $this->patchJson("http://links.t-api.de/v1/api-keys/{$newKeyId}", [
            'preferred_domain_id' => $this->hrefDomain->id,
        ], $headers);

        $patchRes->assertOk();
        $this->assertSame($this->hrefDomain->id, $keyModel->fresh()->preferred_domain_id);

        // Fetch single key
        $showRes = $this->getJson("http://links.t-api.de/v1/api-keys/{$newKeyId}", $headers);
        $showRes->assertOk();
        $this->assertSame('href.nz', $showRes->json('preferred_domain.hostname'));
    }

    public function test_api_key_manager_livewire_component_saves_preferred_domain(): void
    {
        Livewire::actingAs($this->user)
            ->test(ApiKeyManager::class)
            ->set('keyName', 'Livewire Key')
            ->set('newKeyPreferredDomainId', $this->meinlinkDomain->id)
            ->call('createKey')
            ->assertHasNoErrors();

        $key = ApiKey::where('name', 'Livewire Key')->firstOrFail();
        $this->assertSame($this->meinlinkDomain->id, $key->preferred_domain_id);

        Livewire::actingAs($this->user)
            ->test(ApiKeyManager::class)
            ->call('openSettings', $key->id)
            ->assertSet('settingsPreferredDomainId', $this->meinlinkDomain->id)
            ->set('settingsPreferredDomainId', $this->hrefDomain->id)
            ->call('saveSettings')
            ->assertHasNoErrors();

        $this->assertSame($this->hrefDomain->id, $key->fresh()->preferred_domain_id);
    }

    public function test_api_v3_discovery_and_endpoints(): void
    {
        \App\Models\ApiVersion::updateOrCreate(
            ['version' => 3],
            ['status' => \App\Enums\ApiVersionStatus::Active]
        );

        [$apiKey, $headers] = $this->createApiKey([
            'api_version' => 3,
            'preferred_domain_id' => $this->meinlinkDomain->id,
        ]);

        // Public discovery
        $discovery = $this->getJson('http://links.t-api.de/v3');
        $discovery->assertOk();
        $this->assertSame(3, $discovery->json('data.version'));
        $this->assertTrue($discovery->json('data.features.preferred_domains'));

        // Authenticated /v3/me
        $me = $this->getJson('http://links.t-api.de/v3/me', $headers);
        $me->assertOk();
        $this->assertSame('meinlink.at', $me->json('data.api_key.preferred_domain.hostname'));

        // Authenticated /v3/domains
        $domains = $this->getJson('http://links.t-api.de/v3/domains', $headers);
        $domains->assertOk();
        $this->assertNotEmpty($domains->json('data.domains'));

        // Shorten link via v3 with preferred domain auto-resolution
        $linkRes = $this->postJson('http://links.t-api.de/v3/links', [
            'destination_url' => 'https://example.com/v3-test',
        ], $headers);

        $linkRes->assertCreated();
        $createdLink = Link::findOrFail($linkRes->json('data.id'));
        $this->assertSame('meinlink.at', $createdLink->domain->hostname);
    }
}
