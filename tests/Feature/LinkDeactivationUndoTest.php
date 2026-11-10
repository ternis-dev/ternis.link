<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use App\Services\LinkDeactivationUndo;
use App\Services\LinkService;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkDeactivationUndoTest extends TestCase
{
    use RefreshDatabase;

    private function deactivatedLink(): array
    {
        $this->seed([PlanSeeder::class, DomainSeeder::class]);
        $user = User::factory()->create();
        $link = Link::create([
            'user_id' => $user->id, 'domain_id' => Domain::where('hostname', 'href.nz')->firstOrFail()->id,
            'slug' => 'undo-me', 'destination_url' => 'https://example.com', 'is_active' => true,
        ]);
        app(LinkService::class)->deactivate($link);
        $token = app(LinkDeactivationUndo::class)->remember($link, $user);

        return [$user, $link, $token];
    }

    public function test_undo_is_account_bound_and_single_use(): void
    {
        [$user, $link, $token] = $this->deactivatedLink();
        $undo = app(LinkDeactivationUndo::class);
        $this->assertFalse($undo->restore($token, User::factory()->create()));
        $this->assertFalse($link->fresh()->is_active);
        $this->assertTrue($undo->restore($token, $user));
        $this->assertTrue($link->fresh()->is_active);
        $this->assertFalse($undo->restore($token, $user));
        $this->assertDatabaseHas('activity_logs', ['action' => 'link.reactivated']);
    }

    public function test_undo_refuses_changed_or_removed_links(): void
    {
        [$user, $link, $token] = $this->deactivatedLink();
        $link->update(['description' => 'Newer edit']);
        $this->assertFalse(app(LinkDeactivationUndo::class)->restore($token, $user));
        $this->assertFalse($link->fresh()->is_active);
        $token = app(LinkDeactivationUndo::class)->remember($link, $user);
        $link->update(['is_removed' => true]);
        $this->assertFalse(app(LinkDeactivationUndo::class)->restore($token, $user));
    }

    public function test_undo_cannot_reactivate_quarantined_links(): void
    {
        [$user, $link] = $this->deactivatedLink();
        $link->update(['tags' => ['quarantine']]);
        $token = app(LinkDeactivationUndo::class)->remember($link, $user);
        $this->assertFalse(app(LinkDeactivationUndo::class)->restore($token, $user));
        $this->assertFalse($link->fresh()->is_active);
    }

    public function test_undo_expires(): void
    {
        [$user, $link, $token] = $this->deactivatedLink();
        $this->travel(3)->minutes();
        $this->assertFalse(app(LinkDeactivationUndo::class)->restore($token, $user));
        $this->assertFalse($link->fresh()->is_active);
    }
}
