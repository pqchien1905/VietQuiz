<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'avatar', 'phone', 'subject',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /* ── Relationships ── */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(ClassModel::class, 'class_user', 'class_id', 'user_id')
            ->withPivot('joined_at');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_user')
            ->withPivot('enrolled_at');
    }

    public function createdClasses(): HasMany
    {
        return $this->hasMany(ClassModel::class, 'teacher_id');
    }

    public function createdCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'teacher_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'teacher_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'teacher_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'student_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'student_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function vipSubscription(): HasOne
    {
        return $this->hasOne(VipSubscription::class);
    }

    public function quizAttempts(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'quiz_user')
            ->withPivot(['score', 'total_points', 'answers', 'started_at', 'submitted_at', 'is_graded']);
    }

    /* ── Accessors ── */
    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isVip(): bool
    {
        return $this->vipSubscription?->status === 'active';
    }
}
