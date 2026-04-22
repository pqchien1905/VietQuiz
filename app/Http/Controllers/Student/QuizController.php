<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $courseIds = $user->courses()->pluck('courses.id');
        $classIds = $user->classes()->pluck('classes.id');

        // Quizzes assigned to student's classes or courses
        $upcoming = Quiz::where('status', 'published')
            ->where(function ($q) use ($classIds, $courseIds) {
                $q->whereIn('class_id', $classIds)
                  ->orWhereIn('course_id', $courseIds);
            })
            ->where(function ($q) {
                $q->whereNull('end_at')
                  ->orWhere('end_at', '>=', now());
            })
            ->where(function ($q) use ($user) {
                $q->whereDoesntHave('attempts', fn($a) => $a->where('user_id', $user->id)->whereNotNull('submitted_at'));
            })
            ->orWhere(fn($q) => $q->whereHas('attempts', fn($a) => $a->where('user_id', $user->id)->whereColumn('quiz_user.submitted_at', '>', 'quiz_user.start_at')))
            ->with('teacher', 'course', 'classModel')
            ->get()
            ->filter(fn($q) => !$user->quizAttempts()->where('quiz_id', $q->id)->whereNotNull('submitted_at')->exists());

        $completed = $user->quizAttempts()
            ->whereNotNull('submitted_at')
            ->with('teacher', 'course')
            ->orderByDesc('quiz_user.submitted_at')
            ->get();

        $missed = Quiz::where('status', 'published')
            ->whereIn('class_id', $classIds)
            ->where('end_at', '<', now())
            ->whereDoesntHave('attempts', fn($a) => $a->where('user_id', $user->id))
            ->with('teacher', 'classModel')
            ->get();

        return view('pages.student.quizzes', compact('upcoming', 'completed', 'missed'));
    }

    public function take(Request $request, Quiz $quiz)
    {
        $user = $request->user();

        abort_unless(
            $user->classes()->where('classes.id', $quiz->class_id)->exists()
            || $user->courses()->where('courses.id', $quiz->course_id)->exists(),
            403
        );

        abort_if($quiz->status !== 'published', 403);
        abort_if($quiz->end_at && $quiz->end_at->isPast(), 403);

        // Check max attempts
        $attempts = $user->quizAttempts()->where('quiz_id', $quiz->id)->count();
        abort_if($quiz->max_attempts && $attempts >= $quiz->max_attempts, 403);

        // Check existing unfinished attempt
        $existingAttempt = $user->quizAttempts()
            ->where('quiz_id', $quiz->id)
            ->whereNull('submitted_at')
            ->first();

        if ($existingAttempt) {
            $startedAt = $existingAttempt->pivot->started_at;
        } else {
            $startedAt = now();
            $user->quizAttempts()->attach($quiz->id, ['started_at' => $startedAt]);
        }

        $quiz->load(['questions' => fn($q) => $q->inRandomOrder()->limit(50)]);

        return view('pages.student.quiz-take', compact('quiz', 'startedAt'));
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'answers' => 'required|array',
            'answers.*' => 'nullable|string',
        ]);

        $user = $request->user();
        $attempt = $user->quizAttempts()
            ->where('quiz_id', $quiz->id)
            ->whereNull('submitted_at')
            ->first();

        if (!$attempt) {
            return response()->json(['error' => 'No active attempt found'], 400);
        }

        $questions = $quiz->questions;
        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($questions as $question) {
            $totalPoints += $question->points ?? 1;
            $userAnswer = $validated['answers'][$question->id] ?? null;

            if ($userAnswer && $question->isCorrect($userAnswer)) {
                $earnedPoints += $question->points ?? 1;
            }
        }

        $user->quizAttempts()->updateExistingPivot($quiz->id, [
            'answers'       => json_encode($validated['answers']),
            'score'        => $earnedPoints,
            'total_points' => $totalPoints,
            'submitted_at' => now(),
            'is_graded'   => true,
        ]);

        $pct = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100) : 0;
        $passed = $pct >= ($quiz->passing_score ?? 50);

        return response()->json([
            'success'     => true,
            'score'       => $earnedPoints,
            'total'       => $totalPoints,
            'percent'     => $pct,
            'passed'      => $passed,
        ]);
    }

    public function result(Request $request, Quiz $quiz)
    {
        $attempt = $request->user()
            ->quizAttempts()
            ->where('quiz_id', $quiz->id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->first();

        abort_unless($attempt, 403);

        $quiz->load(['questions', 'teacher', 'course']);

        return view('pages.student.quiz-result', compact('quiz', 'attempt'));
    }
}
