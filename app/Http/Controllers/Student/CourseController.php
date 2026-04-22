<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\Assignment;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $courses = $user->courses()
            ->with(['teacher', 'classModel'])
            ->withCount(['quizzes', 'assignments'])
            ->get()
            ->map(function ($course) use ($user) {
                $course->avg_grade = \DB::table('grades')
                    ->where('student_id', $user->id)
                    ->where('gradable_type', 'course')
                    ->where('gradable_id', $course->id)
                    ->avg('score');
                return $course;
            });

        return view('pages.student.courses', compact('courses'));
    }

    public function show(Request $request, Course $course)
    {
        abort_unless(
            $request->user()->courses()->where('courses.id', $course->id)->exists(),
            403
        );

        $user = $request->user();

        $course->load(['teacher', 'classModel', 'quizzes' => fn($q) => $q->orderByDesc('created_at')], 'assignments');

        $completedQuizIds = $user->quizAttempts()
            ->whereIn('quiz_id', $course->quizzes->pluck('id'))
            ->whereNotNull('submitted_at')
            ->pluck('quiz_id')
            ->toArray();

        $submittedAssignmentIds = $user->submissions()
            ->whereIn('assignment_id', $course->assignments->pluck('id'))
            ->pluck('assignment_id')
            ->toArray();

        $quizGrades = \DB::table('quiz_user')
            ->whereIn('quiz_id', $course->quizzes->pluck('id'))
            ->where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->get();

        $assignmentGrades = $user->submissions()
            ->whereIn('assignment_id', $course->assignments->pluck('id'))
            ->with('grades')
            ->get();

        $totalItems = $course->quizzes->count() + $course->assignments->count();
        $completedItems = count($completedQuizIds) + count($submittedAssignmentIds);
        $completionPct = $totalItems > 0 ? round($completedItems / $totalItems * 100) : 0;

        $totalEarned = $quizGrades->sum('score');
        $totalPossible = $quizGrades->sum('total_points');
        foreach ($assignmentGrades as $sub) {
            if ($sub->grades->first()) {
                $totalEarned += $sub->grades->first()->score;
                $totalPossible += $sub->grades->first()->score > 0 ? $totalPossible : 0;
            }
        }
        $avgGrade = $totalPossible > 0 ? round($totalEarned / $totalPossible * 100, 1) : null;

        return view('pages.student.course-detail', compact(
            'course', 'completedQuizIds', 'submittedAssignmentIds', 'completionPct', 'avgGrade'
        ));
    }
}
