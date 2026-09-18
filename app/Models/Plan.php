<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_slug_length',
        'custom_subdomain',
        'rate_limit_per_minute',
        'max_links_per_day',
    ];

    protected $casts = [
        'custom_subdomain' => 'boolean',
        'min_slug_length' => 'integer',
        'rate_limit_per_minute' => 'integer',
        'max_links_per_day' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function allowsCustomSubdomain(): bool
    {
        return $this->custom_subdomain;
    }

    public function hasUnlimitedLinks(): bool
    {
        return $this->max_links_per_day === null;
    }
}
