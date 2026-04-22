<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $classCount = $user->createdClasses()->count();
        $quizCount = $user->quizzes()->count();
        $assignmentCount = $user->assignments()->count();
        $studentCount = $user->createdClasses()->withCount('students')->get()->sum('students_count');

        $avgScore = \DB::table('grades')
            ->where('grader_id', $user->id)
            ->avg('score');

        return view('pages.teacher.profile', compact(
            'user', 'classCount', 'quizCount',
            'assignmentCount', 'studentCount', 'avgScore'
        ));
    }
}
