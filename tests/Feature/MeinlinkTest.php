<?php

namespace Tests\Feature;

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
            ->assertSee('Wohin darf', escape: false)
            ->assertSee('MEINLINK', escape: false)
            ->assertSee('ml-board', escape: false)
            ->assertSee('Abfahrtsanzeige', escape: false)
            ->assertSee('Link kürzen', escape: false)
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
            ->assertDontSee('Wohin darf', escape: false);
    }

    public function test_meinlink_new_page_is_german(): void
    {
        $this->get('http://meinlink.at/new')
            ->assertOk()
            ->assertSee('Nächste', escape: false)
            ->assertSee('als Gast weiter', escape: false)
            ->assertSee('Link kürzen', escape: false);
    }

    public function test_meinlink_login_is_german(): void
    {
        $this->get('http://meinlink.at/login')
            ->assertOk()
            ->assertSee('Dienstausweis', escape: false)
            ->assertSee('Mit Ternis Auth einloggen', escape: false);
    }

    public function test_meinlink_errors_are_german_board(): void
    {
        $this->get('http://meinlink.at/not-a-real-link')
            ->assertStatus(404)
            ->assertSee('class="ml-error"', escape: false)
            ->assertSee('Zug verpasst', escape: false)
            ->assertSee('meinlink.at', escape: false)
            ->assertDontSee('tl-theme', escape: false);
    }

    public function test_form_speaks_german_in_german_mode(): void
    {
        $html = Livewire::test(ShortenForm::class, ['locale' => 'de'])
            ->set('destination_url', 'https://example.com/gut')
            ->html();

        foreach (['Link kürzen', 'Ziel-URL', 'Kürzen', 'kein Konto nötig', 'Sieht gut aus'] as $needle) {
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

    public function test_german_form_validates_in_german(): void
    {
        Livewire::test(ShortenForm::class, ['locale' => 'de'])
            ->set('destination_url', 'nope')
            ->call('create')
            ->assertHasErrors('destination_url')
            ->assertSee('sieht nicht nach einer gültigen URL aus', escape: false);
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

    public function test_german_form_creates_links(): void
    {
        Livewire::test(ShortenForm::class, ['locale' => 'de'])
            ->set('destination_url', 'https://example.com/deutsch')
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('urlState', 'idle');

        $this->assertDatabaseHas('links', ['destination_url' => 'https://example.com/deutsch']);
    }
}
