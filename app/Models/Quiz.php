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
        'teacher_id', 'folder_id', 'course_id', 'class_id', 'title', 'description',
        'duration_minutes', 'time_limit', 'total_points', 'passing_score', 'max_attempts',
        'status', 'start_at', 'end_at', 'shuffle_questions', 'shuffle_answers', 'is_shuffle', 'show_result',
        'quiz_type', 'anti_cheat_enabled', 'assigned_students',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'shuffle_questions' => 'boolean',
            'shuffle_answers' => 'boolean',
            'is_shuffle' => 'boolean',
            'show_result' => 'boolean',
            'anti_cheat_enabled' => 'boolean',
            'assigned_students' => 'array',
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

    public function folder(): BelongsTo
    {
        return $this->belongsTo(QuizFolder::class, 'folder_id');
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'quiz_user')
            ->withPivot(['score', 'total_points', 'answers', 'started_at', 'submitted_at', 'is_graded', 'shuffled_options']);
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

    public function isAssignedToUser(User $user): bool
    {
        if ($this->assigned_students !== null && !empty($this->assigned_students)) {
            return in_array($user->id, $this->assigned_students);
        }

        $hasClassOrCourse = $this->class_id !== null || $this->course_id !== null;
        if (!$hasClassOrCourse) {
            return true;
        }

        return ($this->class_id !== null && $user->classes()->where('classes.id', $this->class_id)->exists())
            || ($this->course_id !== null && $user->courses()->where('courses.id', $this->course_id)->exists());
    }
}
