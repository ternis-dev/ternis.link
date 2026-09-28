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
            ->assertSee('Lange URLs einfach', escape: false)
            ->assertSee('kurz gemacht', escape: false)
            ->assertSee('Kürzen', escape: false)
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

    public function test_meinlink_form_smart_fix(): void
    {
        $component = Livewire::test(MeinlinkShortenForm::class)
            ->set('destination_url', 'beispiel.at/seite');

        $this->assertSame('https://beispiel.at/seite', $component->get('fixablePreview'));

        $component->call('applyFix')
            ->assertSet('destination_url', 'https://beispiel.at/seite')
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

    public function test_form_stays_english_by_default(): void
    {
        $html = Livewire::test(ShortenForm::class)->html();

        foreach (['Shorten a link', 'Destination URL', 'no account needed'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
    }
}
