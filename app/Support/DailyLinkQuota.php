<?php

namespace App\Support;

use App\Models\User;

class DailyLinkQuota
{
    public static function forUser(User $user): array
    {
        $limit = $user->plan?->max_links_per_day;
        $used = $user->links()->where('created_at', '>=', now()->startOfDay())->count();

        return [
            'scope' => 'account',
            'plan' => $user->plan?->name ?? 'current',
            'limit' => $limit,
            'used' => $used,
            'remaining' => $limit === null ? null : max(0, $limit - $used),
            'resets_at' => now()->addDay()->startOfDay()->toIso8601String(),
        ];
    }
}
