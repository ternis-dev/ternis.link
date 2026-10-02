<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\PrivacySettings;
use App\Models\PrivacyExport;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PrivacySettingsUiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
    }

    public function test_settings_page_shows_privacy_section(): void
    {
        $this->actingAs($this->user)
            ->get('http://dash.ternis.link/settings')
            ->assertOk()
            ->assertSee('Your data', escape: false)
            ->assertSee('Export my data', escape: false)
            ->assertSee('Delete my account', escape: false);
    }

    public function test_export_creates_operation_and_downloads(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(PrivacySettings::class)
            ->call('export')
            ->assertHasNoErrors();

        $export = $this->user->privacyExports()->firstOrFail();
        $this->assertSame('done', $export->fresh()->status);

        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/settings/export/{$export->id}/download")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/zip');

        // Second export while one is pending/processing is refused.
        PrivacyExport::create([
            'user_id' => $this->user->id,
            'status' => 'pending',
            'expires_at' => now()->addWeek(),
        ]);

        Livewire::actingAs($this->user)
            ->test(PrivacySettings::class)
            ->call('export')
            ->assertHasErrors('export');
    }

    public function test_download_rejects_foreign_and_missing_exports(): void
    {
        $other = User::factory()->create();
        $export = PrivacyExport::create([
            'user_id' => $other->id,
            'status' => 'done',
            'path' => 'privacy/missing.zip',
            'expires_at' => now()->addWeek(),
        ]);

        $this->actingAs($this->user)
            ->get("http://dash.ternis.link/settings/export/{$export->id}/download")
            ->assertNotFound();
    }

    public function test_schedule_deletion_requires_fresh_login(): void
    {
        // Stale session (no marker).
        Livewire::actingAs($this->user)
            ->test(PrivacySettings::class)
            ->set('acknowledged', true)
            ->set('confirmText', 'DELETE-ME')
            ->call('scheduleDeletion')
            ->assertHasErrors('confirmText');

        $this->assertNull($this->user->fresh()->deletion_requested_at);

        // Wrong confirmation text.
        $this->withSession(['sso_login_at' => now()->toIso8601String()]);

        Livewire::actingAs($this->user)
            ->test(PrivacySettings::class)
            ->set('acknowledged', true)
            ->set('confirmText', 'delete me')
            ->call('scheduleDeletion')
            ->assertHasErrors('confirmText');

        // Missing acknowledgement.
        Livewire::actingAs($this->user)
            ->test(PrivacySettings::class)
            ->call('scheduleDeletion')
            ->assertHasErrors('confirmText');

        // Fresh login + full confirmation schedules.
        Livewire::actingAs($this->user)
            ->test(PrivacySettings::class)
            ->set('acknowledged', true)
            ->set('confirmText', 'DELETE-ME')
            ->call('scheduleDeletion')
            ->assertHasNoErrors();

        $this->assertNotNull($this->user->fresh()->deletion_requested_at);
    }

    public function test_cancel_deletion(): void
    {
        $this->user->update(['deletion_requested_at' => now()]);

        Livewire::actingAs($this->user)
            ->test(PrivacySettings::class)
            ->call('cancelDeletion')
            ->assertHasNoErrors();

        $this->assertNull($this->user->fresh()->deletion_requested_at);
    }
}
