<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'teacher_id', 'class_id', 'course_id', 'title',
        'description', 'attachment', 'type', 'due_at', 'total_points',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
        ];
    }

    /* ── Relationships ── */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function grades(): MorphMany
    {
        return $this->morphMany(Grade::class, 'gradable');
    }

    /* ── Scopes ── */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('due_at')
                ->orWhere('due_at', '>=', now());
        });
    }

    public function scopePast($query)
    {
        return $query->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }
}
