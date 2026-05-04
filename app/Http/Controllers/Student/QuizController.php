<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'course_id' => $request->integer('course_id') ?: null,
            'type' => $request->query('type', 'all'),
            'status' => $request->query('status', 'available'),
        ];
        $filters['type'] = in_array($filters['type'], ['all', 'exam', 'practice'], true)
            ? $filters['type']
            : 'all';
        $filters['status'] = in_array($filters['status'], ['available', 'scheduled', 'completed', 'missed'], true)
            ? $filters['status']
            : 'available';

        if ($filters['course_id']) {
            abort_unless($user->courses()->where('courses.id', $filters['course_id'])->exists(), 404);
        }

        $courseIds = $user->courses()->pluck('courses.id');
        $classIds = $user->classes()->pluck('classes.id');
        $courses = $user->courses()->orderBy('name')->get(['courses.id', 'courses.name']);

        $assignedToUser = function ($q) use ($classIds, $courseIds, $user) {
            $q->whereIn('class_id', $classIds)
                ->orWhereIn('course_id', $courseIds)
                ->orWhereJsonContains('assigned_students', $user->id)
                ->orWhere(function ($q) {
                    $q->whereNull('class_id')
                        ->whereNull('course_id')
                        ->where(function ($q) {
                            $q->whereNull('assigned_students')
                                ->orWhereJsonLength('assigned_students', 0);
                        });
                });
        };

        $quizzes = Quiz::where('status', 'published')
            ->where($assignedToUser)
            ->when($filters['course_id'], fn ($query) => $query->where('quizzes.course_id', $filters['course_id']))
            ->when(in_array($filters['type'], ['exam', 'practice'], true), fn ($query) => $query->where('quiz_type', $filters['type']))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $needle = $filters['q'];
                $query->where(function ($inner) use ($needle) {
                    $inner->where('title', 'like', "%{$needle}%")
                        ->orWhere('description', 'like', "%{$needle}%")
                        ->orWhereHas('teacher', fn ($teacher) => $teacher->where('name', 'like', "%{$needle}%"))
                        ->orWhereHas('course', fn ($course) => $course->where('name', 'like', "%{$needle}%"))
                        ->orWhereHas('classModel', fn ($class) => $class->where('name', 'like', "%{$needle}%"));
                });
            })
            ->with(['teacher:id,name,email', 'course:id,name,color,icon', 'classModel:id,name'])
            ->withCount('questions')
            ->orderByRaw('end_at is null')
            ->orderBy('end_at')
            ->latest('created_at')
            ->get();

        $attempts = DB::table('quiz_user')
            ->where('user_id', $user->id)
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->get()
            ->keyBy('quiz_id');

        $quizzes = $quizzes->map(function (Quiz $quiz) use ($attempts) {
            $attempt = $attempts->get($quiz->id);
            $isSubmitted = $attempt && $attempt->submitted_at !== null;
            $isStarted = $attempt && $attempt->started_at !== null && $attempt->submitted_at === null;
            $isExpired = $quiz->end_at !== null && $quiz->end_at->isPast();
            $isScheduled = $quiz->start_at !== null && $quiz->start_at->isFuture();
            $maxScore = (float) ($attempt?->total_points ?: $quiz->total_points ?: $quiz->questions_count ?: 0);
            $score = $attempt?->score !== null ? (float) $attempt->score : null;
            $scorePct = $score !== null && $maxScore > 0 ? round($score / $maxScore * 100) : null;

            if ($isSubmitted) {
                $learningStatus = 'completed';
            } elseif ($isExpired) {
                $learningStatus = 'missed';
            } elseif ($isScheduled) {
                $learningStatus = 'scheduled';
            } elseif ($isStarted) {
                $learningStatus = 'in_progress';
            } else {
                $learningStatus = 'available';
            }

            $quiz->attempt = $attempt;
            $quiz->learning_status = $learningStatus;
            $quiz->score_pct = $scorePct;
            $quiz->score_value = $score;
            $quiz->score_max = $maxScore;
            $quiz->started_at_display = $attempt?->started_at ? Carbon::parse($attempt->started_at) : null;
            $quiz->submitted_at_display = $attempt?->submitted_at ? Carbon::parse($attempt->submitted_at) : null;
            $quiz->context_name = $quiz->course?->name ?? $quiz->classModel?->name ?? 'Bài giao chung';
            $duration = $quiz->time_limit ?? $quiz->duration_minutes;
            $quiz->duration_label = $duration ? ($duration.' phút') : 'Không giới hạn';
            $quiz->due_state = $this->dueState($quiz);

            return $quiz;
        });

        $summarySource = $quizzes;
        $summary = [
            'total' => $summarySource->count(),
            'available' => $summarySource->whereIn('learning_status', ['available', 'in_progress'])->count(),
            'scheduled' => $summarySource->where('learning_status', 'scheduled')->count(),
            'completed' => $summarySource->where('learning_status', 'completed')->count(),
            'missed' => $summarySource->where('learning_status', 'missed')->count(),
            'avg_score' => $summarySource->where('learning_status', 'completed')->whereNotNull('score_pct')->avg('score_pct'),
        ];

        $available = $quizzes
            ->whereIn('learning_status', ['available', 'in_progress'])
            ->values();
        $scheduled = $quizzes
            ->where('learning_status', 'scheduled')
            ->values();
        $completed = $quizzes
            ->where('learning_status', 'completed')
            ->sortByDesc(fn ($quiz) => optional($quiz->submitted_at_display)->timestamp ?? 0)
            ->values();
        $missed = $quizzes
            ->where('learning_status', 'missed')
            ->values();

        $activeTab = $filters['status'];

        return view('pages.student.quizzes', compact(
            'available',
            'scheduled',
            'completed',
            'missed',
            'courses',
            'filters',
            'summary',
            'activeTab'
        ));
    }

    private function dueState(Quiz $quiz): array
    {
        if ($quiz->start_at && $quiz->start_at->isFuture()) {
            return [
                'label' => 'Mở lúc '.$quiz->start_at->format('d/m/Y H:i'),
                'tone' => 'info',
            ];
        }

        if (! $quiz->end_at) {
            return ['label' => 'Không giới hạn hạn nộp', 'tone' => 'muted'];
        }

        if ($quiz->end_at->isPast()) {
            return ['label' => 'Đã hết hạn '.$quiz->end_at->format('d/m/Y H:i'), 'tone' => 'danger'];
        }

        if ($quiz->end_at->isToday()) {
            return ['label' => 'Hạn hôm nay '.$quiz->end_at->format('H:i'), 'tone' => 'danger'];
        }

        if ($quiz->end_at->isTomorrow()) {
            return ['label' => 'Hạn ngày mai '.$quiz->end_at->format('H:i'), 'tone' => 'warning'];
        }

        return ['label' => 'Hạn '.$quiz->end_at->format('d/m/Y H:i'), 'tone' => 'muted'];
    }

    public function take(Request $request, Quiz $quiz)
    {
        $user = $request->user();

        // Check if user is assigned to this quiz
        abort_unless($quiz->isAssignedToUser($user), 403);

        abort_if($quiz->status !== 'published', 403);
        abort_if($quiz->start_at && $quiz->start_at->isFuture(), 403);
        abort_if($quiz->end_at && $quiz->end_at->isPast(), 403);

        if ($user->quizAttempts()->where('quiz_id', $quiz->id)->whereNotNull('submitted_at')->exists()) {
            return redirect()
                ->route('student.quiz-result', $quiz)
                ->with('info', 'Bạn đã nộp bài kiểm tra này. Hệ thống đang hiển thị kết quả gần nhất.');
        }

        // Check existing unfinished attempt
        $existingAttempt = $user->quizAttempts()
            ->where('quiz_id', $quiz->id)
            ->whereNull('submitted_at')
            ->first();

        if ($existingAttempt) {
            $startedAt = Carbon::parse($existingAttempt->pivot->started_at);
        } else {
            // Check max attempts only before creating a new attempt. An unfinished
            // attempt must remain resumable even when max_attempts is 1.
            $attempts = $user->quizAttempts()->where('quiz_id', $quiz->id)->count();
            abort_if($quiz->max_attempts && $attempts >= $quiz->max_attempts, 403);

            $startedAt = now();
            $user->quizAttempts()->attach($quiz->id, ['started_at' => $startedAt]);
        }

        $query = $quiz->questions()->newQuery();
        if ($quiz->shuffle_questions) {
            $query->inRandomOrder();
        } else {
            $query->orderBy('order');
        }
        $questions = $query->limit(50)->get();

        // Shuffle answer options if enabled
        $shuffledOrder = [];
        foreach ($questions as $question) {
            $options = $this->normalizeOptions($question->options);

            if ($quiz->shuffle_answers && $question->options) {
                if (is_array($options) && count($options) > 1) {
                    $shuffled = $options;
                    shuffle($shuffled);
                    $question->shuffled_options = $shuffled;
                    $shuffledOrder[$question->id] = [
                        'original' => $options,
                        'shuffled' => $shuffled,
                    ];
                } else {
                    $question->shuffled_options = $options;
                }
            } else {
                $question->shuffled_options = $options;
            }
        }

        if (! empty($shuffledOrder)) {
            $user->quizAttempts()->updateExistingPivot($quiz->id, [
                'shuffled_options' => json_encode($shuffledOrder),
            ]);
        }

        $quiz->setRelation('questions', $questions);

        return view('pages.student.quiz-take', compact('quiz', 'startedAt'));
    }

    private function normalizeOptions(mixed $options): array
    {
        if (is_array($options)) {
            return $options;
        }

        if (! $options) {
            return [];
        }

        $decoded = json_decode($options, true);
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        return is_array($decoded) ? $decoded : [];
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'answers' => 'required|array',
            'answers.*' => 'nullable',
        ]);

        $user = $request->user();

        // Check if user is assigned to this quiz
        abort_unless($quiz->isAssignedToUser($user), 403);
        abort_if($quiz->status !== 'published', 403);
        abort_if($quiz->start_at && $quiz->start_at->isFuture(), 403);
        abort_if($quiz->end_at && $quiz->end_at->isPast(), 403);

        $attempt = $user->quizAttempts()
            ->where('quiz_id', $quiz->id)
            ->whereNull('submitted_at')
            ->first();

        if (! $attempt) {
            $submittedAttempt = $user->quizAttempts()
                ->where('quiz_id', $quiz->id)
                ->whereNotNull('submitted_at')
                ->orderByDesc('quiz_user.submitted_at')
                ->first();

            if ($submittedAttempt) {
                return response()->json([
                    'success' => true,
                    'already_submitted' => true,
                    'redirect_url' => route('student.quiz-result', $quiz),
                ]);
            }

            $attemptCount = $user->quizAttempts()->where('quiz_id', $quiz->id)->count();
            if ($quiz->max_attempts && $attemptCount >= $quiz->max_attempts) {
                return response()->json([
                    'error' => 'Bạn đã hết số lượt làm bài cho phép.',
                ], 403);
            }

            $user->quizAttempts()->attach($quiz->id, ['started_at' => now()]);
            $attempt = $user->quizAttempts()
                ->where('quiz_id', $quiz->id)
                ->whereNull('submitted_at')
                ->first();

            if (! $attempt) {
                return response()->json([
                    'error' => 'Không thể khởi tạo lượt làm bài. Vui lòng tải lại trang và thử lại.',
                ], 409);
            }
        }

        $questions = $quiz->questions;
        $totalPoints = 0;
        $earnedPoints = 0;
        $questionResults = [];

        $shuffledMap = [];
        $shuffledOptionsRaw = $attempt->pivot->shuffled_options;
        if ($shuffledOptionsRaw) {
            $shuffledMap = json_decode($shuffledOptionsRaw, true) ?? [];
        }

        foreach ($questions as $question) {
            $totalPoints += $question->points ?? 1;
            $userAnswer = $validated['answers'][$question->id] ?? null;
            $isCorrect = false;
            $earned = 0;

            if ($userAnswer !== null && $userAnswer !== '') {
                $isCorrect = $this->isSubmittedAnswerCorrect($question, (string) $userAnswer, $shuffledMap[$question->id] ?? null);
                if ($isCorrect) {
                    $earned = $question->points ?? 1;
                    $earnedPoints += $earned;
                }
            }

            $questionResults[] = [
                'id' => $question->id,
                'is_correct' => $isCorrect,
                'earned_points' => $earned,
                'max_points' => $question->points ?? 1,
            ];
        }

        $user->quizAttempts()->updateExistingPivot($quiz->id, [
            'answers' => json_encode($validated['answers']),
            'score' => $earnedPoints,
            'total_points' => $totalPoints,
            'submitted_at' => now(),
            'is_graded' => true,
        ]);

        $pct = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100) : 0;
        $passed = $pct >= ($quiz->passing_score ?? 50);

        return response()->json([
            'success' => true,
            'score' => $earnedPoints,
            'total' => $totalPoints,
            'percent' => $pct,
            'passed' => $passed,
            'results' => $questionResults,
        ]);
    }

    private function isSubmittedAnswerCorrect($question, string $answer, ?array $shuffleInfo = null): bool
    {
        $correctAnswer = trim((string) $question->correct_answer);

        if ($question->type === 'multiple_choice') {
            $originalOptions = $this->normalizeOptions($question->options);
            $displayedOptions = $originalOptions;

            if (is_array($shuffleInfo)) {
                $originalOptions = is_array($shuffleInfo['original'] ?? null)
                    ? $shuffleInfo['original']
                    : $originalOptions;
                $displayedOptions = is_array($shuffleInfo['shuffled'] ?? null)
                    ? $shuffleInfo['shuffled']
                    : $originalOptions;
            }

            $selectedText = $answer;
            $selectedOriginalIndex = null;

            if (is_numeric($answer)) {
                $selectedIndex = (int) $answer;
                $selectedText = (string) ($displayedOptions[$selectedIndex] ?? $answer);
                $foundIndex = array_search($selectedText, $originalOptions, true);
                $selectedOriginalIndex = $foundIndex === false ? $selectedIndex : $foundIndex;
            }

            if (is_numeric($correctAnswer)) {
                return $selectedOriginalIndex !== null
                    && (int) $correctAnswer === (int) $selectedOriginalIndex;
            }

            return $this->sameAnswerText($selectedText, $correctAnswer);
        }

        if ($question->type === 'true_false') {
            $submittedBool = $this->booleanAnswerValue($answer);
            $correctBool = $this->booleanAnswerValue($correctAnswer);

            if ($submittedBool !== null && $correctBool !== null) {
                return $submittedBool === $correctBool;
            }
        }

        return $this->sameAnswerText($answer, $correctAnswer);
    }

    private function sameAnswerText(string $a, string $b): bool
    {
        return mb_strtolower(trim($a)) === mb_strtolower(trim($b));
    }

    private function booleanAnswerValue(string $value): ?bool
    {
        $normalized = mb_strtolower(trim($value));

        return match ($normalized) {
            'true', '1', 'yes', 'y', 'dung', 'đúng', 'correct' => true,
            'false', '0', 'no', 'n', 'sai', 'wrong' => false,
            default => null,
        };
    }

    public function result(Request $request, Quiz $quiz)
    {
        $user = $request->user();
        abort_unless($quiz->isAssignedToUser($user), 403);

        $attempt = $user->quizAttempts()
            ->where('quiz_id', $quiz->id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->first();

        abort_unless($attempt, 403);

        $quiz->load(['questions', 'teacher', 'course']);

        return view('pages.student.quiz-result', compact('quiz', 'attempt'));
    }
}
