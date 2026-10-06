<?php

namespace Tests\Feature;

use App\Events\DashboardLinkChanged;
use App\Livewire\Dashboard\LinkTable;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\ApiVersionSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class BroadcastTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, DomainSeeder::class, ApiVersionSeeder::class]);

        $this->user = User::factory()->create();
    }

    public function test_event_targets_private_user_channel(): void
    {
        $event = new DashboardLinkChanged((string) $this->user->id, 'abc123', 'created');

        $this->assertSame('link.changed', $event->broadcastAs());
        $this->assertSame(['private-dashboard.'.$this->user->id], array_map(
            fn ($channel) => (string) $channel,
            $event->broadcastOn()
        ));
    }

    public function test_channel_authorizes_owner_only(): void
    {
        // Channel signing is local HMAC (no socket server needed).
        // Channels bind to the boot-time default driver, so re-register
        // on the pusher driver after switching (production boots with
        // pusher from the start and needs no such step).
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'test-key',
            'broadcasting.connections.pusher.secret' => 'test-secret',
            'broadcasting.connections.pusher.app_id' => 'test-app',
        ]);
        Broadcast::channel('dashboard.{userId}', fn ($user, string $userId): bool => (string) $user->getAuthIdentifier() === (string) $userId);

        $payload = ['socket_id' => '1.1', 'channel_name' => 'private-dashboard.'.$this->user->id];

        $this->actingAs($this->user)
            ->postJson('/broadcasting/auth', $payload)
            ->assertOk()
            ->assertJsonStructure(['auth']);

        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->postJson('/broadcasting/auth', $payload)
            ->assertForbidden();

        $this->postJson('/broadcasting/auth', $payload)
            ->assertForbidden();
    }

    public function test_deactivate_broadcasts_dashboard_event(): void
    {
        Queue::fake();

        $link = Link::create([
            'slug' => 'ws-deact-1',
            'destination_url' => 'https://example.com/ws',
            'domain_id' => Domain::where('hostname', 'href.nz')->firstOrFail()->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(LinkTable::class)
            ->call('deactivate', $link->id)
            ->assertHasNoErrors();

        Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) {
            return $job->event instanceof DashboardLinkChanged
                && $job->event->slug === 'ws-deact-1'
                && $job->event->action === 'deactivated';
        });
    }
}
