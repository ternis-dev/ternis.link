<?php

declare(strict_types=1);

namespace Tests\Feature;

use AltchaOrg\Altcha\Algorithm\Sha;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\SolveChallengeOptions;
use App\Livewire\Public\ShortenForm;
use App\Models\Domain;
use App\Services\AltchaService;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AltchaProtectionTest extends TestCase
{
    use RefreshDatabase;

    private Domain $publicDomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->publicDomain = Domain::where('hostname', 'href.nz')->firstOrFail();

        // Cheap challenges keep the real solve loop fast in tests.
        config(['services.altcha.cost' => 5]);
    }

    /**
     * Solve a real challenge through the production code path.
     */
    private function solvedPayload(): string
    {
        $service = app(AltchaService::class);
        $challenge = $service->challenge();

        $altcha = new Altcha(hmacSignatureSecret: $service->secret());
        $solution = $altcha->solveChallenge(new SolveChallengeOptions(
            challenge: \AltchaOrg\Altcha\Challenge::fromArray($challenge),
            algorithm: new Sha(),
            timeout: 10.0,
        ));

        $this->assertNotNull($solution, 'Altcha challenge should be solvable in tests');

        return (new \AltchaOrg\Altcha\Payload(
            challenge: \AltchaOrg\Altcha\Challenge::fromArray($challenge),
            solution: $solution,
        ))->toBase64();
    }

    public function test_challenge_endpoint_issues_signed_challenges(): void
    {
        $response = $this->get('http://href.nz/altcha/challenge');

        $response->assertStatus(200);
        $response->assertJsonStructure(['parameters', 'signature']);
        $this->assertSame('SHA-256', $response->json('parameters.algorithm'));
    }

    public function test_form_blocks_submission_without_payload(): void
    {
        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/missing-proof')
            ->call('create')
            ->assertHasErrors(['altcha_payload' => 'Please complete the security check.'])
            ->assertSet('errorKind', 'security');

        $this->assertDatabaseMissing('links', [
            'destination_url' => 'https://example.com/missing-proof',
        ]);
    }

    public function test_form_blocks_garbage_payload(): void
    {
        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/bad-proof')
            ->set('altcha_payload', 'not-a-real-payload')
            ->call('create')
            ->assertHasErrors(['altcha_payload' => 'Security check failed — please try again.'])
            ->assertSet('errorKind', 'security')
            ->assertDispatched('reset-altcha');

        $this->assertDatabaseMissing('links', [
            'destination_url' => 'https://example.com/bad-proof',
        ]);
    }

    public function test_form_blocks_tampered_payload(): void
    {
        $payload = $this->solvedPayload();
        $tampered = substr($payload, 0, -4).'AAAA';

        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/tampered-proof')
            ->set('altcha_payload', $tampered)
            ->call('create')
            ->assertHasErrors(['altcha_payload' => 'Security check failed — please try again.']);

        $this->assertDatabaseMissing('links', [
            'destination_url' => 'https://example.com/tampered-proof',
        ]);
    }

    public function test_form_succeeds_with_solved_challenge(): void
    {
        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/valid-proof')
            ->set('altcha_payload', $this->solvedPayload())
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('shortUrl', fn ($val) => is_string($val) && str_starts_with($val, 'https://href.nz/'))
            ->assertDispatched('reset-altcha');

        $this->assertDatabaseHas('links', [
            'destination_url' => 'https://example.com/valid-proof',
            'domain_id' => $this->publicDomain->id,
        ]);
    }

    public function test_solved_payload_is_single_use(): void
    {
        $payload = $this->solvedPayload();

        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/first-use')
            ->set('altcha_payload', $payload)
            ->call('create')
            ->assertHasNoErrors();

        // Same payload replayed on a fresh form must fail.
        Livewire::test(ShortenForm::class)
            ->set('destination_url', 'https://example.com/replay-use')
            ->set('altcha_payload', $payload)
            ->call('create')
            ->assertHasErrors(['altcha_payload' => 'Security check failed — please try again.']);

        $this->assertDatabaseMissing('links', [
            'destination_url' => 'https://example.com/replay-use',
        ]);
    }

    public function test_landing_page_uses_self_hosted_widget_only(): void
    {
        $response = $this->get('http://href.nz/');

        $response->assertStatus(200);
        $response->assertSee('<altcha-widget', escape: false);
        $response->assertSee('/altcha/challenge', escape: false);
        $response->assertDontSee('challenges.cloudflare.com', escape: false);
    }

    public function test_reset_form_dispatches_reset_altcha_event(): void
    {
        Livewire::test(ShortenForm::class)
            ->set('altcha_payload', 'some-payload')
            ->call('resetForm')
            ->assertSet('altcha_payload', null)
            ->assertDispatched('reset-altcha');
    }
}
