<?php

namespace App\Support;

use App\Models\Link;

final class MeinlinkLinkState
{
    public static function blocked(Link $link): bool
    {
        if (in_array('quarantine', $link->tags ?? [], true)) {
            return true;
        }

        return ! $link->is_active && ($link->has_quarantine_evaluation
            ?? $link->threatEvaluations()->where('action_taken', 'quarantined')->exists());
    }

    public static function label(Link $link): string
    {
        return match (true) {
            self::blocked($link) => 'Gesperrt',
            $link->isExpired() => 'Abgelaufen',
            $link->is_active => 'Aktiv',
            default => 'Pausiert',
        };
    }
}
