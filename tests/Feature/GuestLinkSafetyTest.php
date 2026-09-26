<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\LinkForm;
use App\Livewire\Public\ShortenForm;
use App\Models\Domain;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GuestLinkSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }

    public static function blockedDestinations(): array
    {
        return [
            'loopback v4' => ['http://127.0.0.1/admin'],
            'private 10/8' => ['http://10.0.0.5/'],
            'private 192.168 with port' => ['http://192.168.1.1:8080/x'],
            'private 172.16/12' => ['http://172.16.9.9/'],
            'link-local' => ['http://169.254.10.20/'],
            'multicast' => ['http://224.0.0.251/'],
            'unspecified' => ['http://0.0.0.0/'],
            'loopback v6' => ['http://[::1]/'],
            'v4-mapped loopback' => ['http://[::ffff:127.0.0.1]/'],
            'unique-local v6' => ['http://[fd00::1]/'],
            'localhost' => ['http://localhost:3000/admin'],
            'localhost subdomain' => ['http://app.localhost/'],
            'dotless intranet' => ['http://intranet/'],
            'corp suffix' => ['http://app.corp/'],
            'local suffix' => ['http://printer.local/'],
            'embedded credentials' => ['https://user:pass@example.com/'],
        ];
    }

    #[DataProvider('blockedDestinations')]
    public function test_public_api_rejects_unsafe_guest_destinations(string $url): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/links/public', [
            'destination_url' => $url,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('destination_url');
        $this->assertDatabaseMissing('links', ['destination_url' => $url]);
    }

    #[DataProvider('blockedDestinations')]
    public function test_guest_form_rejects_unsafe_destinations(string $url): void
    {
        Livewire::test(ShortenForm::class)
            ->set('destination_url', $url)
            ->call('create')
            ->assertHasErrors(['destination_url'])
            ->assertSet('errorKind', 'invalid')
            ->assertSet('shortUrl', null);

        $this->assertDatabaseMissing('links', ['destination_url' => $url]);
    }

    public function test_public_api_still_accepts_normal_websites(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/links/public', [
            'destination_url' => 'https://example.com/some/page?q=1',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('links', ['destination_url' => 'https://example.com/some/page?q=1']);
    }

    public function test_public_api_rejects_far_future_guest_expiry(): void
    {
        $response = $this->postJson('http://links.t-api.de/v1/links/public', [
            'destination_url' => 'https://example.com/long-lived',
            'expires_at' => now()->addYears(2)->toIso8601String(),
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('links', ['destination_url' => 'https://example.com/long-lived']);
    }

    public function test_logged_in_users_keep_intranet_links(): void
    {
        // 192.168.x.x is explicitly legitimate for the (shared) junk
        // detector — only the guest-only safety layer blocks it.
        $user = \App\Models\User::factory()->create();
        $domain = Domain::where('hostname', 'href.nz')->firstOrFail();

        Livewire::actingAs($user)
            ->test(LinkForm::class)
            ->set('destination_url', 'http://192.168.1.1/dashboard')
            ->set('domain_id', $domain->id)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('links', [
            'destination_url' => 'http://192.168.1.1/dashboard',
            'user_id' => $user->id,
        ]);
    }
}
