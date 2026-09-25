<?php

namespace Tests\Unit;

use App\Support\CommitVersion;
use Tests\TestCase;

class CommitVersionTest extends TestCase
{
    protected function tearDown(): void
    {
        CommitVersion::flush();

        parent::tearDown();
    }

    public function test_resolves_the_short_commit_id_from_git(): void
    {
        config(['app.asset_commit' => '']);
        CommitVersion::flush();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{7}$/', (string) CommitVersion::short());
    }

    public function test_app_commit_config_wins_over_git(): void
    {
        config(['app.asset_commit' => 'deadbeef0123456789']);
        CommitVersion::flush();

        $this->assertSame('deadbee', CommitVersion::short());
    }

    public function test_url_gains_a_version_query(): void
    {
        CommitVersion::pin('deadbee');

        $this->assertSame(
            '/build/assets/app-abc123.css?v=deadbee',
            CommitVersion::forUrl('/build/assets/app-abc123.css')
        );
    }

    public function test_existing_query_string_stays_intact(): void
    {
        CommitVersion::pin('deadbee');

        $this->assertSame(
            '/build/assets/app-abc123.css?foo=bar&v=deadbee',
            CommitVersion::forUrl('/build/assets/app-abc123.css?foo=bar')
        );
    }

    public function test_url_is_untouched_without_a_known_commit(): void
    {
        CommitVersion::pin(null);

        $this->assertSame(
            '/build/assets/app-abc123.css',
            CommitVersion::forUrl('/build/assets/app-abc123.css')
        );
    }

    public function test_garbage_config_value_falls_back_to_git(): void
    {
        config(['app.asset_commit' => 'not-a-sha']);
        CommitVersion::flush();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{7}$/', (string) CommitVersion::short());
    }
}
