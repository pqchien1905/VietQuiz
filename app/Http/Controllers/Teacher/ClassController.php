<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Notification;
use App\Models\Quiz;
use App\Support\VipFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $classesQuery = $user->createdClasses()
            ->withCount('students')
            ->withCount(['quizzes as published_quizzes_count' => fn($q) => $q->where('status', 'published')])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('subject'), fn($q) => $q->where('subject', $request->subject))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->latest();

        $allClasses = $this->attachClassScores((clone $classesQuery)->get());
        $classes = (clone $classesQuery)->paginate(12)->withQueryString();
        $classes->setCollection($this->attachClassScores($classes->getCollection()));

        // Overall stats use the full filtered result, not only the current page.
        $totalStudents = $allClasses->sum('students_count');
        $totalQuizzes = $allClasses->sum('published_quizzes_count');
        $classesWithScores = $allClasses->filter(fn($c) => $c->avg_score !== null);
        $overallAvg = $classesWithScores->count() > 0 ? round($classesWithScores->avg('avg_score'), 1) : null;
        $activeCount = $allClasses->where('status', 'active')->count();
        $archivedCount = $allClasses->where('status', 'archived')->count();

        // Data for JS edit modal on the current page
        $classesData = $classes->getCollection()->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'subject' => $c->subject ?? '',
            'description' => $c->description ?? '',
            'grade_level' => $c->grade_level ?? '',
            'code' => $c->code,
            'status' => $c->status ?? 'active',
        ])->values()->all();

        $subjects = $user->createdClasses()
            ->whereNotNull('subject')
            ->where('subject', '!=', '')
            ->distinct()
            ->pluck('subject')
            ->sort()
            ->values();

        $openModal = session('open_modal');
        $editClassId = session('edit_class_id');

        return view('pages.teacher.classes', compact(
            'classes', 'classesData', 'totalStudents', 'totalQuizzes', 'overallAvg', 'subjects', 'openModal', 'editClassId', 'activeCount', 'archivedCount'
        ));
    }

    private function attachClassScores($classes)
    {
        $classIds = $classes->pluck('id')->toArray();
        $scoreRows = DB::table('quiz_user')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_user.quiz_id')
            ->whereIn('quizzes.class_id', $classIds)
            ->groupBy('quizzes.class_id')
            ->select(
                'quizzes.class_id',
                DB::raw('AVG((quiz_user.score / NULLIF(quiz_user.total_points, 0)) * 100) as avg_score'),
                DB::raw('COUNT(quiz_user.submitted_at) as submitted_count'),
                DB::raw('COUNT(*) as total_count')
            )
            ->get()
            ->keyBy('class_id');

        return $classes->map(function ($class) use ($scoreRows) {
            $raw = $scoreRows[$class->id] ?? null;
            $class->avg_score = $raw && $raw->avg_score ? round((float) $raw->avg_score, 1) : null;
            $class->submission_rate = $raw && $raw->total_count > 0
                ? round(($raw->submitted_count / $raw->total_count) * 100) : 0;
            return $class;
        });
    }

    public function show(Request $request, ClassModel $class)
    {
        $this->authorizeTeacher($request, $class);

        $class->load(['students', 'assignments']);

        $studentCount = $class->students()->count();

        // Per-student grade data
        $studentGrades = $class->students()
            ->with(['quizAttempts' => fn($q) => $q->wherePivot('submitted_at', '!=', null)])
            ->get()
            ->map(function ($student) {
                $attempts = $student->quizAttempts;
                $scores = $attempts
                    ->filter(fn($a) => $a->pivot->total_points > 0)
                    ->map(fn($a) => ($a->pivot->score / $a->pivot->total_points) * 100);

                $student->avg_pct = $scores->count() > 0 ? round($scores->avg(), 1) : null;
                $student->completed_count = $attempts->filter(fn($a) => $a->pivot->submitted_at)->count();
                $student->grade_letter = $this->pctToGrade($student->avg_pct);
                $student->last_attempt = $attempts->max('pivot.submitted_at');
                return $student;
            });

        // Quiz data for this class
        $quizzes = $class->quizzes()
            ->withCount(['attempts as submitted_count' => fn($q) => $q->whereNotNull('submitted_at')])
            ->withCount('questions')
            ->latest()
            ->get()
            ->map(function ($quiz) {
                $quiz->avg_score = DB::table('quiz_user')
                    ->where('quiz_id', $quiz->id)
                    ->whereNotNull('submitted_at')
                    ->avg(DB::raw('(score / NULLIF(total_points, 0)) * 100'));
                $quiz->avg_score = $quiz->avg_score ? round($quiz->avg_score, 1) : null;
                return $quiz;
            });

        // Class stats
        $classAvg = $studentGrades->where('avg_pct', '!=', null)->avg('avg_pct');
        $totalAssigned = $class->assignments->count();
        $submittedAssignments = DB::table('submissions')
            ->whereIn('assignment_id', $class->assignments->pluck('id'))
            ->count();
        $completionRate = $studentCount > 0 && $totalAssigned > 0
            ? round($submittedAssignments / ($studentCount * max($totalAssigned, 1)) * 100)
            : 0;

        // Top/weak students
        $sorted = $studentGrades->filter(fn($s) => $s->avg_pct !== null)->sortByDesc('avg_pct');
        $topStudents = $sorted->take(5)->values();
        $weakStudents = $sorted->filter(fn($s) => $s->avg_pct !== null && $s->avg_pct < 60)->take(5)->values();

        // Grade distribution
        $dist = [
            'excellent' => $studentGrades->filter(fn($s) => $s->avg_pct >= 90)->count(),
            'good' => $studentGrades->filter(fn($s) => $s->avg_pct >= 70 && $s->avg_pct < 90)->count(),
            'average' => $studentGrades->filter(fn($s) => $s->avg_pct >= 50 && $s->avg_pct < 70)->count(),
            'weak' => $studentGrades->filter(fn($s) => $s->avg_pct !== null && $s->avg_pct < 50)->count(),
        ];

        return view('pages.teacher.class-detail', compact(
            'class', 'studentCount', 'studentGrades',
            'quizzes', 'classAvg', 'completionRate',
            'topStudents', 'weakStudents', 'dist'
        ));
    }

    private function classAvgScore(int $classId): ?float
    {
        $avg = DB::table('quiz_user')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_user.quiz_id')
            ->where('quizzes.class_id', $classId)
            ->whereNotNull('quiz_user.submitted_at')
            ->avg(DB::raw('(quiz_user.score / NULLIF(quiz_user.total_points, 0)) * 100'));
        return $avg ? round($avg, 1) : null;
    }

    private function classSubmissionRate(int $classId): int
    {
        $total = DB::table('quiz_user')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_user.quiz_id')
            ->where('quizzes.class_id', $classId)
            ->count();
        $submitted = DB::table('quiz_user')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_user.quiz_id')
            ->where('quizzes.class_id', $classId)
            ->whereNotNull('quiz_user.submitted_at')
            ->count();
        return $total > 0 ? round(($submitted / $total) * 100) : 0;
    }

    public function store(Request $request)
    {
        if (!VipFeature::canCreateClass($request->user())) {
            return redirect()->route('teacher.classes')
                ->with('error', VipFeature::classLimitMessage());
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'subject'     => 'nullable|string|max:255',
            'grade_level' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return redirect()->route('teacher.classes')
                ->with('open_modal', 'create-modal')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $validated['teacher_id'] = $request->user()->id;
        $validated['code'] = strtoupper(Str::random(6));

        $class = ClassModel::create($validated);

        return redirect()->route('teacher.class-detail', $class)
            ->with('success', 'Tạo lớp thành công! Mã lớp: ' . $validated['code']);
    }

    public function update(Request $request, ClassModel $class)
    {
        $this->authorizeTeacher($request, $class);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'subject'     => 'nullable|string|max:255',
            'grade_level' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return redirect()->route('teacher.classes')
                ->with('open_modal', 'edit-modal')
                ->with('edit_class_id', $class->id)
                ->withErrors($validator)
                ->withInput();
        }

        $class->update($validator->validated());

        return redirect()->route('teacher.class-detail', $class)
            ->with('success', 'Cập nhật lớp thành công!');
    }

    public function destroy(Request $request, ClassModel $class)
    {
        $this->authorizeTeacher($request, $class);
        $class->delete();

        return redirect()->route('teacher.classes')
            ->with('success', 'Đã xóa lớp thành công!');
    }

    public function archive(Request $request, ClassModel $class)
    {
        $this->authorizeTeacher($request, $class);
        $class->update(['status' => 'archived']);

        return redirect()->route('teacher.classes')
            ->with('success', 'Đã lưu trữ lớp "' . $class->name . '"!');
    }

    public function restore(Request $request, ClassModel $class)
    {
        $this->authorizeTeacher($request, $class);
        $class->update(['status' => 'active']);

        return redirect()->route('teacher.classes')
            ->with('success', 'Đã khôi phục lớp "' . $class->name . '"!');
    }

    public function removeStudent(Request $request, ClassModel $class, $studentId)
    {
        $this->authorizeTeacher($request, $class);
        $class->students()->detach($studentId);

        return redirect()->route('teacher.class-detail', $class)
            ->with('success', 'Đã xóa học sinh khỏi lớp!');
    }

    public function sendNotification(Request $request, ClassModel $class)
    {
        $this->authorizeTeacher($request, $class);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'title'   => 'required|string|max:255',
            'body'     => 'required|string|max:500',
            'send_email' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('teacher.classes')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $students = $class->students()->get();
        $count = 0;

        foreach ($students as $student) {
            Notification::create([
                'user_id' => $student->id,
                'type'    => 'class_announcement',
                'title'   => $validated['title'],
                'body'    => $validated['body'],
                'data'    => json_encode([
                    'class_id'    => $class->id,
                    'teacher_id'  => $request->user()->id,
                ]),
            ]);
            $count++;
        }

        $msg = "Đã gửi thông báo cho $count học sinh.";

        return redirect()->route('teacher.class-detail', $class)
            ->with('success', $msg);
    }

    public function exportStudents(Request $request, ClassModel $class)
    {
        $this->authorizeTeacher($request, $class);
        if (!VipFeature::isVip($request->user())) {
            return back()->with('error', VipFeature::exportMessage());
        }

        $students = $class->students()->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Danh sách học sinh');

        $headers = ['name' => 'Tên', 'email' => 'Email'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $col++;
        }
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);

        $row = 2;
        foreach ($students as $s) {
            $sheet->setCellValue('A' . $row, $s->name);
            $sheet->setCellValue('B' . $row, $s->email);
            $row++;
        }

        foreach (['A', 'B'] as $col_letter) {
            $sheet->getColumnDimension($col_letter)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = "danh_sach_{$class->code}.xlsx";

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function downloadTemplate(Request $request, ClassModel $class)
    {
        $this->authorizeTeacher($request, $class);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mẫu thêm học sinh');

        $headers = ['name' => 'Tên', 'email' => 'Email'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $col++;
        }
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            'mau_them_hoc_sinh.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function importStudents(Request $request, ClassModel $class)
    {
        $this->authorizeTeacher($request, $class);

        $validated = $request->validate([
            'students_file' => 'required|file|mimes:csv,txt,xlsx|max:5120',
        ]);

        $file = $request->file('students_file');
        if (strtolower($file->getClientOriginalExtension()) === 'xlsx') {
            try {
                $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
                $rows = array_slice($sheet->toArray(null, true, true, false), 1);
            } catch (\Throwable) {
                return back()->with('error', 'Khong the doc file Excel. Vui long kiem tra lai dinh dang file.');
            }

            [$imported, $notFound, $skipped] = $this->importStudentRows($class, $rows);
            $msg = $this->importStudentsMessage($class, $imported, $notFound, $skipped);

            return redirect()->route('teacher.class-detail', $class)
                ->with($notFound > 0 ? 'warning' : 'success', $msg);
        }
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return back()->with('error', 'Không thể đọc file.');
        }

        $imported = 0;
        $notFound = 0;
        $skipped = 0;

        // Skip header row
        fgetcsv($handle, 0, ',');

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count($row) < 1 || empty(trim($row[0]))) {
                $skipped++;
                continue;
            }

            $name = trim($row[0]);
            $email = isset($row[1]) && trim($row[1]) ? trim($row[1]) : null;

            $user = null;
            if ($email) {
                $user = \App\Models\User::where('email', $email)->first();
            }
            if (!$user && $name) {
                $user = \App\Models\User::where('name', $name)->first();
            }

            if (!$user || $user->role !== 'student') {
                $notFound++;
                continue;
            }

            if (!$class->students()->where('users.id', $user->id)->exists()) {
                $class->students()->attach($user->id, ['joined_at' => now()]);
                $imported++;
            } else {
                $skipped++;
            }
        }
        fclose($handle);

        $msg = "Đã thêm {$imported} học sinh vào lớp {$class->name}.";
        if ($notFound > 0) {
            $msg .= " {$notFound} tài khoản không tìm thấy hoặc không phải học sinh.";
        }
        if ($skipped > 0) {
            $msg .= " {$skipped} tài khoản đã có trong lớp.";
        }

        return redirect()->route('teacher.class-detail', $class)
            ->with($notFound > 0 ? 'warning' : 'success', $msg);
    }

    private function importStudentRows(ClassModel $class, array $rows): array
    {
        $imported = 0;
        $notFound = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if (count($row) < 1 || empty(trim((string) $row[0]))) {
                $skipped++;
                continue;
            }

            $name = trim((string) $row[0]);
            $email = isset($row[1]) && trim((string) $row[1]) ? trim((string) $row[1]) : null;

            $user = null;
            if ($email) {
                $user = \App\Models\User::where('email', $email)->first();
            }
            if (!$user && $name) {
                $user = \App\Models\User::where('name', $name)->first();
            }

            if (!$user || $user->role !== 'student') {
                $notFound++;
                continue;
            }

            if (!$class->students()->where('users.id', $user->id)->exists()) {
                $class->students()->attach($user->id, ['joined_at' => now()]);
                $imported++;
            } else {
                $skipped++;
            }
        }

        return [$imported, $notFound, $skipped];
    }

    private function importStudentsMessage(ClassModel $class, int $imported, int $notFound, int $skipped): string
    {
        $msg = "Da them {$imported} hoc sinh vao lop {$class->name}.";
        if ($notFound > 0) {
            $msg .= " {$notFound} tai khoan khong tim thay hoac khong phai hoc sinh.";
        }
        if ($skipped > 0) {
            $msg .= " {$skipped} tai khoan da co trong lop.";
        }

        return $msg;
    }

    private function pctToGrade(?float $pct): string
    {
        if ($pct === null) return '—';
        if ($pct >= 90) return 'A';
        if ($pct >= 80) return 'B';
        if ($pct >= 70) return 'C';
        if ($pct >= 60) return 'D';
        return 'F';
    }

    private function authorizeTeacher(Request $request, ClassModel $class): void
    {
        abort_unless($class->teacher_id === $request->user()->id, 403);
    }
}
