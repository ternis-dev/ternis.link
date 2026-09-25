<?php

namespace App\Notifications\Channels;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Delivery channel for the TernisAuth notification API (push/email
 * relay on the auth platform). That endpoint is still in development:
 * until TERNIS_AUTH_NOTIFY_ENDPOINT is configured this channel
 * records intent to the log and delivers nothing, while the mail and
 * database channels carry the load. No code changes are needed once
 * the endpoint ships — set the env var and this channel activates.
 */
class TernisAuthChannel
{
    public function send(object $notifiable, object $notification): void
    {
        try {
            $endpoint = (string) config('services.ternis_auth.notify_endpoint', '');

            if ($endpoint === '' || ! method_exists($notification, 'toTernisAuth')) {
                Log::debug('TernisAuth notify skipped (endpoint not configured).', [
                    'notification' => $notification::class,
                ]);

                return;
            }

            Http::timeout(5)->post($endpoint, $notification->toTernisAuth($notifiable));
        } catch (Throwable $e) {
            Log::warning('TernisAuth notify failed.', [
                'notification' => $notification::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
