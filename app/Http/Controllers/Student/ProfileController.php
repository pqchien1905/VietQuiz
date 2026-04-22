<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $courseCount = $user->courses()->count();
        $quizCount = $user->quizAttempts()->whereNotNull('submitted_at')->count();
        $assignmentCount = $user->submissions()->count();
        $avgGrade = \DB::table('grades')
            ->where('student_id', $user->id)
            ->avg('score');

        return view('pages.student.profile', compact(
            'user', 'courseCount', 'quizCount',
            'assignmentCount', 'avgGrade'
        ));
    }
}
