<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VipSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'plan', 'started_at', 'expires_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /* ── Relationships ── */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ── Accessors ── */
    public function getIsActiveAttribute(): bool
    {
        if ($this->status === 'active') {
            if ($this->plan === 'lifetime') return true;
            return $this->expires_at === null || $this->expires_at->isFuture();
        }
        return false;
    }
}
