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

        $course->load(['teacher', 'classModel', 'quizzes', 'assignments']);

        $completedQuizzes = $request->user()
            ->quizAttempts()
            ->where('quizzes.id', $course->id)
            ->count();

        $completedAssignments = $request->user()
            ->submissions()
            ->whereIn('assignment_id', $course->assignments->pluck('id'))
            ->count();

        return view('pages.student.course-detail', compact(
            'course', 'completedQuizzes', 'completedAssignments'
        ));
    }
}
