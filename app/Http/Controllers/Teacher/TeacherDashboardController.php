<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Quiz;
use App\Models\Notification;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $classIds = $user->createdClasses()->pluck('id');
        $quizIds = $user->quizzes()->pluck('id');

        // Stats
        $classCount = $classIds->count();
        $courseCount = $user->createdCourses()->count();
        $quizCount = $quizIds->count();
        $publishedQuizCount = $user->quizzes()->where('status', 'published')->count();
        $draftQuizCount = $user->quizzes()->where('status', 'draft')->count();
        $questionCount = $user->questions()->count();
        $assignmentCount = $user->assignments()->count();
        $studentCount = DB::table('class_user')
            ->whereIn('class_id', $classIds)
            ->distinct('user_id')
            ->count('user_id');

        // Ungraded submissions
        $ungradedCount = DB::table('quiz_user')
            ->whereIn('quiz_id', $quizIds)
            ->where('is_graded', false)
            ->whereNotNull('submitted_at')
            ->count();
        $ungradedAssignmentCount = Submission::whereHas('assignment', fn ($query) => $query->where('teacher_id', $user->id))
            ->whereDoesntHave('grades')
            ->count();
        $pendingGradingCount = $ungradedCount + $ungradedAssignmentCount;

        $attemptSummary = DB::table('quiz_user')
            ->whereIn('quiz_id', $quizIds)
            ->selectRaw('COUNT(*) as total_attempts')
            ->selectRaw('SUM(CASE WHEN submitted_at IS NOT NULL THEN 1 ELSE 0 END) as submitted_attempts')
            ->selectRaw('AVG(CASE WHEN submitted_at IS NOT NULL AND total_points > 0 AND score IS NOT NULL THEN (score / total_points) * 100 ELSE NULL END) as average_score')
            ->first();

        $totalAttempts = (int) ($attemptSummary->total_attempts ?? 0);
        $submittedAttempts = (int) ($attemptSummary->submitted_attempts ?? 0);
        $completionRate = $totalAttempts > 0 ? round(($submittedAttempts / $totalAttempts) * 100) : null;
        $averageScore = $attemptSummary?->average_score !== null ? round((float) $attemptSummary->average_score, 1) : null;

        // Recent assignments
        $recentAssignments = Assignment::where('teacher_id', $user->id)
            ->with(['class:id,name', 'course:id,name'])
            ->withCount('submissions')
            ->orderBy('due_at')
            ->where('due_at', '>=', now())
            ->take(5)
            ->get();

        $recentClasses = $user->createdClasses()
            ->withCount(['students', 'quizzes', 'assignments'])
            ->latest()
            ->take(4)
            ->get();

        $recentQuizzes = $user->quizzes()
            ->with(['classModel:id,name', 'course:id,name'])
            ->withCount(['questions', 'attempts'])
            ->latest()
            ->take(5)
            ->get();

        $recentSubmissions = Submission::with([
                'student:id,name,email',
                'assignment:id,teacher_id,class_id,course_id,title,total_points,due_at',
                'assignment.class:id,name',
                'assignment.course:id,name',
                'grades' => fn ($query) => $query->latest('graded_at'),
            ])
            ->whereHas('assignment', fn ($query) => $query->where('teacher_id', $user->id))
            ->latest('submitted_at')
            ->take(5)
            ->get();

        $upcomingQuizzes = Quiz::where('teacher_id', $user->id)
            ->where('status', 'published')
            ->whereNotNull('end_at')
            ->where('end_at', '>=', now())
            ->orderBy('end_at')
            ->with(['classModel:id,name', 'course:id,name'])
            ->take(5)
            ->get();

        // Recent notifications
        $recentNotifications = Notification::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        $classesForForms = $user->createdClasses()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
        $coursesForForms = $user->createdCourses()
            ->orderBy('name')
            ->get(['id', 'name', 'class_id']);

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
            'user', 'classCount', 'courseCount', 'quizCount', 'publishedQuizCount',
            'draftQuizCount', 'questionCount', 'assignmentCount', 'studentCount',
            'ungradedCount', 'ungradedAssignmentCount', 'pendingGradingCount',
            'totalAttempts', 'submittedAttempts', 'completionRate', 'averageScore',
            'recentAssignments', 'recentClasses', 'recentQuizzes', 'recentSubmissions',
            'upcomingQuizzes', 'recentNotifications', 'classesForForms', 'coursesForForms',
            'weekDays', 'activityData'
        ));
    }
}
