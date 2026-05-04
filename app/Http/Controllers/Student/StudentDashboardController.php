<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Notification;
use App\Models\Quiz;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $courseCount = $user->courses()->count();
        $classCount = $user->classes()->count();

        $courseIds = $user->courses()->pluck('courses.id');
        $classIds = $user->classes()->pluck('classes.id');

        $submittedQuizIds = $user->quizAttempts()
            ->whereNotNull('submitted_at')
            ->pluck('quizzes.id');

        $assignedQuizScope = function ($q) use ($classIds, $courseIds, $user) {
            $q->whereIn('class_id', $classIds)
                ->orWhereIn('course_id', $courseIds)
                ->orWhereJsonContains('assigned_students', $user->id)
                ->orWhere(function ($q) {
                    $q->whereNull('class_id')
                        ->whereNull('course_id')
                        ->where(function ($q) {
                            $q->whereNull('assigned_students')
                                ->orWhereJsonLength('assigned_students', 0);
                        });
                });
        };

        $pendingQuizQuery = Quiz::query()
            ->where('status', 'published')
            ->where($assignedQuizScope)
            ->where(function ($q) {
                $q->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            })
            ->whereNotIn('id', $submittedQuizIds);

        $pendingQuizCount = (clone $pendingQuizQuery)->count();

        $upcomingQuizzes = (clone $pendingQuizQuery)
            ->with(['teacher', 'course', 'classModel'])
            ->withCount('questions')
            ->orderByRaw('end_at is null')
            ->orderBy('end_at')
            ->latest()
            ->take(5)
            ->get();

        $submittedAssignmentIds = $user->submissions()->pluck('assignment_id');

        $assignmentQuery = Assignment::query()
            ->where(function ($q) use ($classIds, $courseIds) {
                $q->whereIn('class_id', $classIds)
                    ->orWhereIn('course_id', $courseIds);
            });

        $pendingAssignmentCount = (clone $assignmentQuery)
            ->whereNotIn('id', $submittedAssignmentIds)
            ->where(function ($q) {
                $q->whereNull('due_at')
                    ->orWhere('due_at', '>=', now());
            })
            ->count();

        $dueSoonAssignments = (clone $assignmentQuery)
            ->whereNotIn('id', $submittedAssignmentIds)
            ->where(function ($q) {
                $q->whereNull('due_at')
                    ->orWhere('due_at', '>=', now());
            })
            ->with(['teacher', 'class', 'course'])
            ->get()
            ->sortBy(fn ($assignment) => $assignment->due_at?->timestamp ?? PHP_INT_MAX)
            ->take(5)
            ->values();

        $avgGrade = $user->quizAttempts()
            ->wherePivot('is_graded', true)
            ->get()
            ->avg(fn($q) => $q->pivot->total_points > 0 ? ($q->pivot->score / $q->pivot->total_points) * 100 : 0);

        $recentAttempts = $user->quizAttempts()
            ->whereNotNull('submitted_at')
            ->with(['course', 'classModel'])
            ->latest('quiz_user.submitted_at')
            ->take(5)
            ->get();

        $totalAssignedQuizCount = Quiz::query()
            ->where('status', 'published')
            ->where($assignedQuizScope)
            ->count();
        $totalAssignmentCount = (clone $assignmentQuery)->count();
        $completedQuizCount = $submittedQuizIds->count();
        $submittedAssignmentCount = $submittedAssignmentIds->count();
        $totalLearningItems = $totalAssignedQuizCount + $totalAssignmentCount;
        $completedLearningItems = $completedQuizCount + $submittedAssignmentCount;
        $completionPercent = $totalLearningItems > 0
            ? round(($completedLearningItems / $totalLearningItems) * 100)
            : 0;

        $recentCourses = $user->courses()
            ->with(['teacher', 'classModel'])
            ->withCount(['quizzes', 'assignments'])
            ->latest('course_user.enrolled_at')
            ->take(3)
            ->get();

        $recentClasses = $user->classes()
            ->with(['teacher'])
            ->withCount(['courses', 'quizzes', 'assignments'])
            ->latest('class_user.joined_at')
            ->take(3)
            ->get();

        $recentNotifications = Notification::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('pages.student.dashboard', compact(
            'user', 'courseCount', 'classCount', 'pendingQuizCount',
            'pendingAssignmentCount', 'upcomingQuizzes', 'dueSoonAssignments',
            'avgGrade', 'recentAttempts', 'completionPercent', 'completedLearningItems',
            'totalLearningItems', 'recentCourses', 'recentClasses', 'recentNotifications'
        ));
    }
}
