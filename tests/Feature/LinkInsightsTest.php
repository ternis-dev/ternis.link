<?php

namespace Tests\Feature;

use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use App\Services\LinkInsights;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The insights page of the public dashboard: a weekday × hour heatmap
 * of the clicks, the links that are about to expire, and the traffic
 * sources.
 */
class LinkInsightsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class]);

        $this->user = User::factory()->create();
    }

    public function test_insights_page_renders_on_both_hosts(): void
    {
        foreach (['my.href.nz', 'my.href.nz'] as $host) {
            $this->actingAs($this->user)
                ->get("http://{$host}/insights")
                ->assertOk()
                ->assertSee('Insights');
        }
    }

    public function test_insights_count_clicks_per_weekday_and_hour(): void
    {
        $link = $this->makeLink();

        // 2026-10-21 is a Wednesday, 14:00.
        $this->makeClick($link, '2026-10-21 14:00:00');
        $this->makeClick($link, '2026-10-21 14:05:00');
        $this->makeClick($link, '2026-10-22 09:00:00');

        $response = $this->actingAs($this->user)
            ->get('http://my.href.nz/insights')
            ->assertOk();

        $heatmap = $response->viewData('heatmap');

        // Monday is index 0, so Wednesday is index 2.
        $this->assertSame(2, $heatmap[2][14]);
        $this->assertSame(1, $heatmap[3][9]);

        // The busiest slot is reported.
        $this->assertSame('Wed 14:00', $response->viewData('bestTimes')[0]['label']);
    }

    public function test_insights_only_count_public_links(): void
    {
        $public = $this->makeLink('href.nz');
        $personal = $this->makeLink('clicked.at');

        $this->makeClick($public, '2026-10-21 14:00:00');
        $this->makeClick($personal, '2026-10-21 15:00:00');

        $response = $this->actingAs($this->user)
            ->get('http://my.href.nz/insights')
            ->assertOk();

        $heatmap = $response->viewData('heatmap');

        $this->assertSame(1, $heatmap[2][14]);
        $this->assertSame(0, $heatmap[2][15]);
    }

    public function test_insights_ignore_direct_url_hits(): void
    {
        $link = $this->makeLink();

        $this->makeClick($link, '2026-10-21 14:00:00', direct: true);

        $response = $this->actingAs($this->user)
            ->get('http://my.href.nz/insights')
            ->assertOk();

        $this->assertSame(0, $response->viewData('heatmap')[2][14]);
    }

    public function test_insights_list_expiring_links(): void
    {
        $link = $this->makeLink();
        $link->update(['expires_at' => now()->addDays(3)]);

        $response = $this->actingAs($this->user)
            ->get('http://my.href.nz/insights')
            ->assertOk();

        $calendar = $response->viewData('expiryCalendar');

        $this->assertCount(1, $calendar);
        $this->assertSame($link->id, $calendar->first()['links']->first()->id);
    }

    public function test_insights_summarise_referrers_and_countries(): void
    {
        $link = $this->makeLink();

        $this->makeClick($link, '2026-10-21 14:00:00', referrer: 'https://news.example', country: 'AT');
        $this->makeClick($link, '2026-10-21 15:00:00', referrer: 'https://news.example', country: 'AT');
        $this->makeClick($link, '2026-10-21 16:00:00', referrer: 'https://blog.example', country: 'DE');

        $response = $this->actingAs($this->user)
            ->get('http://my.href.nz/insights')
            ->assertOk();

        $referrers = $response->viewData('topReferrers');
        $countries = $response->viewData('topCountries');

        $this->assertSame('https://news.example', $referrers[0]['referrer']);
        $this->assertSame(2, $referrers[0]['count']);
        $this->assertSame('AT', $countries[0]['country']);
    }

    public function test_heatmap_select_expression_and_weekday_normalization_support_mysql_and_sqlite(): void
    {
        $insights = app(LinkInsights::class);

        // MySQL expression uses WEEKDAY and HOUR, not SQLite strftime
        $mysqlExpr = $insights->heatmapSelectExpression('mysql');
        $this->assertStringContainsString('WEEKDAY(created_at) as weekday', $mysqlExpr);
        $this->assertStringContainsString('HOUR(created_at) as hour', $mysqlExpr);
        $this->assertStringNotContainsString('strftime', $mysqlExpr);

        // MySQL returns 0 = Monday, 6 = Sunday
        $this->assertSame(0, $insights->normalizeWeekday(0, 'mysql'));
        $this->assertSame(6, $insights->normalizeWeekday(6, 'mysql'));

        // SQLite expression uses strftime
        $sqliteExpr = $insights->heatmapSelectExpression('sqlite');
        $this->assertStringContainsString('strftime("%w", created_at)', $sqliteExpr);
        $this->assertStringContainsString('strftime("%H", created_at)', $sqliteExpr);

        // SQLite returns 0 = Sunday, 1 = Monday; shifted to 6 = Sunday, 0 = Monday
        $this->assertSame(6, $insights->normalizeWeekday(0, 'sqlite'));
        $this->assertSame(0, $insights->normalizeWeekday(1, 'sqlite'));
    }

    public function test_insights_window_is_configurable(): void
    {
        $response = $this->actingAs($this->user)
            ->get('http://my.href.nz/insights?days=30')
            ->assertOk();

        $this->assertSame(30, $response->viewData('days'));

        // An unknown window falls back to the default.
        $response = $this->actingAs($this->user)
            ->get('http://my.href.nz/insights?days=7')
            ->assertOk();

        $this->assertSame(90, $response->viewData('days'));
    }

    private function makeLink(string $hostname = 'href.nz'): Link
    {
        return Link::create([
            'slug' => 'ins-'.Str::random(6),
            'destination_url' => 'https://example.com/insights',
            'domain_id' => Domain::where('hostname', $hostname)->firstOrFail()->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
    }

    private function makeClick(Link $link, string $at, ?string $referrer = null, ?string $country = null, bool $direct = false): Click
    {
        return Click::create([
            'link_id' => $link->id,
            'referrer' => $referrer,
            'country_code' => $country,
            'ip_hash' => hash('sha256', '10.0.0.'.rand(1, 250)),
            'user_agent' => 'Mozilla/5.0 (Test)',
            'is_direct_url' => $direct,
            'created_at' => $at,
        ]);
    }
}
