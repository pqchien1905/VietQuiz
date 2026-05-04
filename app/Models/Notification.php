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
        'user_id', 'type', 'title', 'body', 'data', 'is_read',
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

    /* ── Scopes ── */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
