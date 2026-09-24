<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;

/**
 * Thrown when a destination URL matches scanner-junk heuristics.
 *
 * Extends ValidationException so API consumers get a plain 422 with
 * a `destination_url` error and Livewire components can map it onto
 * their error bags like any other validation failure. Catch this
 * BEFORE the generic ValidationException to show junk-specific copy.
 */
class JunkUrlException extends ValidationException
{
    /**
     * @param list<string> $reasons Machine-readable notes for logs; the
     *                              user-facing message stays generic so
     *                              scanners learn nothing.
     */
    public static function forUrl(string $url, array $reasons = []): self
    {
        $exception = static::withMessages([
            'destination_url' => 'That doesn’t look like a real website address. Scanner-style probes can’t be shortened — paste the page you actually want to share.',
        ]);

        $exception->reasons = $reasons;
        $exception->url = $url;

        return $exception;
    }

    /** @var list<string> */
    public array $reasons = [];

    public string $url = '';
}
