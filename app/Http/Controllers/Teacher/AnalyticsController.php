<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Quiz;
use App\Models\Submission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Support\VipFeature;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $period = $this->normalizePeriod($request->query('period', 'month'));
        $classId = $request->integer('class_id') ?: null;
        $range = $this->periodRange($period, $request);

        $classes = $user->createdClasses()
            ->withCount(['students', 'quizzes', 'assignments'])
            ->orderBy('name')
            ->get();

        if ($classId && !$classes->contains('id', $classId)) {
            abort(404);
        }

        $classIds = $classes->pluck('id')->all();
        $rows = $this->analyticsRows($user->id, $range['start'], $range['end'], $classId);
        $allRows = $this->analyticsRows($user->id, null, null, $classId);

        $submittedRows = $rows->whereNotNull('submitted_at');
        $gradedRows = $submittedRows->whereNotNull('score');
        $allSubmittedRows = $allRows->whereNotNull('submitted_at');

        $classCount = $classes->count();
        $quizCount = $user->quizzes()
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->count();
        $assignmentCount = Assignment::where('teacher_id', $user->id)
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->count();
        $studentCount = DB::table('class_user')
            ->whereIn('class_id', $classIds)
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->distinct('user_id')
            ->count('user_id');

        $totalGraded = $gradedRows->count();
        $avgScore = $gradedRows->count() ? round($gradedRows->avg('percentage'), 1) : null;
        $quizSubmissions = $submittedRows->where('type', 'quiz')->count();
        $assignmentSubmissions = $submittedRows->where('type', 'assignment')->count();
        $expectedSubmissions = $this->expectedSubmissions($user->id, $classId);
        $completionRate = $expectedSubmissions > 0
            ? round(min(100, ($allSubmittedRows->count() / $expectedSubmissions) * 100), 1)
            : null;

        $scoreByClass = $this->scoreByClass($classes, $rows, $classId);
        $distribution = $this->scoreDistribution($gradedRows);
        $topStudents = $this->topStudents($gradedRows);
        $weeklyTrend = $this->weeklyTrend($user->id, $classId);
        $activityTrend = $this->activityTrend($user->id, $classId, $range['start'], $range['end']);
        $atRiskStudents = $this->atRiskStudents($rows);
        $recentActivities = $submittedRows
            ->sortByDesc('submitted_at')
            ->take(8)
            ->values();

        $summary = [
            'avg_score' => $avgScore,
            'total_graded' => $totalGraded,
            'submitted' => $submittedRows->count(),
            'quiz_submissions' => $quizSubmissions,
            'assignment_submissions' => $assignmentSubmissions,
            'completion_rate' => $completionRate,
            'pending_grading' => $submittedRows->whereNull('score')->count(),
            'quiz_count' => $quizCount,
            'assignment_count' => $assignmentCount,
            'student_count' => $studentCount,
            'class_count' => $classId ? 1 : $classCount,
        ];

        return view('pages.teacher.analytics', compact(
            'period',
            'classId',
            'range',
            'classes',
            'summary',
            'scoreByClass',
            'distribution',
            'topStudents',
            'weeklyTrend',
            'activityTrend',
            'atRiskStudents',
            'recentActivities'
        ));
    }

    public function export(Request $request)
    {
        if (!VipFeature::isVip($request->user())) {
            return back()->with('error', VipFeature::exportMessage());
        }

        $user = $request->user();
        $period = $this->normalizePeriod($request->query('period', 'month'));
        $classId = $request->integer('class_id') ?: null;
        $range = $this->periodRange($period, $request);

        if ($classId && !$user->createdClasses()->whereKey($classId)->exists()) {
            abort(404);
        }

        $rows = $this->analyticsRows($user->id, $range['start'], $range['end'], $classId)
            ->sortByDesc('submitted_at')
            ->values();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Phan tich');

        $sheet->fromArray([
            'Loại',
            'Lớp',
            'Khóa học',
            'Học sinh',
            'Email',
            'Bài',
            'Điểm',
            'Điểm tối đa',
            'Phần trăm',
            'Trạng thái',
            'Ngày nộp',
            'Ngày chấm',
        ], null, 'A1');

        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $rowNumber = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([
                $row->type === 'quiz' ? 'Bài kiểm tra' : 'Bài tập',
                $row->class_name,
                $row->course_name,
                $row->student_name,
                $row->student_email,
                $row->item_title,
                $row->score,
                $row->max_score,
                $row->percentage !== null ? $row->percentage / 100 : null,
                $row->score !== null ? 'Đã chấm' : 'Chờ chấm',
                optional($row->submitted_at)->format('d/m/Y H:i'),
                optional($row->graded_at)->format('d/m/Y H:i'),
            ], null, "A{$rowNumber}");
            $rowNumber++;
        }

        $lastRow = max(1, $rowNumber - 1);
        $sheet->getStyle("A1:L{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A1:L{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle("I2:I{$lastRow}")->getNumberFormat()->setFormatCode('0.0%');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:L{$lastRow}");

        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        Storage::makeDirectory('exports');
        $xlsxFilename = 'phan_tich_' . $period . '_' . now()->format('Ymd_His') . '.xlsx';
        $xlsxPath = Storage::path('exports/' . $xlsxFilename);

        (new Xlsx($spreadsheet))->save($xlsxPath);
        $spreadsheet->disconnectWorksheets();

        return response()->download(
            $xlsxPath,
            $xlsxFilename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);

    }

    private function normalizePeriod(?string $period): string
    {
        return in_array($period, ['week', 'month', 'quarter', 'year', 'custom'], true) ? $period : 'month';
    }

    private function periodRange(string $period, Request $request): array
    {
        if ($period === 'custom') {
            $start = $this->parseDate($request->query('start_date'))?->startOfDay() ?? now()->startOfMonth();
            $end = $this->parseDate($request->query('end_date'))?->endOfDay() ?? now()->endOfDay();

            if ($end->lt($start)) {
                $end = $start->copy()->endOfDay();
            }

            return [
                'start' => $start,
                'end' => $end,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ];
        }

        $start = match ($period) {
            'week' => now()->startOfWeek(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $end = now()->endOfDay();

        return [
            'start' => $start,
            'end' => $end,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function analyticsRows(int $teacherId, ?Carbon $start, ?Carbon $end, ?int $classId): Collection
    {
        return $this->quizRows($teacherId, $start, $end, $classId)
            ->merge($this->assignmentRows($teacherId, $start, $end, $classId))
            ->values();
    }

    private function quizRows(int $teacherId, ?Carbon $start, ?Carbon $end, ?int $classId): Collection
    {
        return DB::table('quiz_user')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_user.quiz_id')
            ->join('users', 'users.id', '=', 'quiz_user.user_id')
            ->leftJoin('classes', 'classes.id', '=', 'quizzes.class_id')
            ->leftJoin('courses', 'courses.id', '=', 'quizzes.course_id')
            ->leftJoin('grades', function ($join) {
                $join->on('grades.gradable_id', '=', 'quizzes.id')
                    ->on('grades.student_id', '=', 'quiz_user.user_id')
                    ->where('grades.gradable_type', Quiz::class);
            })
            ->where('quizzes.teacher_id', $teacherId)
            ->whereNotNull('quiz_user.submitted_at')
            ->when($classId, fn ($query) => $query->where('quizzes.class_id', $classId))
            ->when($start, fn ($query) => $query->where('quiz_user.submitted_at', '>=', $start))
            ->when($end, fn ($query) => $query->where('quiz_user.submitted_at', '<=', $end))
            ->select([
                'quiz_user.user_id as student_id',
                'users.name as student_name',
                'users.email as student_email',
                'quizzes.id as item_id',
                'quizzes.title as item_title',
                'quizzes.class_id',
                'quizzes.total_points as quiz_max_score',
                'quiz_user.total_points as attempt_max_score',
                'quiz_user.score as attempt_score',
                'quiz_user.submitted_at',
                'classes.name as class_name',
                'courses.name as course_name',
                'grades.score as manual_score',
                'grades.graded_at',
            ])
            ->get()
            ->map(fn ($row) => $this->formatRow('quiz', $row));
    }

    private function assignmentRows(int $teacherId, ?Carbon $start, ?Carbon $end, ?int $classId): Collection
    {
        return DB::table('submissions')
            ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
            ->join('users', 'users.id', '=', 'submissions.student_id')
            ->leftJoin('classes', 'classes.id', '=', 'assignments.class_id')
            ->leftJoin('courses', 'courses.id', '=', 'assignments.course_id')
            ->leftJoin('grades', function ($join) {
                $join->on('grades.gradable_id', '=', 'submissions.id')
                    ->where('grades.gradable_type', Submission::class);
            })
            ->where('assignments.teacher_id', $teacherId)
            ->when($classId, fn ($query) => $query->where('assignments.class_id', $classId))
            ->when($start, fn ($query) => $query->where('submissions.submitted_at', '>=', $start))
            ->when($end, fn ($query) => $query->where('submissions.submitted_at', '<=', $end))
            ->select([
                'submissions.student_id',
                'users.name as student_name',
                'users.email as student_email',
                'submissions.id as submission_id',
                'assignments.id as item_id',
                'assignments.title as item_title',
                'assignments.class_id',
                'assignments.total_points as assignment_max_score',
                'submissions.submitted_at',
                'classes.name as class_name',
                'courses.name as course_name',
                'grades.score as manual_score',
                'grades.graded_at',
            ])
            ->get()
            ->map(fn ($row) => $this->formatRow('assignment', $row));
    }

    private function formatRow(string $type, object $row): object
    {
        $score = $type === 'quiz' ? ($row->manual_score ?? $row->attempt_score) : $row->manual_score;
        $maxScore = $type === 'quiz'
            ? ($row->attempt_max_score ?: ($row->quiz_max_score ?: 10))
            : ($row->assignment_max_score ?: 100);

        return (object) [
            'type' => $type,
            'student_id' => (int) $row->student_id,
            'student_name' => $row->student_name,
            'student_email' => $row->student_email,
            'item_id' => (int) $row->item_id,
            'item_title' => $row->item_title,
            'class_id' => $row->class_id ? (int) $row->class_id : null,
            'class_name' => $row->class_name ?: 'Chưa gán lớp',
            'course_name' => $row->course_name,
            'score' => $score !== null ? (float) $score : null,
            'max_score' => (float) $maxScore,
            'percentage' => $score !== null && $maxScore > 0 ? round(((float) $score / (float) $maxScore) * 100, 1) : null,
            'submitted_at' => $row->submitted_at ? Carbon::parse($row->submitted_at) : null,
            'graded_at' => $row->graded_at ? Carbon::parse($row->graded_at) : null,
        ];
    }

    private function expectedSubmissions(int $teacherId, ?int $classId): int
    {
        $classRows = ClassModel::where('teacher_id', $teacherId)
            ->withCount('students')
            ->when($classId, fn ($query) => $query->whereKey($classId))
            ->get();

        $expected = 0;
        foreach ($classRows as $class) {
            $quizCount = Quiz::where('teacher_id', $teacherId)->where('class_id', $class->id)->count();
            $assignmentCount = Assignment::where('teacher_id', $teacherId)->where('class_id', $class->id)->count();
            $expected += $class->students_count * ($quizCount + $assignmentCount);
        }

        return $expected;
    }

    private function scoreByClass(Collection $classes, Collection $rows, ?int $classId): Collection
    {
        return $classes
            ->when($classId, fn ($items) => $items->where('id', $classId))
            ->map(function (ClassModel $class) use ($rows) {
                $classRows = $rows->where('class_id', $class->id);
                $gradedRows = $classRows->whereNotNull('percentage');
                $expected = $class->students_count * ($class->quizzes_count + $class->assignments_count);
                $submitted = $classRows->count();

                return [
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                    'student_count' => $class->students_count,
                    'submitted_count' => $submitted,
                    'graded_count' => $gradedRows->count(),
                    'avg_score' => $gradedRows->count() ? round($gradedRows->avg('percentage'), 1) : null,
                    'completion_rate' => $expected > 0 ? round(min(100, ($submitted / $expected) * 100), 1) : null,
                    'excellent_rate' => $gradedRows->count() ? round(($gradedRows->where('percentage', '>=', 80)->count() / $gradedRows->count()) * 100) : 0,
                    'weak_rate' => $gradedRows->count() ? round(($gradedRows->where('percentage', '<', 50)->count() / $gradedRows->count()) * 100) : 0,
                ];
            })
            ->sortByDesc(fn ($item) => $item['avg_score'] ?? -1)
            ->values();
    }

    private function scoreDistribution(Collection $gradedRows): array
    {
        $total = $gradedRows->count();
        $buckets = [
            ['label' => 'Xuất sắc (>= 80%)', 'min' => 80, 'max' => null, 'color' => '#16a34a'],
            ['label' => 'Khá (65-79%)', 'min' => 65, 'max' => 79.99, 'color' => '#2563eb'],
            ['label' => 'Trung bình (50-64%)', 'min' => 50, 'max' => 64.99, 'color' => '#f59e0b'],
            ['label' => 'Cần hỗ trợ (< 50%)', 'min' => null, 'max' => 49.99, 'color' => '#dc2626'],
        ];

        return collect($buckets)->map(function ($bucket) use ($gradedRows, $total) {
            $count = $gradedRows->filter(function ($row) use ($bucket) {
                return ($bucket['min'] === null || $row->percentage >= $bucket['min'])
                    && ($bucket['max'] === null || $row->percentage <= $bucket['max']);
            })->count();

            return [
                'label' => $bucket['label'],
                'count' => $count,
                'pct' => $total > 0 ? round(($count / $total) * 100) : 0,
                'color' => $bucket['color'],
            ];
        })->all();
    }

    private function topStudents(Collection $gradedRows): Collection
    {
        return $gradedRows
            ->groupBy('student_id')
            ->map(function (Collection $items) {
                $first = $items->first();

                return [
                    'id' => $first->student_id,
                    'name' => $first->student_name,
                    'email' => $first->student_email,
                    'avg' => round($items->avg('percentage'), 1),
                    'submitted' => $items->count(),
                ];
            })
            ->sortByDesc('avg')
            ->take(5)
            ->values();
    }

    private function atRiskStudents(Collection $rows): Collection
    {
        return $rows
            ->whereNotNull('percentage')
            ->groupBy('student_id')
            ->map(function (Collection $items) {
                $first = $items->first();

                return [
                    'name' => $first->student_name,
                    'email' => $first->student_email,
                    'avg' => round($items->avg('percentage'), 1),
                    'low_count' => $items->where('percentage', '<', 50)->count(),
                    'last_submitted_at' => $items->max('submitted_at'),
                ];
            })
            ->filter(fn ($student) => $student['avg'] < 50 || $student['low_count'] >= 2)
            ->sortBy('avg')
            ->take(5)
            ->values();
    }

    private function weeklyTrend(int $teacherId, ?int $classId): array
    {
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end = now()->subWeeks($i)->endOfWeek();
            $rows = $this->analyticsRows($teacherId, $start, $end, $classId)->whereNotNull('percentage');

            $trend[] = [
                'label' => $start->format('d/m'),
                'val' => $rows->count() ? round($rows->avg('percentage'), 1) : 0,
                'count' => $rows->count(),
            ];
        }

        return $trend;
    }

    private function activityTrend(int $teacherId, ?int $classId, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $trend = [];
        $endDate = $rangeEnd->copy()->endOfDay();
        $startDate = $rangeStart->copy()->startOfDay();

        if ($startDate->diffInDays($endDate) > 30) {
            $startDate = $endDate->copy()->subDays(30)->startOfDay();
        }

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $start = $date->copy()->startOfDay();
            $end = $date->copy()->endOfDay();
            $rows = $this->analyticsRows($teacherId, $start, $end, $classId);

            $trend[] = [
                'label' => $start->format('d/m'),
                'date' => $start->format('d/m/Y'),
                'count' => $rows->count(),
                'avg' => $rows->whereNotNull('percentage')->count()
                    ? round($rows->whereNotNull('percentage')->avg('percentage'), 1)
                    : null,
            ];
        }

        return $trend;
    }
}
