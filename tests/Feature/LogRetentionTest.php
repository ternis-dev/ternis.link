<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ApiRequestLog;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Retention proof: neither API request logs nor activity/audit logs
 * are kept "forever". Both have a bounded window enforced by a daily
 * scheduled prune command (GDPR Art. 5(1)(e) storage limitation).
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

    public function test_activity_logs_are_deleted_after_3_years(): void
    {
        $old = ActivityLog::create(['action' => ActivityLog::AUTH_LOGIN]);
        // created_at is not fillable (append-only default) — backdate explicitly.
        ActivityLog::whereKey($old->id)->update(['created_at' => now()->subDays(1096)]);
        ActivityLog::create(['action' => ActivityLog::AUTH_LOGIN]);

        $this->artisan('privacy:prune-activity-logs')
            ->assertSuccessful()
            ->expectsOutputToContain('Pruned activity logs: 1 row(s)');

        $this->assertSame(1, ActivityLog::count());
        $this->assertEquals(ActivityLog::AUTH_LOGIN, ActivityLog::first()->action);
    }

    public function test_log_pruning_is_scheduled_daily(): void
    {
        $events = collect(app(Schedule::class)->events());

        foreach (['privacy:prune-api-logs', 'privacy:prune-activity-logs', 'privacy:prune-ips'] as $command) {
            $event = $events->first(fn ($e) => str_contains((string) ($e->command ?? ''), $command));

            $this->assertNotNull($event, "Missing schedule for {$command}");
            $this->assertEquals('0 0 * * *', $event->getExpression());
        }
    }
}
