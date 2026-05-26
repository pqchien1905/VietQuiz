<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Notification;
use App\Models\Promotion;
use App\Models\Question;
use App\Models\QuestionFolder;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VipPayment;
use App\Models\VipPlan;
use App\Models\VipSubscription;
use App\Services\QuizAssignmentScopeValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class AdminBaseController extends Controller
{
    public const SESSION_KEY = 'vietquiz_admin_authenticated';

    protected function adminAnalyticsPayload(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse((string) $request->query('from'))->startOfDay()
            : now()->subDays(30)->startOfDay();
        $to = $request->filled('to')
            ? Carbon::parse((string) $request->query('to'))->endOfDay()
            : now()->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $overview = [
            'users' => User::withTrashed()->count(),
            'teachers' => User::where('role', 'teacher')->count(),
            'students' => User::where('role', 'student')->count(),
            'classes' => ClassModel::withTrashed()->count(),
            'courses' => Course::withTrashed()->count(),
            'quizzes' => Quiz::withTrashed()->count(),
            'questions' => Question::withTrashed()->count(),
            'assignments' => Assignment::withTrashed()->count(),
            'submissions' => Submission::count(),
            'grades' => Grade::count(),
            'tickets' => Ticket::count(),
            'vip' => VipSubscription::where('status', 'active')->count(),
            'revenue' => (float) VipPayment::where('status', 'paid')->sum('amount'),
            'avg_score' => round((float) Grade::avg('score'), 2),
        ];

        $periodStats = [
            'users' => User::whereBetween('created_at', [$from, $to])->count(),
            'classes' => ClassModel::whereBetween('created_at', [$from, $to])->count(),
            'courses' => Course::whereBetween('created_at', [$from, $to])->count(),
            'quizzes' => Quiz::whereBetween('created_at', [$from, $to])->count(),
            'questions' => Question::whereBetween('created_at', [$from, $to])->count(),
            'assignments' => Assignment::whereBetween('created_at', [$from, $to])->count(),
            'submissions' => Submission::whereBetween('submitted_at', [$from, $to])->count(),
            'tickets' => Ticket::whereBetween('created_at', [$from, $to])->count(),
            'revenue' => (float) VipPayment::where('status', 'paid')->whereBetween('paid_at', [$from, $to])->sum('amount'),
        ];

        $monthly = collect(range(11, 0))->map(function (int $monthsAgo) {
            $month = now()->subMonths($monthsAgo)->startOfMonth();
            $end = $month->copy()->endOfMonth();

            return [
                'label' => $month->format('m/Y'),
                'users' => User::whereBetween('created_at', [$month, $end])->count(),
                'quizzes' => Quiz::whereBetween('created_at', [$month, $end])->count(),
                'submissions' => Submission::whereBetween('submitted_at', [$month, $end])->count(),
                'revenue' => (float) VipPayment::where('status', 'paid')->whereBetween('paid_at', [$month, $end])->sum('amount'),
            ];
        })->values()->all();

        $roleDistribution = collect(['admin', 'teacher', 'student'])->map(fn (string $role) => [
            'label' => \App\Support\AdminLabels::role($role),
            'value' => User::where('role', $role)->count(),
        ])->values()->all();

        $quizStatus = collect(['draft', 'published', 'closed'])->map(fn (string $status) => [
            'label' => \App\Support\AdminLabels::status($status),
            'value' => Quiz::where('status', $status)->count(),
        ])->values()->all();

        $ticketStatus = Ticket::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => \App\Support\AdminLabels::status((string) $row->status),
                'value' => (int) $row->total,
            ])->values()->all();

        $paymentStatus = VipPayment::query()
            ->select('status', DB::raw('count(*) as total'), DB::raw('coalesce(sum(amount), 0) as amount'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'status' => \App\Support\AdminLabels::status((string) $row->status),
                'count' => (int) $row->total,
                'amount' => (float) $row->amount,
            ])->values()->all();

        $learning = [
            'published_quizzes' => Quiz::where('status', 'published')->count(),
            'draft_quizzes' => Quiz::where('status', 'draft')->count(),
            'closed_quizzes' => Quiz::where('status', 'closed')->count(),
            'quiz_attempts' => DB::table('quiz_user')->count(),
            'submitted_attempts' => DB::table('quiz_user')->whereNotNull('submitted_at')->count(),
            'ungraded_attempts' => DB::table('quiz_user')->whereNotNull('submitted_at')->where('is_graded', false)->count(),
            'overdue_assignments' => Assignment::whereNotNull('due_at')->where('due_at', '<', now())->count(),
            'ungraded_submissions' => Submission::whereDoesntHave('grades')->count(),
        ];

        $topTeachers = User::where('role', 'teacher')
            ->withCount(['createdClasses', 'createdCourses', 'quizzes', 'assignments'])
            ->orderByDesc('quizzes_count')
            ->orderByDesc('assignments_count')
            ->limit(10)
            ->get(['id', 'name', 'email']);

        $topCourses = Course::with('teacher')
            ->withCount(['students', 'quizzes', 'assignments'])
            ->orderByDesc('students_count')
            ->limit(8)
            ->get();

        $recentActivity = collect([
            ['label' => 'Người dùng mới', 'value' => User::latest()->limit(1)->value('created_at')],
            ['label' => 'Quiz mới', 'value' => Quiz::latest()->limit(1)->value('created_at')],
            ['label' => 'Bài nộp mới', 'value' => Submission::latest()->limit(1)->value('submitted_at')],
            ['label' => 'Thanh toán VIP mới', 'value' => VipPayment::where('status', 'paid')->latest('paid_at')->limit(1)->value('paid_at')],
        ])->map(fn ($row) => [
            'label' => $row['label'],
            'value' => $row['value'] ? Carbon::parse($row['value'])->diffForHumans() : 'Chưa có dữ liệu',
        ])->all();

        return compact(
            'from',
            'to',
            'overview',
            'periodStats',
            'monthly',
            'roleDistribution',
            'quizStatus',
            'ticketStatus',
            'paymentStatus',
            'learning',
            'topTeachers',
            'topCourses',
            'recentActivity',
        );
    }

    protected function analyticsExportSheets(array $data): array
    {
        return [
            [
                'title' => 'Thông tin báo cáo',
                'rows' => [
                    ['Mục', 'Giá trị'],
                    ['Tên báo cáo', 'Thống kê chi tiết hệ thống VietQuiz'],
                    ['Khoảng thời gian', $data['from']->format('d/m/Y') . ' - ' . $data['to']->format('d/m/Y')],
                    ['Thời điểm xuất', now()->format('d/m/Y H:i:s')],
                ],
            ],
            [
                'title' => 'Tổng quan',
                'rows' => [
                    ['Chỉ số', 'Tổng hiện tại', 'Phát sinh trong kỳ'],
                    ['Người dùng', $data['overview']['users'], $data['periodStats']['users']],
                    ['Giáo viên', $data['overview']['teachers'], ''],
                    ['Học sinh', $data['overview']['students'], ''],
                    ['Lớp học', $data['overview']['classes'], $data['periodStats']['classes']],
                    ['Khóa học', $data['overview']['courses'], $data['periodStats']['courses']],
                    ['Bài kiểm tra', $data['overview']['quizzes'], $data['periodStats']['quizzes']],
                    ['Câu hỏi', $data['overview']['questions'], $data['periodStats']['questions']],
                    ['Bài tập', $data['overview']['assignments'], $data['periodStats']['assignments']],
                    ['Bài nộp', $data['overview']['submissions'], $data['periodStats']['submissions']],
                    ['Điểm đã ghi', $data['overview']['grades'], ''],
                    ['Điểm trung bình', $data['overview']['avg_score'], ''],
                    ['Ticket hỗ trợ', $data['overview']['tickets'], $data['periodStats']['tickets']],
                    ['VIP active', $data['overview']['vip'], ''],
                    ['Doanh thu VIP', $data['overview']['revenue'], $data['periodStats']['revenue']],
                ],
            ],
            [
                'title' => 'Xu hướng 12 tháng',
                'rows' => array_merge(
                    [['Tháng', 'Người dùng mới', 'Quiz mới', 'Bài nộp', 'Doanh thu VIP']],
                    collect($data['monthly'])->map(fn ($row) => [
                        $row['label'], $row['users'], $row['quizzes'], $row['submissions'], $row['revenue'],
                    ])->all()
                ),
            ],
            [
                'title' => 'Cơ cấu người dùng',
                'rows' => array_merge(
                    [['Vai trò', 'Số lượng']],
                    collect($data['roleDistribution'])->map(fn ($row) => [$row['label'], $row['value']])->all()
                ),
            ],
            [
                'title' => 'Trạng thái quiz',
                'rows' => array_merge(
                    [['Trạng thái', 'Số lượng']],
                    collect($data['quizStatus'])->map(fn ($row) => [$row['label'], $row['value']])->all()
                ),
            ],
            [
                'title' => 'Trạng thái hỗ trợ',
                'rows' => array_merge(
                    [['Trạng thái', 'Số lượng']],
                    collect($data['ticketStatus'])->map(fn ($row) => [$row['label'], $row['value']])->all()
                ),
            ],
            [
                'title' => 'Học tập chấm điểm',
                'rows' => [
                    ['Chỉ số', 'Giá trị'],
                    ['Quiz đã xuất bản', $data['learning']['published_quizzes']],
                    ['Quiz nháp', $data['learning']['draft_quizzes']],
                    ['Quiz đã đóng', $data['learning']['closed_quizzes']],
                    ['Lượt làm quiz', $data['learning']['quiz_attempts']],
                    ['Lượt đã nộp quiz', $data['learning']['submitted_attempts']],
                    ['Lượt quiz chưa chấm', $data['learning']['ungraded_attempts']],
                    ['Bài tập quá hạn', $data['learning']['overdue_assignments']],
                    ['Bài nộp chưa chấm', $data['learning']['ungraded_submissions']],
                ],
            ],
            [
                'title' => 'Giáo viên nổi bật',
                'rows' => array_merge(
                    [['Giáo viên', 'Email', 'Lớp', 'Khóa', 'Quiz', 'Bài tập']],
                    collect($data['topTeachers'])->map(fn ($teacher) => [
                        $teacher->name,
                        $teacher->email,
                        $teacher->created_classes_count,
                        $teacher->created_courses_count,
                        $teacher->quizzes_count,
                        $teacher->assignments_count,
                    ])->all()
                ),
            ],
            [
                'title' => 'Khóa học nhiều học viên',
                'rows' => array_merge(
                    [['Khóa học', 'Giáo viên', 'Học viên', 'Quiz', 'Bài tập']],
                    collect($data['topCourses'])->map(fn ($course) => [
                        $course->name,
                        $course->teacher?->name ?? '',
                        $course->students_count,
                        $course->quizzes_count,
                        $course->assignments_count,
                    ])->all()
                ),
            ],
            [
                'title' => 'VIP thanh toán',
                'rows' => array_merge(
                    [['Trạng thái', 'Số lượng', 'Tổng tiền']],
                    collect($data['paymentStatus'])->map(fn ($row) => [
                        $row['status'], $row['count'], $row['amount'],
                    ])->all()
                ),
            ],
            [
                'title' => 'Hoạt động mới nhất',
                'rows' => array_merge(
                    [['Nhóm dữ liệu', 'Mốc gần nhất']],
                    collect($data['recentActivity'])->map(fn ($row) => [$row['label'], $row['value']])->all()
                ),
            ],
        ];
    }

    protected function writeSheet($sheet, string $title, array $rows): void
    {
        $sheet->setTitle(mb_substr($title, 0, 31));
        $sheet->fromArray($rows, null, 'A1');
        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$highestColumn}1")->getFont()->setBold(true);
        foreach (range('A', $highestColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    protected function generateClassCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (ClassModel::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    protected function validateAdminQuiz(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        $validated = $request->validate([
            'teacher_id' => [$required, 'exists:users,id'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'title' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'time_limit' => ['nullable', 'integer', 'min:1', 'max:600'],
            'total_points' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'passing_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:1'],
            'status' => [$required, 'in:draft,published,closed'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'quiz_type' => ['nullable', Rule::in(['exam', 'practice'])],
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_answers' => ['nullable', 'boolean'],
            'is_shuffle' => ['nullable', 'boolean'],
            'show_result' => ['nullable', 'boolean'],
            'anti_cheat_enabled' => ['nullable', 'boolean'],
            'assigned_students' => ['nullable', 'array'],
            'assigned_students.*' => ['integer', Rule::exists('users', 'id')->where('role', 'student')],
            'public_to_all_students' => ['nullable', 'boolean'],
        ]);

        foreach (['shuffle_questions', 'shuffle_answers', 'is_shuffle', 'show_result', 'anti_cheat_enabled', 'public_to_all_students'] as $booleanField) {
            if ($request->has($booleanField)) {
                $validated[$booleanField] = $request->boolean($booleanField);
            } elseif (! $partial) {
                $validated[$booleanField] = false;
            }
        }

        if (! $partial) {
            $validated['duration_minutes'] = $validated['duration_minutes'] ?? 60;
            $validated['total_points'] = $validated['total_points'] ?? 100;
            $validated['passing_score'] = $validated['passing_score'] ?? 50;
            $validated['max_attempts'] = $validated['max_attempts'] ?? 1;
            $validated['quiz_type'] = $validated['quiz_type'] ?? 'exam';
            $validated['show_result'] = $request->has('show_result') ? $validated['show_result'] : true;
        }

        if (array_key_exists('quiz_type', $validated) && $validated['quiz_type'] === 'practice') {
            $validated['anti_cheat_enabled'] = false;
        }

        if (! $partial || array_key_exists('duration_minutes', $validated) || array_key_exists('time_limit', $validated)) {
            $validated['time_limit'] = $validated['time_limit'] ?? ($validated['duration_minutes'] ?? null);
        }

        if (! $partial || array_key_exists('shuffle_questions', $validated) || array_key_exists('is_shuffle', $validated)) {
            $validated['is_shuffle'] = $validated['is_shuffle'] ?? ($validated['shuffle_questions'] ?? false);
        }

        if (($validated['status'] ?? null) === 'published'
            && empty($validated['class_id'])
            && empty($validated['course_id'])
            && empty($validated['assigned_students'])
            && empty($validated['public_to_all_students'])) {
            abort(422, 'Vui lòng chọn lớp học, khóa học hoặc bật công khai cho tất cả học sinh.');
        }

        return $validated;
    }

    protected function ensureQuizTeacherScope(array $data): void
    {
        if (! empty($data['teacher_id'])) {
            abort_unless(User::whereKey($data['teacher_id'])->where('role', 'teacher')->exists(), 422);
        }

        if (! empty($data['class_id']) && ! empty($data['teacher_id'])) {
            abort_unless(ClassModel::whereKey($data['class_id'])->where('teacher_id', $data['teacher_id'])->exists(), 422);
        }

        if (! empty($data['course_id']) && ! empty($data['teacher_id'])) {
            abort_unless(Course::whereKey($data['course_id'])->where('teacher_id', $data['teacher_id'])->exists(), 422);
        }

        $invalidStudentIds = app(QuizAssignmentScopeValidator::class)->invalidAssignedStudentIds(
            $data['assigned_students'] ?? [],
            isset($data['class_id']) ? (int) $data['class_id'] : null,
            isset($data['course_id']) ? (int) $data['course_id'] : null,
        );

        abort_unless($invalidStudentIds === [], 422);
    }

    protected function validateQuestion(Request $request): array
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:users,id'],
            'quiz_id' => ['nullable', 'exists:quizzes,id'],
            'folder_id' => ['nullable', 'exists:question_folders,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'type' => ['required', Rule::in(['multiple_choice', 'true_false', 'short_answer'])],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string', 'max:1000'],
            'correct_answer' => ['required', 'string', 'max:2000'],
            'points' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'explanation' => ['nullable', 'string', 'max:3000'],
            'order' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        abort_unless(User::whereKey($validated['teacher_id'])->where('role', 'teacher')->exists(), 422);
        if (! empty($validated['quiz_id'])) {
            abort_unless(Quiz::whereKey($validated['quiz_id'])->where('teacher_id', $validated['teacher_id'])->exists(), 422);
        }
        if (! empty($validated['folder_id'])) {
            abort_unless(QuestionFolder::whereKey($validated['folder_id'])->where('teacher_id', $validated['teacher_id'])->exists(), 422);
        }

        $validated['options'] = array_values(array_filter($validated['options'] ?? [], fn ($option) => trim((string) $option) !== ''));
        if ($validated['type'] === 'multiple_choice') {
            abort_unless(count($validated['options']) >= 2, 422);
        }
        if ($validated['type'] !== 'multiple_choice') {
            $validated['options'] = [];
        }
        $validated['points'] = $validated['points'] ?? 1;

        return $validated;
    }

    protected function validateAssignment(Request $request): array
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:users,id'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['file', 'text', 'online'])],
            'due_at' => ['nullable', 'date'],
            'total_points' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        abort_unless(User::whereKey($validated['teacher_id'])->where('role', 'teacher')->exists(), 422);
        if (! empty($validated['class_id'])) {
            abort_unless(ClassModel::whereKey($validated['class_id'])->where('teacher_id', $validated['teacher_id'])->exists(), 422);
        }
        if (! empty($validated['course_id'])) {
            abort_unless(Course::whereKey($validated['course_id'])->where('teacher_id', $validated['teacher_id'])->exists(), 422);
        }

        $validated['total_points'] = $validated['total_points'] ?? 100;

        return $validated;
    }

    protected function validateQuestionFolder(Request $request, ?int $id = null): array
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:users,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('question_folders', 'name')->where('teacher_id', $request->input('teacher_id'))->ignore($id),
            ],
        ]);

        abort_unless(User::whereKey($validated['teacher_id'])->where('role', 'teacher')->exists(), 422);

        return $validated;
    }

    protected function gradeMaxScore(Grade $grade): int
    {
        if ($grade->gradable_type === Quiz::class) {
            return (int) ($grade->gradable?->total_points ?: 100);
        }

        if ($grade->gradable_type === Submission::class) {
            return (int) ($grade->gradable?->assignment?->total_points ?: 100);
        }

        return 1000;
    }

    protected function activateSubscriptionFromPayment(VipPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->refresh();
            $startedAt = $payment->paid_at ?? now();
            $subscription = VipSubscription::updateOrCreate(
                ['user_id' => $payment->user_id],
                [
                    'plan' => $payment->plan,
                    'status' => 'active',
                    'started_at' => $startedAt,
                    'expires_at' => $this->vipSubscriptionExpiresAt($payment->plan, $startedAt),
                ]
            );

            $payment->update([
                'vip_subscription_id' => $subscription->id,
                'status' => 'paid',
                'paid_at' => $payment->paid_at ?? now(),
            ]);
        });
    }

    protected function vipSubscriptionExpiresAt(string $plan, \Illuminate\Support\Carbon $startedAt, ?string $manualExpiresAt = null): ?\Illuminate\Support\Carbon
    {
        if ($plan === 'lifetime') {
            return null;
        }

        if ($manualExpiresAt) {
            return \Illuminate\Support\Carbon::parse($manualExpiresAt);
        }

        return $plan === 'yearly' ? $startedAt->copy()->addYear() : $startedAt->copy()->addMonth();
    }

    protected function ensureVipPlansExist(): void
    {
        foreach (VipPlan::defaults() as $plan) {
            VipPlan::firstOrCreate(
                ['audience' => $plan['audience'], 'plan' => $plan['plan']],
                $plan
            );
        }
    }

    protected function validatePromotion(Request $request, ?int $id = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('promotions', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'vip_plan' => ['nullable', Rule::in(['all', 'monthly', 'yearly', 'lifetime'])],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'used_count' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $validated['code'] = Str::upper($validated['code']);

        return $validated;
    }

    protected function trashMap(): array
    {
        return [
            'users' => ['label' => 'Người dùng', 'model' => User::class, 'title' => 'name'],
            'classes' => ['label' => 'Lớp học', 'model' => ClassModel::class, 'title' => 'name'],
            'courses' => ['label' => 'Khóa học', 'model' => Course::class, 'title' => 'name'],
            'quizzes' => ['label' => 'Bài kiểm tra', 'model' => Quiz::class, 'title' => 'title'],
            'questions' => ['label' => 'Câu hỏi', 'model' => Question::class, 'title' => 'content'],
            'assignments' => ['label' => 'Bài tập', 'model' => Assignment::class, 'title' => 'title'],
            'notifications' => ['label' => 'Thông báo', 'model' => Notification::class, 'title' => 'title'],
            'promotions' => ['label' => 'Khuyến mãi', 'model' => Promotion::class, 'title' => 'code'],
        ];
    }

    protected function adminTrashItems(array $map)
    {
        return collect($map)
            ->flatMap(fn (array $config, string $type) => $config['model']::onlyTrashed()
                ->with($config['with'] ?? [])
                ->latest('deleted_at')
                ->get()
                ->map(fn ($item) => $this->toAdminTrashItem($item, $type, $config)))
            ->sortByDesc('deleted_at')
            ->values();
    }

    protected function toAdminTrashItem($item, string $type, array $config): object
    {
        $deletedAt = $item->deleted_at;
        $ageDays = $deletedAt ? (int) floor($deletedAt->diffInDays(now())) : 0;
        $daysLeft = max(0, 30 - $ageDays);
        $title = (string) ($item->{$config['title']} ?? "#{$item->id}");
        $description = $this->adminTrashDescription($item, $type);
        $owner = $this->adminTrashOwner($item, $type);

        return new \Illuminate\Support\Fluent([
            'id' => $item->id,
            'key' => "{$type}:{$item->id}",
            'type' => $type,
            'label' => $config['label'],
            'type_label' => $config['label'],
            'title' => $title,
            'description' => $description,
            'owner' => $owner,
            'detail_route' => $this->adminTrashDetailRoute($type, $item->id),
            'search_text' => "{$config['label']} {$title} {$description} {$owner} {$item->id}",
            'deleted_at' => $deletedAt,
            'age_days' => $ageDays,
            'days_left' => $daysLeft,
            'is_expiring' => $daysLeft <= 7,
        ]);
    }

    protected function adminTrashDescription($item, string $type): ?string
    {
        return match ($type) {
            'users' => trim(implode(' ', array_filter([$item->email, $item->role, $item->phone]))),
            'classes' => trim(implode(' ', array_filter([$item->code, $item->teacher?->name]))),
            'courses' => trim(implode(' ', array_filter([$item->teacher?->name, $item->classModel?->name]))),
            'quizzes' => trim(implode(' ', array_filter([$item->teacher?->name, $item->status]))),
            'questions' => trim(implode(' ', array_filter([$item->teacher?->name, $item->quiz?->title, $item->type]))),
            'assignments' => trim(implode(' ', array_filter([$item->teacher?->name, $item->class?->name, $item->course?->name]))),
            'notifications' => trim(implode(' ', array_filter([$item->user?->name, $item->type, Str::limit((string) ($item->body ?? ''), 120)]))),
            'promotions' => trim(implode(' ', array_filter([$item->name, $item->status, $item->discount_type]))),
            default => null,
        };
    }

    protected function adminTrashOwner($item, string $type): ?string
    {
        return match ($type) {
            'users' => $item->email,
            'classes', 'courses', 'quizzes', 'questions', 'assignments' => $item->teacher?->name,
            'notifications' => $item->user?->name,
            'promotions' => $item->name,
            default => null,
        };
    }

    protected function adminTrashDetailRoute(string $type, int|string $id): ?string
    {
        return match ($type) {
            'users' => route('admin.users.show', $id),
            'classes' => route('admin.classes.show', $id),
            'courses' => route('admin.courses.show', $id),
            'quizzes' => route('admin.quizzes.show', $id),
            'assignments' => route('admin.assignments.show', $id),
            default => null,
        };
    }

    protected function performSelectedTrashAction(Request $request, string $action): int
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['string'],
        ]);

        $map = $this->trashMap();
        $grouped = collect($validated['items'])
            ->map(function (string $item) {
                [$type, $id] = array_pad(explode(':', $item, 2), 2, null);

                return [
                    'type' => $type,
                    'id' => is_string($id) && $id !== '' ? $id : null,
                ];
            })
            ->filter(fn (array $item) => isset($map[(string) $item['type']]) && $item['id'])
            ->groupBy('type');

        $count = 0;
        foreach ($grouped as $type => $items) {
            $query = $map[$type]['model']::onlyTrashed()->whereIn('id', $items->pluck('id')->all());
            $count += (clone $query)->count();
            $query->{$action}();
        }

        return $count;
    }

    protected function isAdmin(Request $request): bool
    {
        if (Auth::guard('web')->check()) {
            $request->session()->forget(self::SESSION_KEY);

            return false;
        }

        return (bool) $request->session()->get(self::SESSION_KEY, false);
    }

    protected function requireAdmin(Request $request): ?RedirectResponse
    {
        return $this->isAdmin($request) ? null : redirect()->route('admin.dashboard');
    }
}

