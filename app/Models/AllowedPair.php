<?php

namespace App\Models;

/**
 * Alias of AllowedClient for "allowed pairs" domain nomenclature.
 */
class AllowedPair extends AllowedClient
{
    protected $table = 'allowed_clients';
}
