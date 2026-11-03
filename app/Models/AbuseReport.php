<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbuseReport extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'link_id',
        'reported_url',
        'category',
        'reporter_email',
        'reporter_name',
        'reason',
        'status',
        'action_taken',
        'ip_hash',
        'admin_notes',
        'reviewed_by_id',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public const CATEGORIES = [
        'phishing' => 'Phishing & Credential Theft',
        'malware' => 'Malware, Ransomware & Exploits',
        'spam' => 'Spam & Unsolicited Bulk Messaging',
        'fraud' => 'Fraud, Scams & Deception',
        'copyright' => 'Copyright & Trademark Infringement',
        'harassment' => 'Harassment & Threatening Content',
        'other' => 'Other Policy Violation',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeInvestigating(Builder $query): Builder
    {
        return $query->where('status', 'investigating');
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'resolved');
    }

    public function scopeDismissed(Builder $query): Builder
    {
        return $query->where('status', 'dismissed');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }
}
