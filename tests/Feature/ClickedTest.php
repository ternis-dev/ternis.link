<?php

namespace Tests\Feature;

use App\Models\Domain;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClickedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);
    }

    public function test_clicked_host_resolves_as_public_type(): void
    {
        $this->assertTrue(Domain::where('hostname', 'clicked.at')->exists());
        $this->assertSame('public', Domain::where('hostname', 'clicked.at')->first()->type->value);
    }

    public function test_clicked_landing_renders_with_brand_and_preloader(): void
    {
        $this->get('http://clicked.at/')
            ->assertOk()
            ->assertSee('clicked.at', escape: false)
            ->assertSee('Newsletter &amp; email click tracking', escape: false)
            ->assertSee('<link rel="preload" href="/fonts/inter-var.woff2" as="font" type="font/woff2" crossorigin>', escape: false)
            ->assertSee('<link rel="preload" href="/fonts/space-grotesk-var.woff2" as="font" type="font/woff2" crossorigin>', escape: false)
            ->assertSee('id="cl-loader"', escape: false)
            ->assertSee('id="cl-loader-fill"', escape: false)
            ->assertSee('id="cl-load-pct"', escape: false)
            ->assertSee('cl-wrap cl-enter', escape: false);
    }

    public function test_clicked_preview_route_renders(): void
    {
        $this->get('/_preview/clicked')
            ->assertOk()
            ->assertSee('clicked.at', escape: false)
            ->assertSee('id="cl-loader"', escape: false);
    }

    public function test_clicked_landing_supports_locale_switching_via_query_param(): void
    {
        // Switch to German
        $responseDe = $this->get('http://clicked.at/?lang=de');
        $responseDe->assertOk();
        $responseDe->assertSee('Schritt 01', escape: false);
        $responseDe->assertSee('Klicks live beobachten', escape: false);
        $responseDe->assertSee('Newsletter- &amp; E-Mail-Klick-Tracking', escape: false);
        $responseDe->assertCookie('clicked_locale', 'de');

        // Switch to English
        $responseEn = $this->get('http://clicked.at/?lang=en');
        $responseEn->assertOk();
        $responseEn->assertSee('Step 01', escape: false);
        $responseEn->assertSee('Watch the clicks roll in', escape: false);
        $responseEn->assertSee('Newsletter &amp; email click tracking', escape: false);
        $responseEn->assertCookie('clicked_locale', 'en');
    }

    public function test_clicked_landing_detects_locale_from_accept_language_header(): void
    {
        $response = $this->withHeaders(['Accept-Language' => 'de-AT,de;q=0.9,en;q=0.8'])
            ->get('http://clicked.at/');

        $response->assertOk();
        $response->assertSee('Schritt 01', escape: false);
        $response->assertSee('Drei Schritte. Das war&#039;s.', escape: false);
    }

    public function test_how_it_works_steps_and_clean_icons(): void
    {
        $response = $this->get('http://clicked.at/');
        $response->assertOk();

        // Check new clean bar chart icon is used
        $response->assertSee('<path d="M3 3v18h18"/>', escape: false);
        $response->assertSee('<path d="M18 17V9"/>', escape: false);
        $response->assertSee('<path d="M13 17V5"/>', escape: false);
        $response->assertSee('<path d="M8 17v-4"/>', escape: false);

        // Check old ugly overlapping line/rect SVG is gone
        $response->assertDontSee('<path d="M4 11L11 6L16 9L21 3"', escape: false);

        // Check cards and step pills
        $response->assertSee('Step 01');
        $response->assertSee('Step 02');
        $response->assertSee('Step 03');
    }

    public function test_impressum_gateway_redirects_with_domain_and_params(): void
    {
        // On int.ternis.link/impressum
        $response = $this->get('http://int.ternis.link/impressum?domain=clicked.at&lang=de&ref=footer');
        $response->assertRedirect('https://ternis.dev/de/legal/imprint?domain=clicked.at&ref=footer');

        // On int.ternis.link/imprint
        $responseEn = $this->get('http://int.ternis.link/imprint?domain=href.nz&lang=en');
        $responseEn->assertRedirect('https://ternis.dev/en/legal/imprint?domain=href.nz');

        // Auto-detect German for .at domain
        $responseAuto = $this->get('http://int.ternis.link/impressum?domain=meinlink.at');
        $responseAuto->assertRedirect('https://ternis.dev/de/legal/imprint?domain=meinlink.at');
    }
}
