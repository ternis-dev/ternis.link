<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;

/**
 * Thrown when a destination URL is structurally unsafe for a public
 * short link (non-web scheme, embedded credentials, intranet or
 * non-public IP target).
 *
 * Extends ValidationException so API consumers get a plain 422 with
 * a `destination_url` error and Livewire components can map it onto
 * their error bags like any other validation failure.
 */
class UnsafeUrlException extends ValidationException
{
    public static function forUrl(string $url, string $reason): self
    {
        $exception = static::withMessages([
            'destination_url' => 'That address can’t be shortened — short links must point to a public website (https).',
        ]);

        $exception->reason = $reason;
        $exception->url = $url;

        return $exception;
    }

    public string $reason = '';

    public string $url = '';
}
