<?php

namespace Tests\Feature;

use App\Livewire\Meinlink\ShortenForm as MeinlinkShortenForm;
use App\Livewire\Public\ShortenForm;
use App\Models\Domain;
use App\Models\Link;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MeinlinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }

    public function test_meinlink_host_resolves_as_public_type(): void
    {
        $this->assertTrue(Domain::where('hostname', 'meinlink.at')->exists());
        $this->assertSame('public', Domain::where('hostname', 'meinlink.at')->first()->type->value);
    }

    public function test_meinlink_landing_is_german_with_own_theme(): void
    {
        $this->get('http://meinlink.at/')
            ->assertOk()
            ->assertSee('meinlink.at', escape: false)
            ->assertSee('Deutschland', escape: false)
            ->assertSee('Lange URLs einfach', escape: false)
            ->assertSee('kurz gemacht', escape: false)
            ->assertSee('Kürzen', escape: false)
            ->assertDontSee('Österreich', escape: false)
            ->assertDontSee('Amt für kurze Links', escape: false)
            ->assertDontSee('ml-board', escape: false)
            ->assertDontSee('Formular LK-8', escape: false)
            ->assertDontSee('long links go in', escape: false)
            ->assertDontSee('sk-root', escape: false);
    }

    public function test_hrefnz_landing_is_untouched(): void
    {
        $this->get('http://href.nz/')
            ->assertOk()
            ->assertSee('long links go in', escape: false)
            ->assertSee('href<span>.nz</span>', escape: false)
            ->assertDontSee('ml-board', escape: false)
            ->assertDontSee('meinlink', escape: false);
    }

    public function test_meinlink_new_page_is_german(): void
    {
        $this->get('http://meinlink.at/new')
            ->assertOk()
            ->assertSee('Neuen Link erstellen', escape: false)
            ->assertSee('Kürzen', escape: false)
            ->assertDontSee('Formular LK-8', escape: false)
            ->assertDontSee('ml-board', escape: false);
    }

    public function test_meinlink_login_is_german(): void
    {
        $this->get('http://meinlink.at/login')
            ->assertOk()
            ->assertSee('Mitglieder-Login', escape: false)
            ->assertSee('Mit Ternis Auth anmelden', escape: false)
            ->assertDontSee('Amt für kurze Links', escape: false)
            ->assertDontSee('ml-board', escape: false);
    }

    public function test_meinlink_errors_are_german(): void
    {
        $this->get('http://meinlink.at/not-a-real-link')
            ->assertStatus(404)
            ->assertSee('Link nicht gefunden', escape: false)
            ->assertSee('meinlink.at', escape: false)
            ->assertDontSee('Aktenzeichen unbekannt', escape: false)
            ->assertDontSee('ml-error', escape: false)
            ->assertDontSee('tl-theme', escape: false);
    }

    public function test_meinlink_form_validates_in_german(): void
    {
        Livewire::test(MeinlinkShortenForm::class)
            ->set('destination_url', 'invalid-url')
            ->call('create')
            ->assertHasErrors('destination_url')
            ->assertSee('sieht nicht nach einer gültigen URL aus', escape: false);
    }

    public function test_meinlink_form_creates_links_on_meinlink_domain(): void
    {
        $component = Livewire::test(MeinlinkShortenForm::class)
            ->set('destination_url', 'https://example.com/meinlink-ziel')
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('urlState', 'idle');

        $shortUrl = $component->get('shortUrl');
        $this->assertNotNull($shortUrl);
        $this->assertStringStartsWith('https://meinlink.at/', $shortUrl);

        $this->assertDatabaseHas('links', [
            'destination_url' => 'https://example.com/meinlink-ziel',
        ]);
    }

    public function test_meinlink_form_allows_choosing_hrefnz_domain(): void
    {
        $component = Livewire::test(MeinlinkShortenForm::class)
            ->set('selectedDomain', 'href.nz')
            ->set('destination_url', 'https://example.com/hrefnz-ziel')
            ->call('create')
            ->assertHasNoErrors();

        $shortUrl = $component->get('shortUrl');
        $this->assertNotNull($shortUrl);
        $this->assertStringStartsWith('https://href.nz/', $shortUrl);

        $hrefDomain = Domain::where('hostname', 'href.nz')->firstOrFail();
        $this->assertDatabaseHas('links', [
            'destination_url' => 'https://example.com/hrefnz-ziel',
            'domain_id' => $hrefDomain->id,
        ]);
    }

    public function test_meinlink_form_allows_choosing_slug_length(): void
    {
        $component = Livewire::test(MeinlinkShortenForm::class)
            ->set('slugLength', 5)
            ->set('destination_url', 'https://example.com/short-slug')
            ->call('create')
            ->assertHasNoErrors();

        $shortUrl = $component->get('shortUrl');
        $slug = basename(parse_url($shortUrl, PHP_URL_PATH));
        $this->assertSame(5, strlen($slug));

        $component9 = Livewire::test(MeinlinkShortenForm::class)
            ->set('slugLength', 9)
            ->set('destination_url', 'https://example.com/longer-slug')
            ->call('create')
            ->assertHasNoErrors();

        $shortUrl9 = $component9->get('shortUrl');
        $slug9 = basename(parse_url($shortUrl9, PHP_URL_PATH));
        $this->assertSame(9, strlen($slug9));
    }

    public function test_meinlink_form_allows_setting_expiration_date(): void
    {
        $expiryDate = now()->addDays(14)->format('Y-m-d');

        $component = Livewire::test(MeinlinkShortenForm::class)
            ->set('expiresAt', $expiryDate)
            ->set('destination_url', 'https://example.com/expiring-link')
            ->call('create')
            ->assertHasNoErrors();

        $link = Link::where('destination_url', 'https://example.com/expiring-link')->firstOrFail();
        $this->assertNotNull($link->expires_at);
        $this->assertSame($expiryDate, $link->expires_at->format('Y-m-d'));
    }

    public function test_meinlink_form_rejects_past_expiration_date(): void
    {
        $pastDate = now()->subDays(2)->format('Y-m-d');

        Livewire::test(MeinlinkShortenForm::class)
            ->set('expiresAt', $pastDate)
            ->set('destination_url', 'https://example.com/past-link')
            ->call('create')
            ->assertHasErrors('expiresAt');
    }

    public function test_meinlink_form_smart_fix(): void
    {
        $component = Livewire::test(MeinlinkShortenForm::class)
            ->set('destination_url', 'beispiel.de/seite');

        $this->assertSame('https://beispiel.de/seite', $component->get('fixablePreview'));

        $component->call('applyFix')
            ->assertSet('destination_url', 'https://beispiel.de/seite')
            ->assertSet('urlState', 'valid');
    }

    public function test_meinlink_short_links_resolve(): void
    {
        $domain = Domain::where('hostname', 'meinlink.at')->first();

        Link::create([
            'slug' => 'hallo-welt',
            'destination_url' => 'https://example.com/ziel',
            'domain_id' => $domain->id,
            'user_id' => null,
            'is_active' => true,
        ]);

        $this->get('http://meinlink.at/hallo-welt')
            ->assertRedirect('https://example.com/ziel');
    }

    public function test_form_speaks_german_in_german_mode(): void
    {
        $html = Livewire::test(ShortenForm::class, ['locale' => 'de'])
            ->set('destination_url', 'https://example.com/gut')
            ->html();

        foreach (['Link kürzen', 'Ziel-URL', 'kein Konto nötig', 'Sieht gut aus'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
    }

    public function test_meinlink_landing_includes_turnstile_with_action_when_configured(): void
    {
        config(['services.turnstile.key' => '1x00000000000000000000AA']);

        $response = $this->get('http://meinlink.at/');

        $response->assertStatus(200);
        $response->assertSee('https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit', escape: false);
        $response->assertSee("action: '".\App\Services\TurnstileService::ACTION."'", escape: false);
        $response->assertSee('data-cf-container', escape: false);
    }

    public function test_meinlink_form_succeeds_when_turnstile_verification_passes(): void
    {
        config([
            'services.turnstile.key' => 'test-site-key',
            'services.turnstile.secret' => 'test-secret-key',
        ]);

        \Illuminate\Support\Facades\Http::fake([
            \App\Services\TurnstileService::VERIFY_URL => \Illuminate\Support\Facades\Http::response([
                'success' => true,
                'hostname' => 'meinlink.at',
                'action' => \App\Services\TurnstileService::ACTION,
            ], 200),
        ]);

        Livewire::test(MeinlinkShortenForm::class)
            ->set('destination_url', 'https://example.com/meinlink-turnstile')
            ->set('turnstile_token', 'good-token')
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('shortUrl', fn ($val) => is_string($val) && str_starts_with($val, 'https://meinlink.at/'))
            ->assertDispatched('reset-turnstile');

        $this->assertDatabaseHas('links', [
            'destination_url' => 'https://example.com/meinlink-turnstile',
        ]);
    }

    public function test_meinlink_form_fails_when_turnstile_token_is_missing(): void
    {
        config([
            'services.turnstile.key' => 'test-site-key',
            'services.turnstile.secret' => 'test-secret-key',
        ]);

        Livewire::test(MeinlinkShortenForm::class)
            ->set('destination_url', 'https://example.com/meinlink-no-token')
            ->call('create')
            ->assertHasErrors(['turnstile_token' => 'Bitte führe die Sicherheitsprüfung durch.']);
    }

    public function test_meinlink_form_fails_when_turnstile_verification_rejected(): void
    {
        config([
            'services.turnstile.key' => 'test-site-key',
            'services.turnstile.secret' => 'test-secret-key',
        ]);

        \Illuminate\Support\Facades\Http::fake([
            \App\Services\TurnstileService::VERIFY_URL => \Illuminate\Support\Facades\Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        Livewire::test(MeinlinkShortenForm::class)
            ->set('destination_url', 'https://example.com/meinlink-bad-token')
            ->set('turnstile_token', 'bad-token')
            ->call('create')
            ->assertHasErrors(['turnstile_token' => 'Sicherheitsprüfung fehlgeschlagen — bitte versuche es erneut.'])
            ->assertDispatched('reset-turnstile');
    }
}
