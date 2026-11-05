<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;

class DemoUrlBlockedException extends ValidationException
{
    public const URL = 'https://test.ternis.link/blocked-by-ai';

    public static function blocked(): self
    {
        return static::withMessages([
            'destination_url' => 'Demo: Our AI security check blocked this URL. No short link was created.',
        ]);
    }
}
