<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Notification;
use Illuminate\Http\Request;

class TeacherDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Stats
        $classCount     = $user->createdClasses()->count();
        $quizCount      = $user->quizzes()->count();
        $questionCount  = $user->questions()->count();
        $studentCount   = $user->createdClasses()
            ->withCount('students')
            ->get()
            ->sum('students_count');

        // Ungraded submissions
        $quizIds = $user->quizzes()->pluck('id');
        $ungradedCount = \DB::table('quiz_user')
            ->whereIn('quiz_id', $quizIds)
            ->where('is_graded', false)
            ->whereNotNull('submitted_at')
            ->count();

        // Recent assignments
        $recentAssignments = Assignment::where('teacher_id', $user->id)
            ->orderBy('due_at')
            ->where('due_at', '>=', now())
            ->take(5)
            ->get();

        // Recent notifications
        $recentNotifications = Notification::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // Weekly activity
        $weekDays = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        $activityData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $activityData[] = \DB::table('quiz_user')
                ->whereIn('quiz_id', $quizIds)
                ->whereDate('started_at', $date)
                ->count();
        }

        return view('pages.teacher.dashboard', compact(
            'user', 'classCount', 'quizCount', 'questionCount', 'studentCount',
            'ungradedCount', 'recentAssignments', 'recentNotifications',
            'weekDays', 'activityData'
        ));
    }
}
