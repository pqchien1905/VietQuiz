<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'can_switch_role', 'last_active_role', 'avatar', 'phone', 'subject',
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

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /* ── Relationships ── */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(ClassModel::class, 'class_user', 'user_id', 'class_id')
            ->withPivot(['joined_at', 'enrollment_status', 'enrollment_source', 'requested_at', 'approved_at'])
            ->wherePivot('enrollment_status', 'approved');
    }

    public function classEnrollments(): BelongsToMany
    {
        return $this->belongsToMany(ClassModel::class, 'class_user', 'user_id', 'class_id')
            ->withPivot(['joined_at', 'enrollment_status', 'enrollment_source', 'requested_at', 'approved_at']);
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

    public function quizFolders(): HasMany
    {
        return $this->hasMany(QuizFolder::class, 'teacher_id');
    }

    public function questionFolders(): HasMany
    {
        return $this->hasMany(QuestionFolder::class, 'teacher_id');
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

    public function vipPayments(): HasMany
    {
        return $this->hasMany(VipPayment::class);
    }

    public function quizAttempts(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'quiz_user')
            ->withPivot(['score', 'total_points', 'answers', 'started_at', 'submitted_at', 'is_graded', 'attempt_count', 'shuffled_options']);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
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

    public function dashboardRouteName(): string
    {
        return match ($this->role) {
            'teacher' => 'teacher.dashboard',
            default => 'student.dashboard',
        };
    }

    public function dashboardUrl(bool $absolute = true): string
    {
        return route($this->dashboardRouteName(), absolute: $absolute);
    }

    public function isVip(): bool
    {
        return (bool) $this->vipSubscription?->is_active;
    }

    public function canSwitchRole(): bool
    {
        return (bool) $this->can_switch_role;
    }

    public function isDualAccount(): bool
    {
        return $this->can_switch_role === true;
    }

    public function getLastActiveRole(): ?string
    {
        return $this->last_active_role;
    }

    public function setLastActiveRole(string $role): void
    {
        $this->last_active_role = $role;
        $this->save();
    }
}
