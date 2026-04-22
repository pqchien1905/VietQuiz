<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'teacher_id', 'course_id', 'class_id', 'title', 'description',
        'duration_minutes', 'time_limit', 'total_points', 'passing_score', 'max_attempts',
        'status', 'start_at', 'end_at', 'shuffle_questions', 'is_shuffle', 'show_result',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'shuffle_questions' => 'boolean',
            'is_shuffle' => 'boolean',
            'show_result' => 'boolean',
        ];
    }

    /* ── Relationships ── */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'quiz_user')
            ->withPivot(['score', 'total_points', 'answers', 'started_at', 'submitted_at', 'is_graded']);
    }

    /**
     * Alias for students() — represents quiz attempts by students.
     */
    public function attempts(): BelongsToMany
    {
        return $this->students();
    }

    /* ── Scopes ── */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            });
    }
}
