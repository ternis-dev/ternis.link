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
}
