<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'category', 'subject', 'description', 'status', 'priority', 'admin_response',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
