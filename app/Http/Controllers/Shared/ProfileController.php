<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isTeacher()) {
            $classCount = $user->createdClasses()->count();
            $courseCount = $user->createdCourses()->count();
            $quizCount = $user->quizzes()->count();
            $publishedQuizCount = $user->quizzes()->where('status', 'published')->count();
            $assignmentCount = $user->assignments()->count();
            $questionCount = $user->questions()->count();
            $studentCount = $user->createdClasses()->withCount('students')->get()->sum('students_count');
            $submissionCount = DB::table('submissions')
                ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
                ->where('assignments.teacher_id', $user->id)
                ->count();
            $quizAttemptCount = DB::table('quiz_user')
                ->join('quizzes', 'quiz_user.quiz_id', '=', 'quizzes.id')
                ->where('quizzes.teacher_id', $user->id)
                ->whereNotNull('quiz_user.submitted_at')
                ->count();
            $avgScore = DB::table('grades')
                ->where('grader_id', $user->id)
                ->avg('score');
            $avgScore = $avgScore !== null ? round($avgScore, 1) : null;

            $topClasses = $user->createdClasses()
                ->withCount(['students', 'quizzes', 'assignments'])
                ->latest()
                ->limit(4)
                ->get();

            $recentQuizzes = $user->quizzes()
                ->withCount('questions')
                ->latest()
                ->limit(5)
                ->get();

            $recentAssignments = $user->assignments()
                ->withCount('submissions')
                ->latest()
                ->limit(5)
                ->get();

            $recentActivities = collect()
                ->merge($topClasses->map(fn ($class) => (object) [
                    'type' => 'class',
                    'title' => $class->name,
                    'meta' => $class->students_count . ' học sinh',
                    'created_at' => $class->created_at,
                    'url' => route('teacher.class-detail', $class),
                ]))
                ->merge($recentQuizzes->map(fn ($quiz) => (object) [
                    'type' => 'quiz',
                    'title' => $quiz->title,
                    'meta' => $quiz->questions_count . ' câu hỏi',
                    'created_at' => $quiz->created_at,
                    'url' => route('teacher.quiz-detail', $quiz),
                ]))
                ->merge($recentAssignments->map(fn ($assignment) => (object) [
                    'type' => 'assignment',
                    'title' => $assignment->title,
                    'meta' => $assignment->submissions_count . ' bài nộp',
                    'created_at' => $assignment->created_at,
                    'url' => route('teacher.assignments'),
                ]))
                ->sortByDesc('created_at')
                ->take(8)
                ->values();

            $activityHeatmap = $this->buildTeacherActivityHeatmap($user);
            $activeDays = collect($activityHeatmap)->where('count', '>', 0)->count();
            $memberSince = $user->created_at?->format('m/Y') ?? 'Chưa có';

            $achievements = collect([
                [
                    'icon' => 'award',
                    'label' => 'Khởi tạo lớp học',
                    'value' => $classCount > 0 ? $classCount . ' lớp' : 'Chưa đạt',
                    'active' => $classCount > 0,
                ],
                [
                    'icon' => 'target',
                    'label' => 'Đề kiểm tra đã tạo',
                    'value' => $quizCount > 0 ? $quizCount . ' đề' : 'Chưa đạt',
                    'active' => $quizCount > 0,
                ],
                [
                    'icon' => 'users',
                    'label' => 'Học sinh đang quản lý',
                    'value' => $studentCount > 0 ? $studentCount . ' học sinh' : 'Chưa có',
                    'active' => $studentCount > 0,
                ],
                [
                    'icon' => 'book',
                    'label' => 'Ngân hàng câu hỏi',
                    'value' => $questionCount > 0 ? $questionCount . ' câu' : 'Chưa có',
                    'active' => $questionCount > 0,
                ],
                [
                    'icon' => 'star',
                    'label' => 'Điểm trung bình đã chấm',
                    'value' => $avgScore !== null ? $avgScore . '/100' : 'Chưa có',
                    'active' => $avgScore !== null,
                ],
                [
                    'icon' => 'flame',
                    'label' => 'Ngày có hoạt động',
                    'value' => $activeDays . ' ngày',
                    'active' => $activeDays > 0,
                ],
            ]);

            return view('pages.teacher.profile', compact(
                'user', 'classCount', 'courseCount', 'quizCount', 'publishedQuizCount',
                'assignmentCount', 'questionCount', 'studentCount', 'submissionCount',
                'quizAttemptCount', 'avgScore', 'topClasses', 'recentQuizzes',
                'recentAssignments', 'recentActivities', 'activityHeatmap',
                'achievements', 'memberSince'
            ));
        } else {
            $classCount = $user->classes()->count();
            $courseCount = $user->courses()->count();
            $quizCount = $user->quizAttempts()->whereNotNull('submitted_at')->count();
            $assignmentCount = $user->submissions()->count();
            $pendingQuizCount = $user->quizAttempts()->whereNull('submitted_at')->count();

            $submittedAssignmentIds = $user->submissions()->pluck('assignment_id');
            $pendingAssignmentCount = DB::table('assignments')
                ->leftJoin('class_user', 'class_user.class_id', '=', 'assignments.class_id')
                ->leftJoin('course_user', 'course_user.course_id', '=', 'assignments.course_id')
                ->where(function ($query) use ($user) {
                    $query->where('class_user.user_id', $user->id)
                        ->orWhere('course_user.user_id', $user->id);
                })
                ->when($submittedAssignmentIds->isNotEmpty(), fn ($query) => $query->whereNotIn('assignments.id', $submittedAssignmentIds))
                ->whereNull('assignments.deleted_at')
                ->distinct('assignments.id')
                ->count('assignments.id');

            $quizScores = DB::table('quiz_user')
                ->join('quizzes', 'quizzes.id', '=', 'quiz_user.quiz_id')
                ->leftJoin('grades', function ($join) use ($user) {
                    $join->on('grades.gradable_id', '=', 'quizzes.id')
                        ->where('grades.gradable_type', \App\Models\Quiz::class)
                        ->where('grades.student_id', $user->id);
                })
                ->where('quiz_user.user_id', $user->id)
                ->whereNotNull('quiz_user.submitted_at')
                ->select([
                    'quiz_user.score as attempt_score',
                    'quiz_user.total_points as attempt_total',
                    'quizzes.total_points as quiz_total',
                    'grades.score as manual_score',
                ])
                ->get()
                ->map(function ($row) {
                    $score = $row->manual_score ?? $row->attempt_score;
                    $max = $row->attempt_total ?: ($row->quiz_total ?: 10);

                    return $score !== null && $max > 0 ? ((float) $score / (float) $max) * 100 : null;
                })
                ->filter(fn ($score) => $score !== null);

            $assignmentScores = DB::table('grades')
                ->join('submissions', 'submissions.id', '=', 'grades.gradable_id')
                ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
                ->where('grades.student_id', $user->id)
                ->where('grades.gradable_type', \App\Models\Submission::class)
                ->select(['grades.score', 'assignments.total_points'])
                ->get()
                ->map(fn ($row) => $row->score !== null && $row->total_points > 0
                    ? ((float) $row->score / (float) $row->total_points) * 100
                    : null)
                ->filter(fn ($score) => $score !== null);

            $allScores = $quizScores->merge($assignmentScores);
            $avgGrade = $allScores->isNotEmpty() ? round($allScores->avg(), 1) : null;
            $bestGrade = $allScores->isNotEmpty() ? round($allScores->max(), 1) : null;

            $classes = $user->classes()
                ->with(['teacher:id,name,email'])
                ->withCount(['students', 'courses', 'quizzes', 'assignments'])
                ->latest('class_user.joined_at')
                ->limit(4)
                ->get();

            $courses = $user->courses()
                ->with(['teacher:id,name', 'classModel:id,name'])
                ->withCount(['quizzes', 'assignments'])
                ->latest('course_user.enrolled_at')
                ->limit(4)
                ->get();

            $recentQuizAttempts = $user->quizAttempts()
                ->with(['course:id,name', 'classModel:id,name'])
                ->whereNotNull('submitted_at')
                ->latest('quiz_user.submitted_at')
                ->limit(5)
                ->get();

            $recentSubmissions = $user->submissions()
                ->with(['assignment:id,title,course_id,class_id,total_points', 'assignment.course:id,name', 'assignment.class:id,name'])
                ->latest('submitted_at')
                ->limit(5)
                ->get();

            $recentActivities = collect()
                ->merge($recentQuizAttempts->map(fn ($quiz) => (object) [
                    'type' => 'quiz',
                    'title' => $quiz->title,
                    'meta' => 'Nộp bài kiểm tra' . ($quiz->pivot?->score !== null ? ' • ' . $quiz->pivot->score . '/' . ($quiz->pivot->total_points ?: $quiz->total_points ?: 10) : ''),
                    'created_at' => $quiz->pivot?->submitted_at ? \Carbon\Carbon::parse($quiz->pivot->submitted_at) : $quiz->updated_at,
                    'url' => route('student.quiz-result', $quiz),
                ]))
                ->merge($recentSubmissions->map(fn ($submission) => (object) [
                    'type' => 'assignment',
                    'title' => $submission->assignment?->title ?? 'Bài tập',
                    'meta' => 'Nộp bài tập' . ($submission->assignment?->course?->name ? ' • ' . $submission->assignment->course->name : ''),
                    'created_at' => $submission->submitted_at,
                    'url' => $submission->assignment ? route('student.assignment-detail', $submission->assignment) : route('student.assignments'),
                ]))
                ->merge($classes->map(fn ($class) => (object) [
                    'type' => 'class',
                    'title' => $class->name,
                    'meta' => 'Tham gia lớp • ' . ($class->teacher?->name ?? 'Giáo viên'),
                    'created_at' => $class->pivot?->joined_at ? \Carbon\Carbon::parse($class->pivot->joined_at) : $class->created_at,
                    'url' => route('student.classes.show', $class),
                ]))
                ->sortByDesc('created_at')
                ->take(8)
                ->values();

            $activityHeatmap = $this->buildStudentActivityHeatmap($user);
            $activeDays = collect($activityHeatmap)->where('count', '>', 0)->count();
            $memberSince = $user->created_at?->format('m/Y') ?? 'Chưa có';

            $achievements = collect([
                [
                    'icon' => 'class',
                    'label' => 'Tham gia lớp học',
                    'value' => $classCount > 0 ? $classCount . ' lớp' : 'Chưa có',
                    'active' => $classCount > 0,
                ],
                [
                    'icon' => 'book',
                    'label' => 'Khóa học đang học',
                    'value' => $courseCount > 0 ? $courseCount . ' khóa' : 'Chưa có',
                    'active' => $courseCount > 0,
                ],
                [
                    'icon' => 'target',
                    'label' => 'Bài kiểm tra đã làm',
                    'value' => $quizCount > 0 ? $quizCount . ' bài' : 'Chưa có',
                    'active' => $quizCount > 0,
                ],
                [
                    'icon' => 'assignment',
                    'label' => 'Bài tập đã nộp',
                    'value' => $assignmentCount > 0 ? $assignmentCount . ' bài' : 'Chưa có',
                    'active' => $assignmentCount > 0,
                ],
                [
                    'icon' => 'star',
                    'label' => 'Điểm cao nhất',
                    'value' => $bestGrade !== null ? $bestGrade . '%' : 'Chưa có',
                    'active' => $bestGrade !== null,
                ],
                [
                    'icon' => 'flame',
                    'label' => 'Ngày có hoạt động',
                    'value' => $activeDays . ' ngày',
                    'active' => $activeDays > 0,
                ],
            ]);

            return view('pages.student.profile', compact(
                'user', 'classCount', 'courseCount', 'quizCount',
                'assignmentCount', 'avgGrade', 'bestGrade', 'pendingQuizCount',
                'pendingAssignmentCount', 'classes', 'courses', 'recentActivities',
                'activityHeatmap', 'achievements', 'memberSince'
            ));
        }
    }

    private function buildTeacherActivityHeatmap($user): Collection
    {
        $start = now()->subDays(83)->startOfDay();
        $end = now()->endOfDay();
        $counts = [];

        $dateSources = collect()
            ->merge($user->createdClasses()->whereBetween('created_at', [$start, $end])->pluck('created_at'))
            ->merge($user->quizzes()->whereBetween('created_at', [$start, $end])->pluck('created_at'))
            ->merge($user->assignments()->whereBetween('created_at', [$start, $end])->pluck('created_at'))
            ->merge(DB::table('grades')->where('grader_id', $user->id)->whereBetween('graded_at', [$start, $end])->pluck('graded_at'));

        foreach ($dateSources as $date) {
            if (!$date) {
                continue;
            }
            $key = \Carbon\Carbon::parse($date)->toDateString();
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $max = max($counts ?: [0]);

        return collect(range(0, 83))->map(function ($offset) use ($start, $counts, $max) {
            $date = $start->copy()->addDays($offset);
            $count = $counts[$date->toDateString()] ?? 0;

            return [
                'date' => $date,
                'count' => $count,
                'level' => $count === 0 || $max === 0 ? 0 : max(1, (int) ceil(($count / $max) * 4)),
            ];
        });
    }

    private function buildStudentActivityHeatmap($user): Collection
    {
        $start = now()->subDays(83)->startOfDay();
        $end = now()->endOfDay();
        $counts = [];

        $dateSources = collect()
            ->merge(DB::table('quiz_user')
                ->where('user_id', $user->id)
                ->whereNotNull('submitted_at')
                ->whereBetween('submitted_at', [$start, $end])
                ->pluck('submitted_at'))
            ->merge(DB::table('submissions')
                ->where('student_id', $user->id)
                ->whereBetween('submitted_at', [$start, $end])
                ->pluck('submitted_at'))
            ->merge(DB::table('class_user')
                ->where('user_id', $user->id)
                ->whereBetween('joined_at', [$start, $end])
                ->pluck('joined_at'))
            ->merge(DB::table('course_user')
                ->where('user_id', $user->id)
                ->whereBetween('enrolled_at', [$start, $end])
                ->pluck('enrolled_at'));

        foreach ($dateSources as $date) {
            if (!$date) {
                continue;
            }

            $key = \Carbon\Carbon::parse($date)->toDateString();
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $max = max($counts ?: [0]);

        return collect(range(0, 83))->map(function ($offset) use ($start, $counts, $max) {
            $date = $start->copy()->addDays($offset);
            $count = $counts[$date->toDateString()] ?? 0;

            return [
                'date' => $date,
                'count' => $count,
                'level' => $count === 0 || $max === 0 ? 0 : max(1, (int) ceil(($count / $max) * 4)),
            ];
        });
    }
}
