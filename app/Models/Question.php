<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quiz_id', 'teacher_id', 'folder_id', 'subject', 'content',
        'type', 'options', 'correct_answer', 'points', 'explanation', 'order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    /* ── Relationships ── */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(QuestionFolder::class, 'folder_id');
    }

    /* ── Helpers ── */
    public function isCorrect(string $answer): bool
    {
        return strtolower(trim($answer)) === strtolower(trim($this->correct_answer));
    }

    public function getOptionsArray(): array
    {
        return $this->options ?? [];
    }
}
