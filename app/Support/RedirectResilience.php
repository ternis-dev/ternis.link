<?php

namespace App\Support;

use Closure;
use Throwable;

class RedirectResilience
{
    /** Optional redirect work must never prevent delivery of the destination. */
    public static function optional(Closure $work, mixed $fallback = null): mixed
    {
        try {
            return $work();
        } catch (Throwable $e) {
            self::reportFailure($e);

            return $fallback;
        }
    }

    public static function reportFailure(Throwable $e): void
    {
        // Error reporting may itself depend on unavailable infrastructure.
        try {
            report($e);
        } catch (Throwable) {
            // Preserve the redirect even when the reporter is unavailable.
        }
    }
}
