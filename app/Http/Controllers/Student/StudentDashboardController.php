<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Stats
        $courseCount = $user->courses()->count();
        $classCount = $user->classes()->count();

        // Pending quizzes (assigned but not taken)
        $pendingQuizCount = $user->classes()
            ->with('assignments.quiz')
            ->get()
            ->pluck('assignments')
            ->flatten()
            ->pluck('quiz_id')
            ->unique()
            ->diff(
                $user->quizAttempts()->pluck('quizzes.id')
            )
            ->count();

        // Assignments due soon
        $dueSoonAssignments = $user->classes()
            ->with(['assignments' => fn($q) => $q->where('due_at', '>=', now())->orderBy('due_at')])
            ->get()
            ->pluck('assignments')
            ->flatten()
            ->take(5);

        // Average grade
        $avgGrade = $user->quizAttempts()
            ->wherePivot('is_graded', true)
            ->get()
            ->avg(fn($q) => $q->pivot->total_points > 0 ? ($q->pivot->score / $q->pivot->total_points) * 100 : 0);

        // Recent activity
        $recentAttempts = $user->quizAttempts()
            ->latest('quiz_user.submitted_at')
            ->take(5)
            ->get();

        // Notifications
        $recentNotifications = Notification::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('pages.student.dashboard', compact(
            'user', 'courseCount', 'classCount', 'pendingQuizCount',
            'dueSoonAssignments', 'avgGrade', 'recentAttempts', 'recentNotifications'
        ));
    }
}
