<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Mail\GradePublished;
use App\Models\Grade;
use App\Models\Notification;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use App\Support\CollectionPaginator;
use App\Support\VipFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GradingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filters = [
            'status' => $request->query('status', 'pending'),
            'type' => $request->query('type', 'all'),
            'q' => trim((string) $request->query('q', '')),
        ];

        $items = $this->gradingItems($user->id);
        $allItems = $items;

        if (in_array($filters['status'], ['pending', 'graded'], true)) {
            $items = $items->filter(fn ($item) => $filters['status'] === 'graded' ? $item->is_graded : !$item->is_graded);
        }

        if (in_array($filters['type'], ['quiz', 'assignment'], true)) {
            $items = $items->filter(fn ($item) => $item->type === $filters['type']);
        }

        if ($filters['q'] !== '') {
            $search = mb_strtolower($filters['q']);
            $items = $items->filter(function ($item) use ($search) {
                return str_contains(mb_strtolower($item->student_name), $search)
                    || str_contains(mb_strtolower($item->student_email ?? ''), $search)
                    || str_contains(mb_strtolower($item->item_title), $search)
                    || str_contains(mb_strtolower($item->class_name ?? ''), $search)
                    || str_contains(mb_strtolower($item->course_name ?? ''), $search);
            });
        }

        $items = CollectionPaginator::make($items->sortByDesc('submitted_at')->values(), $request, 10);

        $summary = [
            'pending' => $allItems->where('is_graded', false)->count(),
            'graded' => $allItems->where('is_graded', true)->count(),
            'quiz' => $allItems->where('type', 'quiz')->count(),
            'assignment' => $allItems->where('type', 'assignment')->count(),
            'graded_today' => $allItems
                ->filter(fn ($item) => $item->is_graded && $item->graded_at && $item->graded_at->isToday())
                ->count(),
            'avg_pct' => $allItems->where('is_graded', true)->count() > 0
                ? round($allItems->where('is_graded', true)->avg('percentage'), 1)
                : null,
        ];

        return view('pages.teacher.grading', compact('items', 'allItems', 'summary', 'filters'));
    }

    public function storeGrade(Request $request)
    {
        $validated = $request->validate([
            'gradable_type' => 'required|in:quiz,assignment',
            'gradable_id' => 'required|integer',
            'student_id' => 'required|integer|exists:users,id',
            'score' => 'required|integer|min:0',
            'feedback' => 'nullable|string|max:3000',
        ]);

        [$gradableClass, $gradable, $maxScore, $itemTitle] = $this->resolveGradable($request, $validated);

        if ((float) $validated['score'] > (float) $maxScore) {
            return back()->withErrors(['score' => "Điểm không được lớn hơn {$maxScore}."])->withInput();
        }

        Grade::updateOrCreate(
            [
                'student_id' => (int) $validated['student_id'],
                'gradable_type' => $gradableClass,
                'gradable_id' => (int) $validated['gradable_id'],
            ],
            [
                'score' => (int) $validated['score'],
                'feedback' => $validated['feedback'] ?? null,
                'grader_id' => $request->user()->id,
                'graded_at' => now(),
            ]
        );

        if ($validated['gradable_type'] === 'quiz') {
            DB::table('quiz_user')
                ->where('quiz_id', $validated['gradable_id'])
                ->where('user_id', $validated['student_id'])
                ->update(['is_graded' => true, 'score' => (int) $validated['score']]);
        }

        $this->notifyStudent(
            (int) $validated['student_id'],
            $request->user()->id,
            $validated['gradable_type'],
            $itemTitle,
            (float) $validated['score'],
            (float) $maxScore,
            (int) $validated['gradable_id']
        );

        return back()->with('success', 'Đã lưu điểm thành công.');
    }

    public function inlineSubmissionAttachment(Request $request, Submission $submission)
    {
        $this->authorizeSubmission($request, $submission);
        abort_unless($submission->attachment && Storage::exists($submission->attachment), 404);

        return response()->file(Storage::path($submission->attachment), [
            'Content-Type' => Storage::mimeType($submission->attachment) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes(basename($submission->attachment)) . '"',
        ]);
    }

    public function downloadSubmissionAttachment(Request $request, Submission $submission)
    {
        $this->authorizeSubmission($request, $submission);
        abort_unless($submission->attachment && Storage::exists($submission->attachment), 404);

        return Storage::download($submission->attachment, basename($submission->attachment));
    }

    public function exportGrades(Request $request)
    {
        if (!VipFeature::isVip($request->user())) {
            return back()->with('error', VipFeature::exportMessage());
        }

        $items = $this->gradingItems($request->user()->id)->sortByDesc('submitted_at')->values();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bang diem');

        $headers = [
            'Loại',
            'Học sinh',
            'Email',
            'Lớp/Khóa học',
            'Bài',
            'Điểm',
            'Tối đa',
            'Phần trăm',
            'Trạng thái',
            'Ngày nộp',
            'Ngày chấm',
            'Nhận xét',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $rowNumber = 2;

        foreach ($items as $item) {
            $sheet->setCellValue("A{$rowNumber}", $item->type === 'quiz' ? 'Bài kiểm tra' : 'Bài tập');
            $sheet->setCellValue("B{$rowNumber}", $item->student_name);
            $sheet->setCellValueExplicit("C{$rowNumber}", (string) ($item->student_email ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("D{$rowNumber}", trim(($item->class_name ?? '') . (($item->course_name ?? '') ? ' / ' . $item->course_name : '')));
            $sheet->setCellValue("E{$rowNumber}", $item->item_title);
            $sheet->setCellValue("F{$rowNumber}", $item->score);
            $sheet->setCellValue("G{$rowNumber}", $item->max_score);
            $sheet->setCellValue("H{$rowNumber}", $item->percentage !== null ? $item->percentage / 100 : null);
            $sheet->setCellValue("I{$rowNumber}", $item->is_graded ? 'Đã chấm' : 'Chờ chấm');
            $sheet->setCellValue("J{$rowNumber}", optional($item->submitted_at)->format('d/m/Y H:i'));
            $sheet->setCellValue("K{$rowNumber}", optional($item->graded_at)->format('d/m/Y H:i'));
            $sheet->setCellValue("L{$rowNumber}", $item->feedback);
            $rowNumber++;
        }

        $lastRow = max(1, $rowNumber - 1);
        $sheet->getStyle("A1:L{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A1:L{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle("H2:H{$lastRow}")->getNumberFormat()->setFormatCode('0.0%');
        $sheet->getStyle("L2:L{$lastRow}")->getAlignment()->setWrapText(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:L{$lastRow}");

        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        Storage::makeDirectory('exports');
        $filename = 'bang_diem_' . now()->format('Ymd_His') . '.xlsx';
        $path = Storage::path('exports/' . $filename);

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()->download(
            $path,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    private function gradingItems(int $teacherId): Collection
    {
        $quizItems = $this->quizItems($teacherId);
        $assignmentItems = $this->assignmentItems($teacherId);

        return $quizItems->merge($assignmentItems)->values();
    }

    private function quizItems(int $teacherId): Collection
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
            ->select([
                'quiz_user.user_id as student_id',
                'users.name as student_name',
                'users.email as student_email',
                'quizzes.id as item_id',
                'quizzes.title as item_title',
                'quizzes.total_points as quiz_max_score',
                'quiz_user.total_points as attempt_max_score',
                'quiz_user.score as attempt_score',
                'quiz_user.is_graded',
                'quiz_user.answers',
                'quiz_user.submitted_at',
                'classes.name as class_name',
                'courses.name as course_name',
                'grades.score as manual_score',
                'grades.feedback',
                'grades.graded_at',
            ])
            ->get()
            ->map(function ($row) {
                $score = $row->manual_score ?? $row->attempt_score;
                $maxScore = $row->attempt_max_score ?: ($row->quiz_max_score ?: 10);

                return (object) [
                    'key' => 'quiz-' . $row->item_id . '-' . $row->student_id,
                    'type' => 'quiz',
                    'gradable_type' => 'quiz',
                    'gradable_id' => (int) $row->item_id,
                    'student_id' => (int) $row->student_id,
                    'student_name' => $row->student_name,
                    'student_email' => $row->student_email,
                    'item_title' => $row->item_title,
                    'class_name' => $row->class_name,
                    'course_name' => $row->course_name,
                    'max_score' => (float) $maxScore,
                    'score' => $score !== null ? (float) $score : null,
                    'percentage' => $score !== null && $maxScore > 0 ? round(((float) $score / (float) $maxScore) * 100, 1) : null,
                    'is_graded' => (bool) $row->is_graded || $row->manual_score !== null,
                    'submitted_at' => $row->submitted_at ? \Carbon\Carbon::parse($row->submitted_at) : null,
                    'graded_at' => $row->graded_at ? \Carbon\Carbon::parse($row->graded_at) : null,
                    'feedback' => $row->feedback,
                    'content' => $this->formatQuizAnswers($row->answers),
                    'attachment' => null,
                ];
            });
    }

    private function assignmentItems(int $teacherId): Collection
    {
        return Submission::query()
            ->with([
                'student:id,name,email',
                'assignment:id,teacher_id,class_id,course_id,title,total_points,type,due_at',
                'assignment.class:id,name',
                'assignment.course:id,name',
                'grades' => fn ($query) => $query->latest('graded_at'),
            ])
            ->whereHas('assignment', fn ($query) => $query->where('teacher_id', $teacherId))
            ->latest('submitted_at')
            ->get()
            ->map(function (Submission $submission) {
                $grade = $submission->grades->first();
                $maxScore = $submission->assignment?->total_points ?: 100;
                $score = $grade?->score;

                return (object) [
                    'key' => 'assignment-' . $submission->id,
                    'type' => 'assignment',
                    'gradable_type' => 'assignment',
                    'gradable_id' => $submission->id,
                    'student_id' => $submission->student_id,
                    'student_name' => $submission->student?->name ?? 'Học sinh',
                    'student_email' => $submission->student?->email,
                    'item_title' => $submission->assignment?->title ?? 'Bài tập',
                    'class_name' => $submission->assignment?->class?->name,
                    'course_name' => $submission->assignment?->course?->name,
                    'max_score' => (float) $maxScore,
                    'score' => $score !== null ? (float) $score : null,
                    'percentage' => $score !== null && $maxScore > 0 ? round(((float) $score / (float) $maxScore) * 100, 1) : null,
                    'is_graded' => $grade !== null,
                    'submitted_at' => $submission->submitted_at,
                    'graded_at' => $grade?->graded_at,
                    'feedback' => $grade?->feedback,
                    'content' => $submission->content,
                    'attachment' => $submission->attachment,
                ];
            });
    }

    private function resolveGradable(Request $request, array $validated): array
    {
        if ($validated['gradable_type'] === 'quiz') {
            $quiz = Quiz::whereKey($validated['gradable_id'])
                ->where('teacher_id', $request->user()->id)
                ->firstOrFail();

            $attempt = DB::table('quiz_user')
                ->where('quiz_id', $quiz->id)
                ->where('user_id', $validated['student_id'])
                ->whereNotNull('submitted_at')
                ->first();
            abort_unless($attempt !== null, 404);

            return [Quiz::class, $quiz, $attempt->total_points ?: ($quiz->total_points ?: 10), $quiz->title];
        }

        $submission = Submission::with('assignment')
            ->whereKey($validated['gradable_id'])
            ->where('student_id', $validated['student_id'])
            ->whereHas('assignment', fn ($query) => $query->where('teacher_id', $request->user()->id))
            ->firstOrFail();

        return [Submission::class, $submission, $submission->assignment?->total_points ?: 100, $submission->assignment?->title ?? 'Bài tập'];
    }

    private function authorizeSubmission(Request $request, Submission $submission): void
    {
        abort_unless($submission->assignment?->teacher_id === $request->user()->id, 403);
    }

    private function notifyStudent(int $studentId, int $teacherId, string $type, string $itemTitle, float $score, float $totalPoints, int $gradableId): void
    {
        $pct = $totalPoints > 0 ? round(($score / $totalPoints) * 100) : 0;
        $title = $type === 'quiz' ? 'Bài kiểm tra đã được chấm điểm' : 'Bài tập đã được chấm điểm';
        $body = "{$itemTitle}: {$score}/{$totalPoints} điểm ({$pct}%).";

        Notification::create([
            'user_id' => $studentId,
            'type' => 'grade_published',
            'title' => $title,
            'body' => $body,
            'data' => [
                'gradable_type' => $type,
                'gradable_id' => $gradableId,
                'score' => $score,
                'total_points' => $totalPoints,
                'pct' => $pct,
                'grader_id' => $teacherId,
            ],
        ]);

        $student = User::find($studentId);
        if ($student?->email) {
            try {
                Mail::to($student->email)->queue(new GradePublished($student->name, $itemTitle, $score, $totalPoints, $pct, $type));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    private function formatQuizAnswers(?string $answers): ?string
    {
        if (!$answers) {
            return null;
        }

        $decoded = json_decode($answers, true);
        if (!is_array($decoded)) {
            return $answers;
        }

        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

}
