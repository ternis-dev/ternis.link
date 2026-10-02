<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\LinkEditForm;
use App\Livewire\Dashboard\LinkForm;
use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use App\Services\LinkService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LinkUtmTest extends TestCase
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
        $this->domain = Domain::where('hostname', 'href.nz')->firstOrFail();

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

    public function test_create_and_redirect_appends_utm(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/links', [
            'destination_url' => 'https://example.com/landing',
            'domain_id' => $this->domain->id,
            'slug' => 'utmtest01',
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => 'spring launch',
        ], $this->headers());

        $response->assertCreated();

        $this->get('http://href.nz/utmtest01')
            ->assertRedirect('https://example.com/landing?utm_source=newsletter&utm_medium=email&utm_campaign=spring+launch');
    }

    public function test_existing_destination_params_win(): void
    {
        Link::create([
            'slug' => 'utmtest02',
            'destination_url' => 'https://example.com/l?utm_source=ads',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'is_active' => true,
        ]);

        $this->get('http://href.nz/utmtest02')
            ->assertRedirect('https://example.com/l?utm_source=ads&utm_medium=email');
    }

    public function test_update_and_clear_utm(): void
    {
        $link = Link::create([
            'slug' => 'utmtest03',
            'destination_url' => 'https://example.com/l',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $this->putJson("http://links.t-api.de/v1/links/{$link->id}", [
            'utm_campaign' => 'q3',
        ], $this->headers())->assertOk();

        $this->assertSame('q3', $link->fresh()->utm_campaign);
        $this->assertNull($link->fresh()->utm_source);

        $this->get('http://href.nz/utmtest03')
            ->assertRedirect('https://example.com/l?utm_campaign=q3');
    }

    public function test_invalid_utm_rejected_and_guest_prohibited(): void
    {
        $this->postJson('http://links.t-api.de/v1/links', [
            'destination_url' => 'https://example.com/x',
            'domain_id' => $this->domain->id,
            'utm_source' => 'a&b=c',
        ], $this->headers())->assertStatus(422);

        $this->postJson('http://links.t-api.de/v1/links/public', [
            'destination_url' => 'https://example.com/x',
            'utm_source' => 'newsletter',
        ])->assertStatus(422)->assertJsonValidationErrors(['utm_source']);
    }

    public function test_normalize_utm_drops_garbage(): void
    {
        $normalized = LinkService::normalizeUtm([
            'utm_source' => ' ok ',
            'utm_medium' => 'a&b',
            'utm_campaign' => str_repeat('x', 200),
        ]);

        $this->assertSame('ok', $normalized['utm_source']);
        $this->assertNull($normalized['utm_medium']);
        $this->assertSame(100, strlen((string) $normalized['utm_campaign']));
    }

    public function test_dashboard_forms_set_utm(): void
    {
        Livewire::actingAs($this->user)
            ->test(LinkForm::class)
            ->set('destination_url', 'https://example.com/form')
            ->set('domain_id', $this->domain->id)
            ->set('utm_source', 'dashboard')
            ->call('create')
            ->assertHasNoErrors();

        $link = Link::where('destination_url', 'https://example.com/form')->firstOrFail();
        $this->assertSame('dashboard', $link->utm_source);

        Livewire::actingAs($this->user)
            ->test(LinkEditForm::class, ['link' => $link])
            ->set('utm_campaign', 'v2')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('v2', $link->fresh()->utm_campaign);
    }
}
