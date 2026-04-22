<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $courses = $user->courses()
            ->with('teacher')
            ->withCount(['quizzes', 'assignments', 'students'])
            ->get();

        return response()->json(['courses' => $courses]);
    }

    public function show(Request $request, Course $course)
    {
        abort_unless(
            $request->user()->courses()->where('courses.id', $course->id)->exists(),
            403
        );

        $course->load(['teacher', 'quizzes', 'assignments', 'students']);

        return response()->json(['course' => $course]);
    }
}
