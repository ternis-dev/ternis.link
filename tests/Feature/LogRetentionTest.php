<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ApiRequestLog;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Retention proof: API request logs are bounded (90-day rolling window
 * via a daily prune), while activity/audit history is intentionally
 * kept indefinitely — there is no prune command for activity_logs, so
 * no schedule or operator action can delete the trail, not even after
 * decades. GDPR basis for the indefinite trail: legitimate interest
 * (Art. 6(1)(f)); individual erasure requests (Art. 17) are honored
 * on request (see privacy policy).
 */
class LogRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class]);
    }

    public function test_api_request_logs_are_deleted_after_90_days(): void
    {
        ApiRequestLog::record('GET', 'links.t-api.de', '/v1/links', 200, 3);
        ApiRequestLog::whereNotNull('id')->update(['created_at' => now()->subDays(91)]);

        ApiRequestLog::record('GET', 'links.t-api.de', '/v1/links', 200, 3);

        $this->assertSame(2, ApiRequestLog::count());

        $this->artisan('privacy:prune-api-logs')->assertSuccessful();

        $remaining = ApiRequestLog::count();
        $this->assertSame(1, $remaining);
    }

    public function test_activity_logs_are_never_pruned(): void
    {
        $old = ActivityLog::create(['action' => ActivityLog::AUTH_LOGIN]);
        // created_at is not fillable (append-only default) — backdate explicitly.
        ActivityLog::whereKey($old->id)->update(['created_at' => now()->subYears(30)]);
        ActivityLog::create(['action' => ActivityLog::AUTH_LOGIN]);

        // No prune command exists for this table — the trail survives everything.
        $this->assertSame(2, ActivityLog::count());
        $this->assertArrayNotHasKey(
            'privacy:prune-activity-logs',
            Artisan::all(),
            'A prune command for activity_logs must not exist.'
        );
    }

    public function test_log_pruning_is_scheduled_daily(): void
    {
        $events = collect(app(Schedule::class)->events());

        foreach (['privacy:prune-api-logs', 'privacy:prune-ips'] as $command) {
            $event = $events->first(fn ($e) => str_contains((string) ($e->command ?? ''), $command));

            $this->assertNotNull($event, "Missing schedule for {$command}");
            $this->assertEquals('0 0 * * *', $event->getExpression());
        }

        $this->assertNull(
            $events->first(fn ($e) => str_contains((string) ($e->command ?? ''), 'prune-activity')),
            'Activity logs must have no scheduled prune.'
        );
    }
}
