<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $quizzes = Quiz::published()
            ->where(function ($q) use ($user) {
                $q->whereIn('class_id', $user->classes()->pluck('classes.id'))
                  ->orWhereIn('course_id', $user->courses()->pluck('courses.id'));
            })
            ->with('teacher', 'course')
            ->get()
            ->map(function ($quiz) use ($user) {
                $attempt = $user->quizAttempts()->where('quiz_id', $quiz->id)->first();
                $quiz->attempt = $attempt ? [
                    'started_at'   => $attempt->pivot->started_at,
                    'submitted_at' => $attempt->pivot->submitted_at,
                    'score'       => $attempt->pivot->score,
                    'total_points'=> $attempt->pivot->total_points,
                ] : null;
                return $quiz;
            });

        return response()->json(['quizzes' => $quizzes]);
    }

    public function show(Request $request, Quiz $quiz)
    {
        abort_unless(
            $request->user()->classes()->where('classes.id', $quiz->class_id)->exists()
            || $request->user()->courses()->where('courses.id', $quiz->course_id)->exists(),
            403
        );

        $quiz->load('teacher', 'course');

        return response()->json([
            'quiz' => $quiz,
            'questions_count' => $quiz->questions()->count(),
        ]);
    }

    public function start(Request $request, Quiz $quiz)
    {
        $user = $request->user();

        if ($quiz->max_attempts) {
            $attempts = $user->quizAttempts()->where('quiz_id', $quiz->id)->count();
            abort_if($attempts >= $quiz->max_attempts, 403, 'Đã hết số lần làm bài.');
        }

        $user->quizAttempts()->attach($quiz->id, ['started_at' => now()]);

        $questions = $quiz->questions
            ->shuffle()
            ->map(fn($q) => [
                'id'      => $q->id,
                'type'    => $q->type,
                'content' => $q->content,
                'options' => $q->options,
                'points'  => $q->points,
            ]);

        return response()->json([
            'started_at'  => now()->toIso8601String(),
            'questions'    => $questions,
            'duration'    => $quiz->time_limit,
        ]);
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'answers'   => 'required|array',
            'answers.*' => 'nullable|string',
        ]);

        $user = $request->user();
        $attempt = $user->quizAttempts()
            ->where('quiz_id', $quiz->id)
            ->whereNull('submitted_at')
            ->first();

        abort_unless($attempt, 400, 'Không tìm thấy lần làm bài.');

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
            'answers'      => json_encode($validated['answers']),
            'score'       => $earnedPoints,
            'total_points' => $totalPoints,
            'submitted_at' => now(),
            'is_graded'   => true,
        ]);

        $pct = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100) : 0;

        return response()->json([
            'score'  => $earnedPoints,
            'total'  => $totalPoints,
            'percent'=> $pct,
        ]);
    }
}
