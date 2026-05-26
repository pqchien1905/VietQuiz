<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Quiz;
use App\Models\User;
use App\Support\CollectionPaginator;
use App\Support\VipFeature;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $teacher = $request->user();
        $classes = $teacher->createdClasses()->orderBy('name')->get();

        if ($request->filled('class_id') && ! $classes->contains('id', (int) $request->input('class_id'))) {
            abort(403);
        }

        $filteredStudents = $this->studentsForRequest($request, $teacher);
        $allStudents = CollectionPaginator::make($filteredStudents, $request, 15);
        $allForStats = $this->studentsForRequest(new Request, $teacher);

        $stats = [
            'total' => $allForStats->count(),
            'good' => $allForStats->filter(fn ($s) => $s->avg_score !== null && $s->avg_score >= 8)->count(),
            'ok' => $allForStats->filter(fn ($s) => $s->avg_score !== null && $s->avg_score >= 6 && $s->avg_score < 8)->count(),
            'weak' => $allForStats->filter(fn ($s) => $s->avg_score === null || $s->avg_score < 6)->count(),
        ];

        $studentsData = $allStudents->getCollection()->map(function ($student) {
            $recentAttempts = $student->quizAttempts
                ->sortByDesc('pivot.submitted_at')
                ->take(5)
                ->map(fn ($attempt) => [
                    'title' => $attempt->title,
                    'score' => $attempt->pivot->total_points > 0
                        ? round(($attempt->pivot->score / $attempt->pivot->total_points) * 10, 1)
                        : null,
                    'date' => $attempt->pivot->submitted_at
                        ? Carbon::parse($attempt->pivot->submitted_at)->format('d/m/Y')
                        : '—',
                ])->values();

            return [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'avg' => $student->avg_score,
                'submitted' => $student->submissions_count,
                'total_assignments' => $student->total_assignments,
                'joined_at' => $student->joined_at
                    ? Carbon::parse($student->joined_at)->format('d/m/Y')
                    : null,
                'classes' => $student->classes->map(fn ($class) => [
                    'id' => $class->id,
                    'name' => $class->name,
                ])->values(),
                'grades' => $recentAttempts,
            ];
        })->values();

        return view('pages.teacher.students', compact(
            'allStudents',
            'classes',
            'studentsData',
            'stats',
        ));
    }

    public function export(Request $request)
    {
        if (! VipFeature::isVip($request->user())) {
            return back()->with('error', VipFeature::exportMessage());
        }

        $teacher = $request->user();
        $classes = $teacher->createdClasses()->orderBy('name')->get();

        if ($request->filled('class_id') && ! $classes->contains('id', (int) $request->input('class_id'))) {
            abort(403);
        }

        $students = $this->studentsForRequest($request, $teacher);
        $filename = 'danh-sach-hoc-sinh-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($students) {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Danh sách học sinh');

            $headers = ['Họ tên', 'Email', 'Lớp', 'Điểm TB', 'Bài đã nộp', 'Tổng bài', 'Hoàn thành (%)', 'Xếp loại', 'Ngày tham gia'];
            $sheet->fromArray($headers, null, 'A1');

            $row = 2;
            foreach ($students as $student) {
                $percent = $student->total_assignments > 0
                    ? min(100, (int) round(($student->submissions_count / $student->total_assignments) * 100))
                    : 0;

                $sheet->fromArray([
                    $student->name,
                    $student->email,
                    $student->classes->pluck('name')->join(', '),
                    $student->avg_score !== null ? (float) number_format($student->avg_score, 1, '.', '') : null,
                    $student->submissions_count,
                    $student->total_assignments,
                    $percent / 100,
                    $this->rankLabel($student->avg_score),
                    $student->joined_at ? Carbon::parse($student->joined_at)->format('d/m/Y') : '',
                ], null, "A{$row}");

                $row++;
            }

            $lastRow = max(1, $row - 1);
            $sheet->getStyle('A1:I1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getStyle("A1:I{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ]);
            $sheet->getStyle("D2:D{$lastRow}")->getNumberFormat()->setFormatCode('0.0');
            $sheet->getStyle("G2:G{$lastRow}")->getNumberFormat()->setFormatCode('0%');
            $sheet->getStyle("A1:I{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->freezePane('A2');

            foreach (range('A', 'I') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function inviteByEmail(Request $request)
    {
        $emailList = [];

        if ($request->has('emails') && is_array($request->emails)) {
            $emailList = $request->emails;
        } elseif ($request->filled('emails_raw')) {
            $emailList = preg_split('/[\r\n,;]+/', $request->input('emails_raw'));
        }

        $emailList = collect($emailList)
            ->map(fn ($email) => trim(strtolower((string) $email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        if ($emailList->isEmpty()) {
            return back()->with('error', 'Vui lòng nhập ít nhất một email hợp lệ.');
        }

        $validated = $request->validate([
            'emails_raw' => 'nullable|string',
            'class_id' => ['required', 'integer', Rule::exists('classes', 'id')->where('teacher_id', $request->user()->id)],
        ]);

        $class = ClassModel::findOrFail($validated['class_id']);
        abort_unless($class->teacher_id === $request->user()->id, 403);

        $added = 0;
        $alreadyInClass = 0;
        $notFound = [];

        foreach ($emailList as $email) {
            $student = User::where('email', $email)->where('role', 'student')->first();

            if (! $student) {
                $notFound[] = $email;

                continue;
            }

            $existingEnrollment = \Illuminate\Support\Facades\DB::table('class_user')
                ->where('class_id', $class->id)
                ->where('user_id', $student->id)
                ->first();

            if ($existingEnrollment && $existingEnrollment->enrollment_status === 'approved') {
                $alreadyInClass++;

                continue;
            }

            if ($existingEnrollment) {
                \Illuminate\Support\Facades\DB::table('class_user')
                    ->where('class_id', $class->id)
                    ->where('user_id', $student->id)
                    ->update([
                        'enrollment_status' => 'approved',
                        'enrollment_source' => 'teacher_invite',
                        'approved_at' => now(),
                        'joined_at' => now(),
                    ]);
            } else {
                $class->studentEnrollments()->attach($student->id, [
                    'joined_at' => now(),
                    'enrollment_status' => 'approved',
                    'enrollment_source' => 'teacher_invite',
                    'approved_at' => now(),
                ]);
            }
            $added++;
        }

        $message = "Đã thêm {$added} học sinh vào lớp {$class->name}.";

        if ($alreadyInClass > 0) {
            $message .= " {$alreadyInClass} học sinh đã có trong lớp.";
        }

        if (count($notFound) > 0) {
            $message .= ' '.count($notFound).' email không tìm thấy tài khoản học sinh.';

            return back()->with('warning', $message);
        }

        return back()->with('success', $message);
    }

    public function inviteByLink(Request $request, ClassModel $class)
    {
        abort_unless($class->teacher_id === $request->user()->id, 403);

        do {
            $code = strtoupper(Str::random(6));
        } while (ClassModel::where('code', $code)->whereKeyNot($class->id)->exists());

        $class->update(['code' => $code]);

        return back()->with('success', "Đã tạo mã tham gia mới cho lớp {$class->name}.");
    }

    public function removeStudent(Request $request)
    {
        $validated = $request->validate([
            'class_id' => ['required', 'integer', Rule::exists('classes', 'id')->where('teacher_id', $request->user()->id)],
            'student_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'student')],
        ]);

        $class = ClassModel::findOrFail($validated['class_id']);
        abort_unless($class->teacher_id === $request->user()->id, 403);

        $class->students()->detach($validated['student_id']);

        return redirect()
            ->route('teacher.students')
            ->with('success', 'Đã gỡ học sinh khỏi lớp.');
    }

    private function studentsForRequest(Request $request, User $teacher)
    {
        $query = User::where('role', 'student')
            ->whereHas('classes', fn ($query) => $query->where('classes.teacher_id', $teacher->id))
            ->with([
                'classes' => fn ($query) => $query->where('classes.teacher_id', $teacher->id),
                'quizAttempts' => fn ($query) => $query
                    ->where('quizzes.teacher_id', $teacher->id)
                    ->whereNotNull('quiz_user.submitted_at'),
            ]);

        if ($search = $request->input('search')) {
            $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        if ($classId = $request->input('class_id')) {
            $query->whereHas('classes', fn ($query) => $query->where('classes.id', $classId));
        }

        $students = $query->latest()->get()->map(function ($student) use ($teacher) {
            $scores = $student->quizAttempts
                ->filter(fn ($attempt) => $attempt->pivot->total_points > 0)
                ->map(fn ($attempt) => ($attempt->pivot->score / $attempt->pivot->total_points) * 10);

            $student->avg_score = $scores->count() > 0 ? round($scores->avg(), 1) : null;
            $student->submissions_count = $student->quizAttempts->count();
            $student->total_assignments = Quiz::where('teacher_id', $teacher->id)
                ->whereIn('class_id', $student->classes->pluck('id'))
                ->where('status', 'published')
                ->count();
            $student->joined_at = $student->classes
                ->filter(fn ($class) => $class->pivot->joined_at)
                ->sortBy('pivot.joined_at')
                ->first()?->pivot->joined_at;

            return $student;
        });

        if ($perf = $request->input('perf')) {
            $students = $students->filter(function ($student) use ($perf) {
                $avg = $student->avg_score;

                if ($avg === null) {
                    return $perf === 'weak';
                }

                return match ($perf) {
                    'good' => $avg >= 8,
                    'ok' => $avg >= 6 && $avg < 8,
                    'weak' => $avg < 6,
                    default => true,
                };
            })->values();
        }

        return $students;
    }

    private function rankLabel(?float $avg): string
    {
        if ($avg === null) {
            return 'Chưa có điểm';
        }

        return match (true) {
            $avg >= 8 => 'Giỏi',
            $avg >= 6 => 'Khá',
            $avg >= 5 => 'Trung bình',
            default => 'Yếu',
        };
    }
}
