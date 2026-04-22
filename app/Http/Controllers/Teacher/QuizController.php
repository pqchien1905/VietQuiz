<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $quizzes = $user->quizzes()
            ->withCount('questions')
            ->withCount(['attempts as attempts_count'])
            ->latest()
            ->get();

        $courses = $user->createdCourses()->get();

        return view('pages.teacher.quizzes', compact('quizzes', 'courses'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $courses = $user->createdCourses()->get();

        return view('pages.teacher.quiz-create', compact('courses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string|max:2000',
            'course_id'       => 'nullable|exists:courses,id',
            'class_id'        => 'nullable|exists:classes,id',
            'time_limit'      => 'nullable|integer|min:1',
            'max_attempts'    => 'nullable|integer|min:1',
            'passing_score'   => 'nullable|integer|min:0|max:100',
            'is_shuffle'      => 'boolean',
            'is_published'    => 'boolean',
            'questions'       => 'required|array|min:1',
            'questions.*.content'       => 'required|string',
            'questions.*.type'         => 'required|in:multiple_choice,true_false,short_answer',
            'questions.*.options'      => 'nullable|array',
            'questions.*.correct_answer' => 'required|string',
            'questions.*.points'       => 'nullable|integer|min:1',
            'questions.*.explanation'  => 'nullable|string',
        ]);

        $quiz = Quiz::create([
            'teacher_id'    => $request->user()->id,
            'course_id'     => $validated['course_id'] ?? null,
            'class_id'     => $validated['class_id'] ?? null,
            'title'         => $validated['title'],
            'description'   => $validated['description'] ?? null,
            'time_limit'    => $validated['time_limit'] ?? null,
            'max_attempts'  => $validated['max_attempts'] ?? 1,
            'passing_score' => $validated['passing_score'] ?? 50,
            'is_shuffle'   => $validated['is_shuffle'] ?? false,
            'status'        => ($validated['is_published'] ?? false) ? 'published' : 'draft',
        ]);

        foreach ($validated['questions'] as $i => $qData) {
            Question::create([
                'quiz_id'     => $quiz->id,
                'teacher_id'  => $request->user()->id,
                'type'        => $qData['type'],
                'content'     => $qData['content'],
                'options'     => isset($qData['options']) ? json_encode($qData['options']) : null,
                'correct_answer' => $qData['correct_answer'],
                'points'      => $qData['points'] ?? 1,
                'explanation' => $qData['explanation'] ?? null,
                'order'       => $i + 1,
            ]);
        }

        return redirect()->route('teacher.quiz-detail', $quiz)
            ->with('success', 'Tạo bài kiểm tra thành công!');
    }

    public function show(Request $request, Quiz $quiz)
    {
        $this->authorizeTeacher($request, $quiz);

        $quiz->load(['questions', 'course', 'attempts' => fn($q) => $q->latest('quiz_user.submitted_at')->take(20)]);
        $quiz->loadCount(['questions', 'attempts']);

        $avgScore = $quiz->attempts->avg(fn($a) => $a->pivot->total_points > 0
            ? round(($a->pivot->score / $a->pivot->total_points) * 100)
            : 0);

        return view('pages.teacher.quiz-detail', compact('quiz', 'avgScore'));
    }

    public function update(Request $request, Quiz $quiz)
    {
        $this->authorizeTeacher($request, $quiz);

        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string|max:2000',
            'course_id'     => 'nullable|exists:courses,id',
            'time_limit'    => 'nullable|integer|min:1',
            'max_attempts'  => 'nullable|integer|min:1',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'is_shuffle'    => 'boolean',
            'status'        => 'in:draft,published,archived',
        ]);

        $quiz->update($validated);

        return redirect()->route('teacher.quiz-detail', $quiz)
            ->with('success', 'Cập nhật bài kiểm tra thành công!');
    }

    public function destroy(Request $request, Quiz $quiz)
    {
        $this->authorizeTeacher($request, $quiz);
        $quiz->delete();

        return redirect()->route('teacher.quizzes')
            ->with('success', 'Đã xóa bài kiểm tra!');
    }

    private function authorizeTeacher(Request $request, Quiz $quiz): void
    {
        abort_unless($quiz->teacher_id === $request->user()->id, 403);
    }
}
