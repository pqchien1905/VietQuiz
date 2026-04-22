<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'gradable_type', 'gradable_id',
        'score', 'feedback', 'grader_id', 'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'graded_at' => 'datetime',
        ];
    }

    /* ── Relationships ── */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'grader_id');
    }

    public function gradable(): MorphTo
    {
        return $this->morphTo();
    }
}
