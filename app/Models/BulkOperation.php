<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkOperation extends Model
{
    use HasUlids;

    public const TYPES = ['import', 'update', 'deactivate', 'destroy', 'export'];

    public const STATUSES = ['pending', 'processing', 'done', 'partial', 'failed'];

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'total',
        'succeeded',
        'failed',
        'input_filename',
        'result_path',
        'error_summary',
    ];

    protected $casts = [
        'error_summary' => 'array',
        'total' => 'integer',
        'succeeded' => 'integer',
        'failed' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
