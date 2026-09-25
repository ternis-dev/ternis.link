<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\ErrorEncounter;
use App\Models\User;
use App\Notifications\AdminSecurityAlert;
use App\Notifications\SecurityAlert;
use App\Notifications\ServerErrorAlert;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Single entry point for user-facing notifications. Everything is
 * queued (ShouldQueue on the notification) and never throws — a
 * notification failure must not break the action that triggered it.
 */
final class Notifier
{
    /**
     * Throttle window for 5xx admin alerts: one notification per
     * exception class + path per window, so an error storm pages
     * once instead of once per exception.
     */
    public const SERVER_ERROR_THROTTLE_SECONDS = 1800;

    public static function security(
        User $user,
        string $title,
        array $lines,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
    ): void {
        try {
            $user->notify(new SecurityAlert($title, $lines, $actionUrl, $actionLabel));
        } catch (Throwable) {
            // Notifications must never break the triggering action.
        }
    }

    /**
     * Fan out to every admin except (optionally) the acting one.
     */
    public static function admins(
        string $title,
        array $lines,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        ?User $except = null,
    ): void {
        try {
            User::where('role', UserRole::Admin->value)
                ->when($except, fn ($q) => $q->where('id', '!=', $except->id))
                ->cursor()
                ->each(fn (User $admin) => $admin->notify(
                    new AdminSecurityAlert($title, $lines, $actionUrl, $actionLabel)
                ));
        } catch (Throwable) {
            // Notifications must never break the triggering action.
        }
    }

    public static function serverError(ErrorEncounter $encounter): void
    {
        try {
            $key = 'server-error-notify:'.md5(
                $encounter->exception_class.'|'.$encounter->method.'|'.$encounter->path
            );

            if (! Cache::add($key, true, self::SERVER_ERROR_THROTTLE_SECONDS)) {
                return;
            }

            User::where('role', UserRole::Admin->value)
                ->cursor()
                ->each(fn (User $admin) => $admin->notify(new ServerErrorAlert(
                    $encounter->http_code,
                    $encounter->exception_class ?? '?',
                    $encounter->method ?? '?',
                    $encounter->host ?? '?',
                    $encounter->path ?? '/',
                    mb_substr($encounter->error_message ?? '', 0, 500),
                )));
        } catch (Throwable) {
            // Notifications must never break error rendering itself.
        }
    }
}
