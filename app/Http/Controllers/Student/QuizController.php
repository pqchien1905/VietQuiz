<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizViolation;
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
                        ->where('public_to_all_students', true);
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

        $attemptsByQuiz = DB::table('quiz_user')
            ->where('user_id', $user->id)
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->orderByDesc('submitted_at')
            ->orderByDesc('started_at')
            ->get()
            ->groupBy('quiz_id');

        $quizzes = $quizzes->map(function (Quiz $quiz) use ($attemptsByQuiz) {
            $quizAttempts = $attemptsByQuiz->get($quiz->id, collect());
            $activeAttempt = $quizAttempts->first(fn ($attempt) => $attempt->started_at !== null && $attempt->submitted_at === null);
            $latestSubmitted = $quizAttempts->first(fn ($attempt) => $attempt->submitted_at !== null);
            $attempt = $activeAttempt ?? $latestSubmitted ?? $quizAttempts->first();
            $submittedAttempts = max((int) ($attempt?->attempt_count ?? 0), $latestSubmitted ? 1 : 0);
            $isUnlimitedAttempts = empty($quiz->max_attempts);
            $maxAttempts = $isUnlimitedAttempts ? null : max(1, (int) $quiz->max_attempts);
            $remainingAttempts = $isUnlimitedAttempts ? null : max(0, $maxAttempts - $submittedAttempts);
            $isSubmitted = $attempt !== null && $submittedAttempts > 0;
            $isStarted = $activeAttempt !== null;
            $isExpired = $quiz->end_at !== null && $quiz->end_at->isPast();
            $isScheduled = $quiz->start_at !== null && $quiz->start_at->isFuture();
            $canRetry = ($isUnlimitedAttempts || (($remainingAttempts ?? 0) > 0)) && ! $isStarted && ! $isExpired && ! $isScheduled;
            $maxScore = (float) ($attempt?->best_total_points ?: $attempt?->total_points ?: $quiz->total_points ?: $quiz->questions_count ?: 0);
            $score = $attempt?->best_score !== null
                ? (float) $attempt->best_score
                : ($attempt?->score !== null ? (float) $attempt->score : null);
            $scorePct = $score !== null && $maxScore > 0 ? round($score / $maxScore * 100) : null;

            if ($isStarted) {
                $learningStatus = 'in_progress';
            } elseif ($isExpired) {
                $learningStatus = 'missed';
            } elseif ($isScheduled) {
                $learningStatus = 'scheduled';
            } elseif ($isSubmitted && ! $canRetry) {
                $learningStatus = 'completed';
            } else {
                $learningStatus = 'available';
            }

            $quiz->attempt = $attempt;
            $quiz->learning_status = $learningStatus;
            $quiz->score_pct = $scorePct;
            $quiz->score_value = $score;
            $quiz->score_max = $maxScore;
            $quiz->started_at_display = $attempt?->started_at ? Carbon::parse($attempt->started_at) : null;
            $quiz->submitted_at_display = $latestSubmitted?->submitted_at ? Carbon::parse($latestSubmitted->submitted_at) : null;
            $quiz->submitted_attempts = $submittedAttempts;
            $quiz->remaining_attempts = $remainingAttempts;
            $quiz->is_unlimited_attempts = $isUnlimitedAttempts;
            $quiz->max_attempts_display = $maxAttempts;
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

        $currentAttemptRow = $user->quizAttempts()
            ->where('quiz_id', $quiz->id)
            ->first();
        $submittedAttempts = max(
            (int) ($currentAttemptRow?->pivot->attempt_count ?? 0),
            $currentAttemptRow?->pivot->submitted_at ? 1 : 0
        );
        if (!empty($quiz->max_attempts) && $submittedAttempts >= (int) $quiz->max_attempts) {
            return redirect()
                ->route('student.quiz-result', $quiz)
                ->with('info', 'Bạn đã dùng hết số lượt làm bài cho phép. Hệ thống đang hiển thị kết quả gần nhất.');
        }

        // Check existing unfinished attempt
        $existingAttempt = $user->quizAttempts()
            ->where('quiz_id', $quiz->id)
            ->whereNull('submitted_at')
            ->first();

        $existingShuffleState = [];
        if ($existingAttempt) {
            $startedAt = Carbon::parse($existingAttempt->pivot->started_at);
            $existingShuffleState = $existingAttempt->pivot->shuffled_options
                ? (json_decode($existingAttempt->pivot->shuffled_options, true) ?: [])
                : [];

            if ($this->attemptDeadline($quiz, $startedAt)?->isPast()) {
                $this->expireAttempt($user, $quiz);

                return redirect()
                    ->route('student.quiz-result', $quiz)
                    ->with('error', 'Thời gian làm bài đã kết thúc. Hệ thống đã tự động đóng lượt làm bài này.');
            }
        } else {
            // Check max attempts only before creating a new attempt. An unfinished
            // attempt must remain resumable even when max_attempts is 1.
            abort_if($quiz->max_attempts && $submittedAttempts >= $quiz->max_attempts, 403);

            $startedAt = now();
            $existingPivotRow = $user->quizAttempts()
                ->where('quiz_id', $quiz->id)
                ->first();

            if ($existingPivotRow) {
                $user->quizAttempts()->updateExistingPivot($quiz->id, [
                    'started_at' => $startedAt,
                    'submitted_at' => null,
                    'is_graded' => false,
                    'shuffled_options' => null,
                ]);
            } else {
                $user->quizAttempts()->attach($quiz->id, ['started_at' => $startedAt]);
            }
        }

        $storedQuestionOrder = collect($existingShuffleState['_question_order'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $query = $quiz->questions()->newQuery();
        if ($storedQuestionOrder->isNotEmpty()) {
            $orderSql = $storedQuestionOrder
                ->map(fn ($id, $index) => 'WHEN '.$id.' THEN '.$index)
                ->implode(' ');

            $query->whereIn('id', $storedQuestionOrder)
                ->orderByRaw('CASE id '.$orderSql.' ELSE '.($storedQuestionOrder->count() + 1).' END');
        } elseif ($quiz->shuffle_questions) {
            $query->inRandomOrder();
        } else {
            $query->orderBy('order');
        }
        $questions = $query->limit(50)->get();

        // Shuffle answer options if enabled
        $shuffledOrder = [];
        foreach ($questions as $question) {
            $options = $this->normalizeOptions($question->options);

            $storedQuestionShuffle = $existingShuffleState[$question->id] ?? $existingShuffleState[(string) $question->id] ?? null;
            if (is_array($storedQuestionShuffle) && isset($storedQuestionShuffle['shuffled'])) {
                $question->shuffled_options = $storedQuestionShuffle['shuffled'];
                $shuffledOrder[$question->id] = $storedQuestionShuffle;
            } elseif ($quiz->shuffle_answers && $question->options) {
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

        if ($storedQuestionOrder->isEmpty() && $quiz->shuffle_questions) {
            $shuffledOrder['_question_order'] = $questions->pluck('id')->values()->all();
        } elseif (isset($existingShuffleState['_question_order'])) {
            $shuffledOrder['_question_order'] = $existingShuffleState['_question_order'];
        }

        if (! empty($shuffledOrder) && $shuffledOrder !== $existingShuffleState) {
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

            $submittedCount = max(
                (int) ($submittedAttempt?->pivot->attempt_count ?? 0),
                $submittedAttempt?->pivot->submitted_at ? 1 : 0
            );

            if ($submittedAttempt && $quiz->max_attempts && $submittedCount >= $quiz->max_attempts) {
                return response()->json([
                    'success' => true,
                    'already_submitted' => true,
                    'redirect_url' => route('student.quiz-result', $quiz),
                ]);
            }

            $attemptCount = $submittedCount;
            if ($quiz->max_attempts && $attemptCount >= $quiz->max_attempts) {
                return response()->json([
                    'error' => 'Bạn đã hết số lượt làm bài cho phép.',
                ], 403);
            }

            $startedAt = now();
            $existingPivotRow = $user->quizAttempts()
                ->where('quiz_id', $quiz->id)
                ->first();

            if ($existingPivotRow) {
                $user->quizAttempts()->updateExistingPivot($quiz->id, [
                    'started_at' => $startedAt,
                    'submitted_at' => null,
                    'is_graded' => false,
                    'shuffled_options' => null,
                ]);
            } else {
                $user->quizAttempts()->attach($quiz->id, ['started_at' => $startedAt]);
            }

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

        if ($this->attemptDeadline($quiz, Carbon::parse($attempt->pivot->started_at))?->isPast()) {
            $this->expireAttempt($user, $quiz);

            return response()->json([
                'error' => 'Thời gian làm bài đã kết thúc. Bài làm không được ghi nhận sau thời hạn.',
                'time_expired' => true,
                'redirect_url' => route('student.quiz-result', $quiz),
            ], 403);
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

        $currentPct = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
        $previousBestScore = is_numeric($attempt->pivot->best_score ?? null)
            ? (float) $attempt->pivot->best_score
            : (is_numeric($attempt->pivot->score) ? (float) $attempt->pivot->score : null);
        $previousBestTotal = is_numeric($attempt->pivot->best_total_points ?? null)
            ? (float) $attempt->pivot->best_total_points
            : (is_numeric($attempt->pivot->total_points) ? (float) $attempt->pivot->total_points : null);
        $previousBestPct = $previousBestScore !== null && $previousBestTotal > 0
            ? ($previousBestScore / $previousBestTotal) * 100
            : null;
        $isBestAttempt = $previousBestPct === null || $currentPct >= $previousBestPct;
        $nextAttemptCount = ((int) ($attempt->pivot->attempt_count ?? 0)) + 1;

        $user->quizAttempts()->updateExistingPivot($quiz->id, [
            'answers' => json_encode($validated['answers']),
            'score' => $earnedPoints,
            'total_points' => $totalPoints,
            'best_score' => $isBestAttempt ? $earnedPoints : $previousBestScore,
            'best_total_points' => $isBestAttempt ? $totalPoints : $previousBestTotal,
            'submitted_at' => now(),
            'is_graded' => true,
            'attempt_count' => $nextAttemptCount,
        ]);

        $pct = round($currentPct);
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

    public function logViolation(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'event_type' => 'required|string|in:tab_hidden,focus_lost,fullscreen_exit,copy,cut,paste,context_menu,blocked_shortcut,devtools_detected,screenshot_attempt',
            'metadata' => 'nullable|array',
        ]);

        $user = $request->user();

        abort_unless($quiz->isAssignedToUser($user), 403);

        if ($quiz->status !== 'published' || $quiz->quiz_type !== 'exam' || ! $quiz->anti_cheat_enabled) {
            return response()->json(['logged' => false]);
        }

        $attemptRow = DB::table('quiz_user')
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->whereNull('submitted_at')
            ->first();

        if (! $attemptRow) {
            return response()->json([
                'logged' => false,
                'already_submitted' => DB::table('quiz_user')
                    ->where('quiz_id', $quiz->id)
                    ->where('user_id', $user->id)
                    ->whereNotNull('submitted_at')
                    ->exists(),
            ], 409);
        }

        if ($this->attemptDeadline($quiz, Carbon::parse($attemptRow->started_at))?->isPast()) {
            $this->expireAttempt($user, $quiz);

            return response()->json([
                'logged' => false,
                'time_expired' => true,
                'should_redirect' => true,
                'redirect_url' => route('student.quiz-result', $quiz),
            ], 403);
        }

        $attemptStartedAt = Carbon::parse($attemptRow->started_at);
        $forceCloseAttempt = (bool) data_get($validated, 'metadata.return_timeout', false);
        if ($forceCloseAttempt) {
            QuizViolation::create([
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'quiz_attempt_id' => $attemptRow->id,
                'event_type' => $validated['event_type'],
                'metadata' => $validated['metadata'] ?? null,
                'occurred_at' => now(),
            ]);

            $this->expireAttempt($user, $quiz);

            return response()->json([
                'logged' => true,
                'violation_count' => 1,
                'max_violations' => 1,
                'should_auto_submit' => true,
                'redirect_url' => route('student.quiz-result', $quiz),
            ]);
        }

        $nonScoringEvents = ['copy', 'cut', 'context_menu', 'blocked_shortcut', 'fullscreen_exit', 'screenshot_attempt'];
        if (in_array($validated['event_type'], $nonScoringEvents, true)) {
            return response()->json([
                'logged' => false,
                'ignored' => true,
                'violation_count' => QuizViolation::where('quiz_id', $quiz->id)
                    ->where('user_id', $user->id)
                    ->where('quiz_attempt_id', $attemptRow->id)
                    ->whereIn('event_type', ['tab_hidden', 'focus_lost'])
                    ->where('occurred_at', '>=', $attemptStartedAt)
                    ->count(),
                'max_violations' => 2,
                'should_auto_submit' => false,
                'redirect_url' => route('student.quiz-result', $quiz),
            ]);
        }

        $attentionEvents = ['tab_hidden', 'focus_lost'];
        $isAttentionEvent = in_array($validated['event_type'], $attentionEvents, true);
        $duplicateRecentAttentionEvent = $isAttentionEvent
            && QuizViolation::where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->where('quiz_attempt_id', $attemptRow->id)
                ->whereIn('event_type', $attentionEvents)
                ->where('occurred_at', '>=', $attemptStartedAt)
                ->where('occurred_at', '>=', now()->subSeconds(10))
                ->exists();

        if ($duplicateRecentAttentionEvent) {
            $violationCount = QuizViolation::where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->where('quiz_attempt_id', $attemptRow->id)
                ->when($isAttentionEvent, fn ($query) => $query->whereIn('event_type', $attentionEvents))
                ->where('occurred_at', '>=', $attemptStartedAt)
                ->count();

            return response()->json([
                'logged' => false,
                'duplicate' => true,
                'violation_count' => $violationCount,
                'max_violations' => $isAttentionEvent ? 2 : $this->maxAntiCheatViolations(),
                'should_auto_submit' => false,
                'redirect_url' => route('student.quiz-result', $quiz),
            ]);
        }

        QuizViolation::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'quiz_attempt_id' => $attemptRow->id,
            'event_type' => $validated['event_type'],
            'metadata' => $validated['metadata'] ?? null,
            'occurred_at' => now(),
        ]);

        $violationCountQuery = QuizViolation::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('quiz_attempt_id', $attemptRow->id)
            ->where('occurred_at', '>=', $attemptStartedAt);

        if ($isAttentionEvent) {
            $violationCountQuery->whereIn('event_type', $attentionEvents);
        }

        $violationCount = $violationCountQuery->count();
        $maxViolations = $isAttentionEvent ? 2 : $this->maxAntiCheatViolations();
        $shouldAutoSubmit = $violationCount >= $maxViolations;
        if ($shouldAutoSubmit) {
            $this->expireAttempt($user, $quiz);
        }

        return response()->json([
            'logged' => true,
            'violation_count' => $violationCount,
            'max_violations' => $maxViolations,
            'should_auto_submit' => $shouldAutoSubmit,
            'redirect_url' => route('student.quiz-result', $quiz),
        ]);
    }

    private function maxAntiCheatViolations(): int
    {
        return max(1, (int) config('vietquiz.anti_cheat.max_violations', 3));
    }

    private function attemptDeadline(Quiz $quiz, Carbon $startedAt): ?Carbon
    {
        $limit = $quiz->time_limit ?? $quiz->duration_minutes;

        return $limit ? $startedAt->copy()->addMinutes((int) $limit) : null;
    }

    private function expireAttempt($user, Quiz $quiz): void
    {
        $totalPoints = $quiz->questions()->get(['points'])->sum(fn ($question) => $question->points ?? 1);
        $attemptRow = DB::table('quiz_user')
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->whereNull('submitted_at')
            ->first();

        if (! $attemptRow) {
            return;
        }

        DB::table('quiz_user')
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->whereNull('submitted_at')
            ->update([
                'answers' => json_encode([]),
                'score' => 0,
                'total_points' => $totalPoints,
                'best_score' => is_numeric($attemptRow->best_score ?? null)
                    ? $attemptRow->best_score
                    : (is_numeric($attemptRow->score ?? null) ? $attemptRow->score : 0),
                'best_total_points' => is_numeric($attemptRow->best_total_points ?? null)
                    ? $attemptRow->best_total_points
                    : (is_numeric($attemptRow->total_points ?? null) ? $attemptRow->total_points : $totalPoints),
                'submitted_at' => now(),
                'is_graded' => true,
                'attempt_count' => ((int) ($attemptRow->attempt_count ?? 0)) + 1,
                'updated_at' => now(),
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
