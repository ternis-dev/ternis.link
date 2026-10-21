<?php

namespace App\Exceptions;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DailyLinkLimitException extends ValidationException
{
    public function __construct(public readonly array $quota)
    {
        $validator = Validator::make([], []);
        $validator->errors()->add('destination_url',
            "Daily link limit reached ({$quota['limit']} per day on the {$quota['plan']} plan). "
            .'This quota is shared across your account, including all API keys. '
            ."Try again after {$quota['resets_at']} or change your plan.");
        parent::__construct($validator);
    }
}
