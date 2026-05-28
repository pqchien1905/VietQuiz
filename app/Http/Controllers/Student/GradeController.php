<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Quiz;
use App\Models\Submission;
use App\Support\CollectionPaginator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'type' => $request->query('type', 'all'),
            'status' => $request->query('status', 'all'),
            'course_id' => $request->integer('course_id') ?: null,
            'class_id' => $request->integer('class_id') ?: null,
            'per_page' => $request->integer('per_page') ?: 10,
        ];
        $filters['per_page'] = in_array($filters['per_page'], [10, 20, 50, 100], true) ? $filters['per_page'] : 10;

        if ($filters['course_id']) {
            abort_unless($user->courses()->where('courses.id', $filters['course_id'])->exists(), 404);
        }

        if ($filters['class_id']) {
            abort_unless($user->classes()->where('classes.id', $filters['class_id'])->exists(), 404);
        }

        $courses = $user->courses()->orderBy('name')->get(['courses.id', 'courses.name']);
        $classes = $user->classes()->orderBy('name')->get(['classes.id', 'classes.name']);

        $items = $this->gradeItems($user->id);
        $summarySource = $items;

        $summary = $this->summary($summarySource);
        $courseChartData = $this->courseChartData($summarySource);
        $trendChartData = $this->trendChartData($summarySource);

        $items = $this->applyFilters($items, $filters)
            ->sortByDesc(fn ($item) => $item->sort_at?->timestamp ?? 0)
            ->values();

        $grades = CollectionPaginator::make($items, $request, $filters['per_page']);

        return view('pages.student.grades', compact(
            'grades',
            'courses',
            'classes',
            'filters',
            'summary',
            'courseChartData',
            'trendChartData'
        ));
    }

    private function gradeItems(int $studentId): Collection
    {
        return $this->quizItems($studentId)
            ->merge($this->submittedAssignmentItems($studentId))
            ->merge($this->missingAssignmentItems($studentId))
            ->values();
    }

    private function quizItems(int $studentId): Collection
    {
        return DB::table('quiz_user')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_user.quiz_id')
            ->leftJoin('classes', 'classes.id', '=', 'quizzes.class_id')
            ->leftJoin('courses', 'courses.id', '=', 'quizzes.course_id')
            ->leftJoin('grades', function ($join) use ($studentId) {
                $join->on('grades.gradable_id', '=', 'quizzes.id')
                    ->where('grades.gradable_type', Quiz::class)
                    ->where('grades.student_id', $studentId);
            })
            ->where('quiz_user.user_id', $studentId)
            ->whereNotNull('quiz_user.submitted_at')
            ->select([
                'quizzes.id',
                'quizzes.title',
                'quizzes.course_id',
                'quizzes.class_id',
                'quizzes.total_points as quiz_total_points',
                'quiz_user.score as attempt_score',
                'quiz_user.total_points as attempt_total_points',
                'quiz_user.submitted_at',
                'quiz_user.is_graded',
                'classes.name as class_name',
                'courses.name as course_name',
                'grades.score as manual_score',
                'grades.feedback',
                'grades.graded_at',
                'quiz_user.best_score',
                'quiz_user.best_total_points',
            ])
            ->get()
            ->map(function ($row) {
                $score = $row->manual_score ?? $row->best_score ?? $row->attempt_score;
                $maxScore = $row->best_total_points ?: ($row->attempt_total_points ?: ($row->quiz_total_points ?: 10));
                $percentage = $score !== null && $maxScore > 0
                    ? round(((float) $score / (float) $maxScore) * 100, 1)
                    : null;
                $submittedAt = $row->submitted_at ? Carbon::parse($row->submitted_at) : null;
                $gradedAt = $row->graded_at ? Carbon::parse($row->graded_at) : null;

                return (object) [
                    'key' => 'quiz-' . $row->id,
                    'type' => 'quiz',
                    'type_label' => 'Bài kiểm tra',
                    'status' => ((bool) $row->is_graded || $score !== null) ? 'graded' : 'pending',
                    'title' => $row->title,
                    'course_id' => $row->course_id ? (int) $row->course_id : null,
                    'class_id' => $row->class_id ? (int) $row->class_id : null,
                    'course_name' => $row->course_name,
                    'class_name' => $row->class_name,
                    'scope_name' => $row->course_name ?? $row->class_name ?? 'Không rõ lớp',
                    'score' => $score !== null ? (float) $score : null,
                    'max_score' => (float) $maxScore,
                    'percentage' => $percentage,
                    'letter' => $this->letterGrade($percentage),
                    'feedback' => $row->feedback,
                    'submitted_at' => $submittedAt,
                    'graded_at' => $gradedAt,
                    'sort_at' => $gradedAt ?? $submittedAt,
                    'date_label' => $gradedAt
                        ? 'Chấm ' . $gradedAt->format('d/m/Y H:i')
                        : ($submittedAt ? 'Nộp ' . $submittedAt->format('d/m/Y H:i') : 'Đã nộp'),
                    'url' => route('student.quiz-result', $row->id),
                ];
            });
    }

    private function submittedAssignmentItems(int $studentId): Collection
    {
        return Submission::query()
            ->with([
                'assignment:id,class_id,course_id,title,total_points,due_at,type',
                'assignment.class:id,name',
                'assignment.course:id,name',
                'grades' => fn ($query) => $query
                    ->where('student_id', $studentId)
                    ->latest('graded_at'),
            ])
            ->where('student_id', $studentId)
            ->whereHas('assignment', fn ($query) => $this->assignedAssignmentQuery($query, $studentId))
            ->latest('submitted_at')
            ->get()
            ->map(function (Submission $submission) {
                $assignment = $submission->assignment;
                $grade = $submission->grades->first();
                $maxScore = $assignment?->total_points ?: 100;
                $percentage = $grade && $maxScore > 0
                    ? round(((float) $grade->score / (float) $maxScore) * 100, 1)
                    : null;

                return (object) [
                    'key' => 'assignment-' . $submission->id,
                    'type' => 'assignment',
                    'type_label' => 'Bài tập',
                    'status' => $grade ? 'graded' : 'pending',
                    'title' => $assignment?->title ?? 'Bài tập',
                    'course_id' => $assignment?->course_id ? (int) $assignment->course_id : null,
                    'class_id' => $assignment?->class_id ? (int) $assignment->class_id : null,
                    'course_name' => $assignment?->course?->name,
                    'class_name' => $assignment?->class?->name,
                    'scope_name' => $assignment?->course?->name ?? $assignment?->class?->name ?? 'Không rõ lớp',
                    'score' => $grade?->score !== null ? (float) $grade->score : null,
                    'max_score' => (float) $maxScore,
                    'percentage' => $percentage,
                    'letter' => $this->letterGrade($percentage),
                    'feedback' => $grade?->feedback,
                    'submitted_at' => $submission->submitted_at,
                    'graded_at' => $grade?->graded_at,
                    'sort_at' => $grade?->graded_at ?? $submission->submitted_at,
                    'date_label' => $grade?->graded_at
                        ? 'Chấm ' . $grade->graded_at->format('d/m/Y H:i')
                        : 'Nộp ' . optional($submission->submitted_at)->format('d/m/Y H:i'),
                    'url' => $assignment ? route('student.assignment-detail', $assignment) : '#',
                ];
            });
    }

    private function missingAssignmentItems(int $studentId): Collection
    {
        return Assignment::query()
            ->with(['class:id,name', 'course:id,name'])
            ->where(fn ($query) => $this->assignedAssignmentQuery($query, $studentId))
            ->whereDoesntHave('submissions', fn ($query) => $query->where('student_id', $studentId))
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->get()
            ->map(function (Assignment $assignment) {
                $isOverdue = $assignment->due_at?->isPast() ?? false;

                return (object) [
                    'key' => 'missing-assignment-' . $assignment->id,
                    'type' => 'assignment',
                    'type_label' => 'Bài tập',
                    'status' => 'not_submitted',
                    'title' => $assignment->title,
                    'course_id' => $assignment->course_id ? (int) $assignment->course_id : null,
                    'class_id' => $assignment->class_id ? (int) $assignment->class_id : null,
                    'course_name' => $assignment->course?->name,
                    'class_name' => $assignment->class?->name,
                    'scope_name' => $assignment->course?->name ?? $assignment->class?->name ?? 'Không rõ lớp',
                    'score' => null,
                    'max_score' => (float) ($assignment->total_points ?: 100),
                    'percentage' => null,
                    'letter' => null,
                    'feedback' => $isOverdue ? 'Đã quá hạn nộp bài.' : null,
                    'submitted_at' => null,
                    'graded_at' => null,
                    'sort_at' => $assignment->due_at ?? $assignment->created_at,
                    'date_label' => $assignment->due_at
                        ? ($isOverdue ? 'Quá hạn ' : 'Hạn ') . $assignment->due_at->format('d/m/Y H:i')
                        : 'Không giới hạn',
                    'url' => route('student.assignment-detail', $assignment),
                ];
            });
    }

    private function assignedAssignmentQuery($query, int $studentId)
    {
        return $query->where(function ($inner) use ($studentId) {
            $inner->whereHas('class.students', fn ($students) => $students->where('users.id', $studentId))
                ->orWhereHas('course.students', fn ($students) => $students->where('users.id', $studentId));
        });
    }

    private function applyFilters(Collection $items, array $filters): Collection
    {
        if (in_array($filters['type'], ['quiz', 'assignment'], true)) {
            $items = $items->where('type', $filters['type']);
        }

        if (in_array($filters['status'], ['graded', 'pending', 'not_submitted'], true)) {
            $items = $items->where('status', $filters['status']);
        }

        if ($filters['course_id']) {
            $items = $items->where('course_id', $filters['course_id']);
        }

        if ($filters['class_id']) {
            $items = $items->where('class_id', $filters['class_id']);
        }

        if ($filters['q'] !== '') {
            $search = mb_strtolower($filters['q']);
            $items = $items->filter(function ($item) use ($search) {
                return str_contains(mb_strtolower($item->title), $search)
                    || str_contains(mb_strtolower($item->scope_name ?? ''), $search)
                    || str_contains(mb_strtolower($item->course_name ?? ''), $search)
                    || str_contains(mb_strtolower($item->class_name ?? ''), $search);
            });
        }

        return $items;
    }

    private function summary(Collection $items): array
    {
        $graded = $items->where('status', 'graded')->whereNotNull('percentage');

        return [
            'total' => $items->count(),
            'avg_pct' => $graded->isNotEmpty() ? round($graded->avg('percentage'), 1) : null,
            'graded' => $items->where('status', 'graded')->count(),
            'pending' => $items->where('status', 'pending')->count(),
            'not_submitted' => $items->where('status', 'not_submitted')->count(),
            'quiz' => $items->where('type', 'quiz')->count(),
            'assignment' => $items->where('type', 'assignment')->count(),
            'best_pct' => $graded->isNotEmpty() ? round($graded->max('percentage'), 1) : null,
            'letter' => $this->letterGrade($graded->isNotEmpty() ? $graded->avg('percentage') : null),
        ];
    }

    private function courseChartData(Collection $items): array
    {
        return $items
            ->where('status', 'graded')
            ->whereNotNull('percentage')
            ->groupBy('scope_name')
            ->map(fn ($group, $name) => [
                'label' => $name ?: 'Không rõ lớp',
                'average' => round($group->avg('percentage'), 1),
                'count' => $group->count(),
            ])
            ->sortByDesc('average')
            ->take(8)
            ->values()
            ->all();
    }

    private function trendChartData(Collection $items): array
    {
        return $items
            ->where('status', 'graded')
            ->whereNotNull('percentage')
            ->filter(fn ($item) => $item->sort_at !== null)
            ->sortBy('sort_at')
            ->groupBy(fn ($item) => $item->sort_at->format('d/m'))
            ->map(fn ($group, $label) => [
                'label' => $label,
                'average' => round($group->avg('percentage'), 1),
            ])
            ->take(-8)
            ->values()
            ->all();
    }

    private function letterGrade(?float $percentage): ?string
    {
        if ($percentage === null) {
            return null;
        }

        return match (true) {
            $percentage >= 90 => 'A',
            $percentage >= 80 => 'B',
            $percentage >= 65 => 'C',
            $percentage >= 50 => 'D',
            default => 'F',
        };
    }
}
