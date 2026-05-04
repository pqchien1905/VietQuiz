<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\Submission;
use App\Support\CollectionPaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status', 'all'),
            'class_id' => $request->query('class_id'),
        ];

        $courses = $user->courses()
            ->with([
                'teacher:id,name,email',
                'classModel:id,name,grade_level',
                'quizzes' => fn ($query) => $query
                    ->withCount('questions')
                    ->orderByRaw('end_at is null')
                    ->orderBy('end_at')
                    ->latest(),
                'assignments' => fn ($query) => $query
                    ->orderByRaw('due_at is null')
                    ->orderBy('due_at')
                    ->latest(),
            ])
            ->withCount(['students', 'quizzes', 'assignments'])
            ->latest('course_user.enrolled_at')
            ->get();

        $quizIds = $courses->flatMap(fn ($course) => $course->quizzes->pluck('id'))->unique()->values();
        $assignmentIds = $courses->flatMap(fn ($course) => $course->assignments->pluck('id'))->unique()->values();

        $quizAttempts = DB::table('quiz_user')
            ->where('user_id', $user->id)
            ->whereIn('quiz_id', $quizIds)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->get()
            ->unique('quiz_id')
            ->keyBy('quiz_id');

        $manualQuizGrades = Grade::query()
            ->where('student_id', $user->id)
            ->where('gradable_type', Quiz::class)
            ->whereIn('gradable_id', $quizIds)
            ->latest('graded_at')
            ->get()
            ->unique('gradable_id')
            ->keyBy('gradable_id');

        $submissions = Submission::query()
            ->with(['grades' => fn ($query) => $query->latest('graded_at')])
            ->where('student_id', $user->id)
            ->whereIn('assignment_id', $assignmentIds)
            ->latest('submitted_at')
            ->get()
            ->unique('assignment_id')
            ->keyBy('assignment_id');

        $classes = $courses
            ->pluck('classModel')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $courses = $courses->map(function (Course $course) use ($quizAttempts, $manualQuizGrades, $submissions) {
                $visibleQuizzes = $course->quizzes->where('status', 'published');
                $totalItems = $visibleQuizzes->count() + $course->assignments->count();
                $completedQuizzes = $visibleQuizzes->filter(fn ($quiz) => $quizAttempts->has($quiz->id))->count();
                $submittedAssignments = $course->assignments->filter(fn ($assignment) => $submissions->has($assignment->id))->count();
                $completedItems = $completedQuizzes + $submittedAssignments;

                $earnedPoints = 0;
                $possiblePoints = 0;

                foreach ($visibleQuizzes as $quiz) {
                    $attempt = $quizAttempts->get($quiz->id);
                    if (!$attempt) {
                        continue;
                    }

                    $maxScore = (float) ($attempt->total_points ?: $quiz->total_points ?: 0);
                    $score = $manualQuizGrades->get($quiz->id)?->score ?? $attempt->score;

                    if ($score !== null && $maxScore > 0) {
                        $earnedPoints += (float) $score;
                        $possiblePoints += $maxScore;
                    }
                }

                foreach ($course->assignments as $assignment) {
                    $submission = $submissions->get($assignment->id);
                    $grade = $submission?->grades->first();

                    if ($grade && $assignment->total_points > 0) {
                        $earnedPoints += (float) $grade->score;
                        $possiblePoints += (float) $assignment->total_points;
                    }
                }

                $nextQuiz = $visibleQuizzes
                    ->filter(fn ($quiz) => !$quizAttempts->has($quiz->id) && $quiz->end_at && $quiz->end_at->isFuture())
                    ->sortBy('end_at')
                    ->first();
                $nextAssignment = $course->assignments
                    ->filter(fn ($assignment) => !$submissions->has($assignment->id) && $assignment->due_at && $assignment->due_at->isFuture())
                    ->sortBy('due_at')
                    ->first();
                $nextDueAt = collect([$nextQuiz?->end_at, $nextAssignment?->due_at])->filter()->sort()->first();

                $course->progress_pct = $totalItems > 0 ? (int) round($completedItems / $totalItems * 100) : 0;
                $course->completed_items = $completedItems;
                $course->total_items = $totalItems;
                $course->avg_grade = $possiblePoints > 0 ? round($earnedPoints / $possiblePoints * 100, 1) : null;
                $course->next_due_at = $nextDueAt;
                $course->next_due_label = $nextDueAt ? $nextDueAt->diffForHumans(['parts' => 2, 'short' => true]) : null;
                $course->learning_status = $course->status === 'draft'
                    ? 'draft'
                    : ($totalItems > 0 && $completedItems >= $totalItems ? 'completed' : 'active');
                $course->published_quizzes_count = $visibleQuizzes->count();

                return $course;
            });

        $summary = [
            'total' => $courses->count(),
            'active' => $courses->where('learning_status', 'active')->count(),
            'completed' => $courses->where('learning_status', 'completed')->count(),
            'pending_items' => $courses->sum(fn ($course) => max(0, $course->total_items - $course->completed_items)),
            'avg_progress' => $courses->count() > 0 ? round($courses->avg('progress_pct')) : 0,
        ];

        if ($filters['q'] !== '') {
            $needle = mb_strtolower($filters['q']);
            $courses = $courses->filter(function (Course $course) use ($needle) {
                return str_contains(mb_strtolower($course->name), $needle)
                    || str_contains(mb_strtolower($course->description ?? ''), $needle)
                    || str_contains(mb_strtolower($course->teacher?->name ?? ''), $needle)
                    || str_contains(mb_strtolower($course->classModel?->name ?? ''), $needle);
            });
        }

        if (in_array($filters['status'], ['active', 'completed', 'draft'], true)) {
            $courses = $courses->where('learning_status', $filters['status']);
        }

        if ($filters['class_id']) {
            $courses = $courses->where('class_id', (int) $filters['class_id']);
        }

        $courses = CollectionPaginator::make($courses->values(), $request, 9);

        return view('pages.student.courses', compact('courses', 'classes', 'filters', 'summary'));
    }

    public function show(Request $request, Course $course)
    {
        abort_unless(
            $request->user()->courses()->where('courses.id', $course->id)->exists(),
            403
        );

        $user = $request->user();

        $course->load([
            'teacher',
            'classModel',
            'quizzes' => fn ($q) => $q->withCount('questions')->orderByDesc('created_at'),
            'assignments',
        ]);

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
            ->with(['assignment', 'grades' => fn ($query) => $query->latest('graded_at')])
            ->get();

        $totalItems = $course->quizzes->count() + $course->assignments->count();
        $completedItems = count($completedQuizIds) + count($submittedAssignmentIds);
        $completionPct = $totalItems > 0 ? round($completedItems / $totalItems * 100) : 0;

        $manualQuizGrades = \App\Models\Grade::where('student_id', $user->id)
            ->where('gradable_type', Quiz::class)
            ->whereIn('gradable_id', $course->quizzes->pluck('id'))
            ->latest('graded_at')
            ->get()
            ->unique('gradable_id')
            ->keyBy('gradable_id');

        $totalEarned = 0;
        $totalPossible = 0;
        foreach ($quizGrades as $quizGrade) {
            $score = $manualQuizGrades->get($quizGrade->quiz_id)?->score ?? $quizGrade->score;
            if ($score !== null && $quizGrade->total_points > 0) {
                $totalEarned += (float) $score;
                $totalPossible += (float) $quizGrade->total_points;
            }
        }
        foreach ($assignmentGrades as $sub) {
            $grade = $sub->grades->first();
            if ($grade && $sub->assignment?->total_points > 0) {
                $totalEarned += (float) $grade->score;
                $totalPossible += (float) $sub->assignment->total_points;
            }
        }
        $avgGrade = $totalPossible > 0 ? round($totalEarned / $totalPossible * 100, 1) : null;

        return view('pages.student.course-detail', compact(
            'course', 'completedQuizIds', 'submittedAssignmentIds', 'completionPct', 'avgGrade'
        ));
    }
}
