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
 * Retention proof: API request logs AND activity/audit history are
 * intentionally kept indefinitely — there is no prune command for
 * either table, so no schedule, operator action, or individual
 * request deletes them, not even after decades (see privacy policy
 * for the stated basis).
 */
class LogRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class]);
    }

    public function test_api_request_logs_are_never_pruned(): void
    {
        ApiRequestLog::record('GET', 'links.t-api.de', '/v1/links', 200, 3);
        ApiRequestLog::whereNotNull('id')->update(['created_at' => now()->subYears(30)]);

        ApiRequestLog::record('GET', 'links.t-api.de', '/v1/links', 200, 3);

        // No prune command exists for this table — rows survive everything.
        $this->assertSame(2, ApiRequestLog::count());
        $this->assertArrayNotHasKey(
            'privacy:prune-api-logs',
            Artisan::all(),
            'A prune command for api_request_logs must not exist.'
        );
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

        $event = $events->first(fn ($e) => str_contains((string) ($e->command ?? ''), 'privacy:prune-ips'));

        $this->assertNotNull($event, 'Missing schedule for privacy:prune-ips');
        $this->assertEquals('0 0 * * *', $event->getExpression());

        foreach (['prune-api', 'prune-activity'] as $forbidden) {
            $this->assertNull(
                $events->first(fn ($e) => str_contains((string) ($e->command ?? ''), $forbidden)),
                "Log tables must have no scheduled prune ({$forbidden})."
            );
        }
    }
}
