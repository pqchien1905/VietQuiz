<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'name',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'folder_id');
    }
}
