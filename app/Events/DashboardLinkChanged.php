<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A user's link was created or deactivated somewhere (dashboard,
 * public dashboard, API, import). Broadcast on their private channel
 * so open dashboards refresh live instead of polling.
 *
 * Low volume by design: analytics clicks never broadcast (redirect
 * hot path stays untouched).
 */
class DashboardLinkChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $userId,
        public string $slug,
        public string $action,
    ) {}

    /**
     * @return list<Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('dashboard.'.$this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'link.changed';
    }
}
