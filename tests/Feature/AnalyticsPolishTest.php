<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\LinkAnalytics;
use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsPolishTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Domain $domain;

    private Link $link;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
        $this->domain = Domain::where('hostname', 'href.nz')->first();
        $this->link = Link::create([
            'slug' => 'analyticspolish',
            'destination_url' => 'https://ternis.dev',
            'domain_id' => $this->domain->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
    }

    private function recordClick(array $overrides = []): Click
    {
        return Click::create(array_merge([
            'link_id' => $this->link->id,
            'referrer' => 'https://example.com',
            'user_agent' => 'Mozilla/5.0 (Macintosh) AppleWebKit/537.36 Chrome/120.0 Safari/537.36',
            'ip_hash' => hash('sha256', uniqid()),
            'country_code' => 'DE',
            'city' => 'Berlin',
            'is_direct_url' => false,
            'created_at' => now(),
        ], $overrides));
    }

    public function test_analytics_renders_chart_and_breakdowns(): void
    {
        $this->recordClick(['referrer' => 'https://example.com', 'country_code' => 'DE']);
        $this->recordClick([
            'referrer' => 'https://example.com',
            'country_code' => 'DE',
            'ip_hash' => hash('sha256', 'other'),
            'user_agent' => 'Mozilla/5.0 Firefox/121.0',
        ]);

        $response = $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$this->link->id}");

        $response->assertStatus(200);
        $response->assertSee('Clicks over time');
        $response->assertSee('Top Referrers');
        $response->assertSee('Top Countries');
        $response->assertSee('Browsers');
        $response->assertSee('Export CSV');
        // Referrer share: 2/2 clicks = 100%
        $response->assertSee('100%');
        // Browser families bucketed from user agents
        $response->assertSee('Chrome');
        $response->assertSee('Firefox');
    }

    public function test_analytics_empty_state(): void
    {
        $response = $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$this->link->id}");

        $response->assertStatus(200);
        $response->assertSee('No clicks in the last 7 days yet');
    }

    public function test_analytics_period_switching(): void
    {
        // Old enough for every window (young links are capped, see below).
        $this->link->created_at = now()->subDays(100);
        $this->link->save();

        $this->recordClick(['created_at' => now()->subDays(2)]);
        $this->recordClick(['created_at' => now()->subDays(60), 'ip_hash' => hash('sha256', 'old')]);

        $test = Livewire::actingAs($this->user)
            ->test(LinkAnalytics::class, ['link' => $this->link->fresh()]);

        // Default 30d window excludes the 60-day-old click
        $test->assertSee('Clicks · last 30 days');
        $this->assertSame(1, $test->viewData('totalClicks'));

        $test->call('setPeriod', 90);
        $this->assertSame(2, $test->viewData('totalClicks'));
        $test->assertSee('Clicks · last 90 days');

        $test->call('setPeriod', 7);
        $this->assertSame(1, $test->viewData('totalClicks'));

        // Invalid period falls back to the longest available window
        $test->call('setPeriod', 999);
        $test->assertSet('period', 90);
    }

    public function test_analytics_chart_is_zero_filled(): void
    {
        // Old enough for the full 30-day window.
        $this->link->created_at = now()->subDays(40);
        $this->link->save();

        $this->recordClick(['created_at' => now()]);

        $test = Livewire::actingAs($this->user)
            ->test(LinkAnalytics::class, ['link' => $this->link->fresh()]);

        $days = $test->viewData('clicksByDay');

        $this->assertCount(30, $days);
        $this->assertSame(1, $days->last()['count']);
        $this->assertSame(0, $days->first()['count']);
    }

    public function test_export_csv_downloads_clicks(): void
    {
        $this->recordClick(['referrer' => 'https://export.test', 'country_code' => 'AT']);

        $response = $this->actingAs($this->user)
            ->get("http://dash.ternis.link/links/{$this->link->id}/export");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('timestamp,referrer,user_agent,country_code,city,ip_hash', $content);
        $this->assertStringContainsString('https://export.test', $content);
    }

    public function test_export_csv_requires_ownership(): void
    {
        // Guest first: actingAs() below persists for later requests.
        $this->get("http://dash.ternis.link/links/{$this->link->id}/export")
            ->assertRedirect('http://dash.ternis.link/login');

        $other = User::factory()->create();

        $this->actingAs($other)
            ->get("http://dash.ternis.link/links/{$this->link->id}/export")
            ->assertStatus(404);
    }

    public function test_average_per_day_caps_at_link_age(): void
    {
        // The reported case: created yesterday, 4 clicks → 2/day,
        // not 4/30 (0.1).
        $this->link->created_at = now()->subDay();
        $this->link->save();

        for ($i = 0; $i < 4; $i++) {
            $this->recordClick();
        }

        $test = Livewire::actingAs($this->user)
            ->test(LinkAnalytics::class, ['link' => $this->link->fresh()]);

        $this->assertSame(4, $test->viewData('totalClicks'));
        $this->assertSame(2.0, $test->viewData('averagePerDay'));
    }

    public function test_average_per_day_respects_window_and_today(): void
    {
        // Created today: divide by 1, never by zero.
        for ($i = 0; $i < 3; $i++) {
            $this->recordClick();
        }

        $test = Livewire::actingAs($this->user)
            ->test(LinkAnalytics::class, ['link' => $this->link]);

        $this->assertSame(3.0, $test->viewData('averagePerDay'));

        // Old link, short window: the window still caps the divisor.
        $this->link->created_at = now()->subDays(60);
        $this->link->save();

        Click::query()->delete();
        $this->recordClick(['created_at' => now()->subDays(2)]);
        $this->recordClick(['created_at' => now()->subDays(1)]);

        $test = Livewire::actingAs($this->user)
            ->test(LinkAnalytics::class, ['link' => $this->link->fresh()]);
        $test->call('setPeriod', 7);

        $this->assertSame(2, $test->viewData('totalClicks'));
        $this->assertSame(round(2 / 7, 1), $test->viewData('averagePerDay'));
    }

    public function test_periods_are_capped_by_link_age(): void
    {
        // Fresh link: 7d only, default clamped down from 30.
        $fresh = Livewire::actingAs($this->user)
            ->test(LinkAnalytics::class, ['link' => $this->link]);
        $fresh->assertSet('period', 7);
        $fresh->call('setPeriod', 90);
        $fresh->assertSet('period', 7);
        $fresh->assertSee('Longer ranges unlock as the link ages.');

        // Ten days old: 7/30d, 90d rejected back to the max available.
        $this->link->created_at = now()->subDays(10);
        $this->link->save();

        $mid = Livewire::actingAs($this->user)
            ->test(LinkAnalytics::class, ['link' => $this->link->fresh()]);
        $mid->assertSet('period', 30);
        $mid->call('setPeriod', 90);
        $mid->assertSet('period', 30);
        $mid->call('setPeriod', 7);
        $mid->assertSet('period', 7);

        // Old link: everything available.
        $this->link->created_at = now()->subDays(100);
        $this->link->save();

        $old = Livewire::actingAs($this->user)
            ->test(LinkAnalytics::class, ['link' => $this->link->fresh()]);
        $old->assertSet('period', 30);
        $old->call('setPeriod', 90);
        $old->assertSet('period', 90);
        $old->assertDontSee('Longer ranges unlock as the link ages.');
    }

    public function test_chart_type_switching(): void
    {
        // The canvas renders once clicks exist.
        $this->recordClick();

        $test = Livewire::actingAs($this->user)
            ->test(LinkAnalytics::class, ['link' => $this->link]);

        $test->assertSet('chartType', 'bar');
        $test->assertSee('data-chart-type="bar"', escape: false);

        $test->call('setChartType', 'line');
        $test->assertSet('chartType', 'line');
        $test->assertSee('data-chart-type="line"', escape: false);

        // Invalid input falls back to bars, never breaks the canvas.
        $test->call('setChartType', 'pie');
        $test->assertSet('chartType', 'bar');
    }

    public function test_browser_family_classification(): void
    {
        $this->assertSame('Chrome', LinkAnalytics::browserFamily('Mozilla/5.0 Chrome/120.0 Safari/537.36'));
        $this->assertSame('Firefox', LinkAnalytics::browserFamily('Mozilla/5.0 Firefox/121.0'));
        $this->assertSame('Safari', LinkAnalytics::browserFamily('Mozilla/5.0 Version/17.0 Safari/605.1.15'));
        $this->assertSame('Edge', LinkAnalytics::browserFamily('Mozilla/5.0 Chrome/120.0 Edg/120.0'));
        $this->assertSame('Bots', LinkAnalytics::browserFamily('Googlebot/2.1 (+http://www.google.com/bot.html)'));
        $this->assertSame('Scripts', LinkAnalytics::browserFamily('curl/8.0'));
        $this->assertSame('Unknown', LinkAnalytics::browserFamily(null));
        $this->assertSame('Other', LinkAnalytics::browserFamily('SomeRandomAgent/1.0'));
    }
}
