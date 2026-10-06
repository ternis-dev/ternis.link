<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| Private per-user channel carrying dashboard link events
| (`DashboardLinkChanged`: created, deactivated). Only the owning user
| may listen — admins moderate cross-user links on the admin host, not
| over this socket.
*/

Broadcast::channel('dashboard.{userId}', function ($user, string $userId): bool {
    return (string) $user->getAuthIdentifier() === (string) $userId;
});
