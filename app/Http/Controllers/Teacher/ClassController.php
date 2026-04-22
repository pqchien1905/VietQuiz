<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $classes = $user->createdClasses()
            ->withCount('students')
            ->with(['assignments', 'courses'])
            ->latest()
            ->get();

        return view('pages.teacher.classes', compact('classes'));
    }

    public function show(Request $request, ClassModel $class)
    {
        $this->authorizeTeacher($request, $class);

        $class->load(['students', 'assignments.quiz']);

        $studentCount = $class->students()->count();

        // Per-student grade data
        $studentGrades = $class->students()
            ->with(['quizAttempts' => fn($q) => $q->wherePivot('submitted_at', '!=', null)])
            ->get()
            ->map(function ($student) {
                $attempts = $student->quizAttempts;
                $scores = $attempts
                    ->filter(fn($a) => $a->pivot->total_points > 0)
                    ->map(fn($a) => ($a->pivot->score / $a->pivot->total_points) * 100);

                $student->avg_pct = $scores->count() > 0 ? round($scores->avg(), 1) : null;
                $student->completed_count = $attempts->filter(fn($a) => $a->pivot->submitted_at)->count();
                $student->grade_letter = $this->pctToGrade($student->avg_pct);
                $student->last_attempt = $attempts->max('pivot.submitted_at');
                return $student;
            });

        // Quiz data for this class
        $quizzes = $class->quizzes()
            ->withCount(['attempts as submitted_count' => fn($q) => $q->whereNotNull('submitted_at')])
            ->withCount('questions')
            ->latest()
            ->get()
            ->map(function ($quiz) {
                $quiz->avg_score = \DB::table('quiz_user')
                    ->where('quiz_id', $quiz->id)
                    ->whereNotNull('submitted_at')
                    ->avg(\DB::raw('(score / NULLIF(total_points, 0)) * 100'));
                $quiz->avg_score = $quiz->avg_score ? round($quiz->avg_score, 1) : null;
                return $quiz;
            });

        // Class stats
        $classAvg = $studentGrades->where('avg_pct', '!=', null)->avg('avg_pct');
        $totalAssigned = $class->assignments->count();
        $submittedAssignments = \DB::table('submissions')
            ->whereIn('assignment_id', $class->assignments->pluck('id'))
            ->count();
        $completionRate = $studentCount > 0 && $totalAssigned > 0
            ? round($submittedAssignments / ($studentCount * max($totalAssigned, 1)) * 100)
            : 0;

        // Top/weak students
        $sorted = $studentGrades->filter(fn($s) => $s->avg_pct !== null)->sortByDesc('avg_pct');
        $topStudents = $sorted->take(5)->values();
        $weakStudents = $sorted->filter(fn($s) => $s->avg_pct !== null && $s->avg_pct < 60)->take(5)->values();

        // Grade distribution
        $dist = [
            'excellent' => $studentGrades->filter(fn($s) => $s->avg_pct >= 90)->count(),
            'good' => $studentGrades->filter(fn($s) => $s->avg_pct >= 70 && $s->avg_pct < 90)->count(),
            'average' => $studentGrades->filter(fn($s) => $s->avg_pct >= 50 && $s->avg_pct < 70)->count(),
            'weak' => $studentGrades->filter(fn($s) => $s->avg_pct !== null && $s->avg_pct < 50)->count(),
        ];

        return view('pages.teacher.class-detail', compact(
            'class', 'studentCount', 'studentGrades',
            'quizzes', 'classAvg', 'completionRate',
            'topStudents', 'weakStudents', 'dist'
        ));
    }

    private function pctToGrade(?float $pct): string
    {
        if ($pct === null) return '—';
        if ($pct >= 90) return 'A';
        if ($pct >= 80) return 'B';
        if ($pct >= 70) return 'C';
        if ($pct >= 60) return 'D';
        return 'F';
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'subject'     => 'nullable|string|max:255',
            'grade_level' => 'nullable|string|max:50',
        ]);

        $validated['teacher_id'] = $request->user()->id;
        $validated['code'] = strtoupper(Str::random(6));

        ClassModel::create($validated);

        return redirect()->route('teacher.classes')
            ->with('success', 'Tạo lớp thành công! Mã lớp: ' . $validated['code']);
    }

    public function update(Request $request, ClassModel $class)
    {
        $this->authorizeTeacher($request, $class);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'subject'     => 'nullable|string|max:255',
            'grade_level' => 'nullable|string|max:50',
        ]);

        $class->update($validated);

        return redirect()->route('teacher.class-detail', $class)
            ->with('success', 'Cập nhật lớp thành công!');
    }

    public function destroy(Request $request, ClassModel $class)
    {
        $this->authorizeTeacher($request, $class);

        $class->delete();

        return redirect()->route('teacher.classes')
            ->with('success', 'Đã xóa lớp thành công!');
    }

    public function removeStudent(Request $request, ClassModel $class, $studentId)
    {
        $this->authorizeTeacher($request, $class);
        $class->students()->detach($studentId);

        return redirect()->route('teacher.class-detail', $class)
            ->with('success', 'Đã xóa học sinh khỏi lớp!');
    }

    private function authorizeTeacher(Request $request, ClassModel $class): void
    {
        abort_unless($class->teacher_id === $request->user()->id, 403);
    }
}
