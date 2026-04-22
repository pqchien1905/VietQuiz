<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $grades = Grade::where('student_id', $user->id)
            ->with('grader')
            ->orderByDesc('graded_at')
            ->get()
            ->map(function ($grade) {
                $grade->item = $grade->gradable;
                return $grade;
            });

        $avgGrade = $grades->avg('score');

        $gradedCount = $grades->whereNotNull('graded_at')->count();
        $pendingCount = $grades->whereNull('graded_at')->count();

        return view('pages.student.grades', compact(
            'grades', 'avgGrade', 'gradedCount', 'pendingCount'
        ));
    }
}
