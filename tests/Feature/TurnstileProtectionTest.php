<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Public\ShortenForm;
use App\Models\Domain;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class TurnstileProtectionTest extends TestCase
{
    use RefreshDatabase;

    private Domain $publicDomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->publicDomain = Domain::where('hostname', 'href.nz')->firstOrFail();
    }

    public function test_form_submits_without_turnstile_when_disabled(): void
    {
        config([
            'services.turnstile.key' => null,
            'services.turnstile.secret' => null,
        ]);

        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/no-turnstile')
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('shortUrl', fn ($val) => is_string($val) && str_starts_with($val, 'https://href.nz/'));

        $this->assertDatabaseHas('links', [
            'destination_url' => 'https://example.com/no-turnstile',
            'domain_id' => $this->publicDomain->id,
        ]);
    }

    public function test_form_blocks_submission_when_turnstile_is_enabled_but_token_is_missing(): void
    {
        config([
            'services.turnstile.key' => '1x00000000000000000000AA',
            'services.turnstile.secret' => '1x0000000000000000000000000000000AA',
        ]);

        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/missing-token')
            ->call('create')
            ->assertHasErrors(['turnstile_token' => 'Please complete the security check.'])
            ->assertSet('errorKind', 'security');

        $this->assertDatabaseMissing('links', [
            'destination_url' => 'https://example.com/missing-token',
        ]);
    }

    public function test_form_blocks_submission_when_turnstile_verification_fails(): void
    {
        config([
            'services.turnstile.key' => 'test-site-key',
            'services.turnstile.secret' => 'test-secret-key',
        ]);

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/invalid-token')
            ->set('turnstile_token', 'bad-token')
            ->call('create')
            ->assertHasErrors(['turnstile_token' => 'Security check failed — please try again.'])
            ->assertSet('errorKind', 'security')
            ->assertDispatched('reset-turnstile');

        $this->assertDatabaseMissing('links', [
            'destination_url' => 'https://example.com/invalid-token',
        ]);
    }

    public function test_form_succeeds_when_turnstile_verification_passes(): void
    {
        config([
            'services.turnstile.key' => 'test-site-key',
            'services.turnstile.secret' => 'test-secret-key',
        ]);

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
                'challenge_ts' => now()->toIso8601String(),
                'hostname' => 'href.nz',
            ], 200),
        ]);

        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/valid-turnstile')
            ->set('turnstile_token', 'good-token')
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('shortUrl', fn ($val) => is_string($val) && str_starts_with($val, 'https://href.nz/'))
            ->assertDispatched('reset-turnstile');

        $this->assertDatabaseHas('links', [
            'destination_url' => 'https://example.com/valid-turnstile',
            'domain_id' => $this->publicDomain->id,
        ]);
    }

    public function test_landing_page_includes_turnstile_script_when_key_configured(): void
    {
        config(['services.turnstile.key' => '1x00000000000000000000AA']);

        $response = $this->get('http://href.nz/');

        $response->assertStatus(200);
        $response->assertSee('https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit', escape: false);
    }

    public function test_landing_page_omits_turnstile_script_when_key_not_configured(): void
    {
        config(['services.turnstile.key' => null]);

        $response = $this->get('http://href.nz/');

        $response->assertStatus(200);
        $response->assertDontSee('challenges.cloudflare.com/turnstile', escape: false);
    }

    public function test_reset_form_dispatches_reset_turnstile_event(): void
    {
        Livewire::test(ShortenForm::class)
            ->set('turnstile_token', 'some-token')
            ->call('resetForm')
            ->assertSet('turnstile_token', null)
            ->assertDispatched('reset-turnstile');
    }
}
