<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected function options(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): array => $this->normalizeOptions($value),
            set: function (mixed $value): ?string {
                $options = $this->normalizeOptions($value);

                return $options === [] ? null : json_encode($options, JSON_UNESCAPED_UNICODE);
            },
        );
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
        return $this->normalizeOptions($this->options);
    }

    private function normalizeOptions(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return array_values($decoded);
            }

            if (is_string($decoded)) {
                $decodedAgain = json_decode($decoded, true);

                if (is_array($decodedAgain)) {
                    return array_values($decodedAgain);
                }
            }
        }

        return [];
    }
}
