<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id', 'audience_role', 'type', 'title', 'body', 'data', 'is_read',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_read' => 'boolean',
        ];
    }

    /* ── Relationships ── */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Notification $notification): void {
            if (! $notification->audience_role && $notification->user_id) {
                $notification->audience_role = User::whereKey($notification->user_id)->value('role') ?: 'student';
            }
        });
    }

    /* ── Scopes ── */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForAudience($query, string $role)
    {
        return $query->where('audience_role', $role);
    }
}
