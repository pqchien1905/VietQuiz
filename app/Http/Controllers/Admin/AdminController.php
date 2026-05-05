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

class AdminController extends Controller
{
    public const SESSION_KEY = 'vietquiz_admin_authenticated';

    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->isAdmin($request)) {
            return view('pages.admin.login');
        }

        $stats = [
            'users' => User::count(),
            'teachers' => User::where('role', 'teacher')->count(),
            'students' => User::where('role', 'student')->count(),
            'classes' => ClassModel::count(),
            'courses' => Course::count(),
            'quizzes' => Quiz::count(),
            'assignments' => Assignment::count(),
            'submissions' => Submission::count(),
            'tickets' => Ticket::whereIn('status', ['open', 'in_progress'])->count(),
            'vip' => VipSubscription::where('status', 'active')->count(),
        ];

        $recentUsers = User::latest()->limit(6)->get();
        $recentTickets = Ticket::with('user')->latest()->limit(6)->get();
        $recentQuizzes = Quiz::with('teacher')->withCount('questions')->latest()->limit(6)->get();
        $attemptCount = DB::table('quiz_user')->whereNotNull('submitted_at')->count();
        $avgScore = Grade::avg('score');
        $today = now()->startOfDay();
        $last7Days = now()->subDays(7);
        $last30Days = now()->subDays(30);

        $growth = [
            'today' => [
                'users' => User::where('created_at', '>=', $today)->count(),
                'quizzes' => Quiz::where('created_at', '>=', $today)->count(),
                'submissions' => Submission::where('created_at', '>=', $today)->count(),
                'tickets' => Ticket::where('created_at', '>=', $today)->count(),
            ],
            'seven_days' => [
                'users' => User::where('created_at', '>=', $last7Days)->count(),
                'quizzes' => Quiz::where('created_at', '>=', $last7Days)->count(),
                'submissions' => Submission::where('created_at', '>=', $last7Days)->count(),
                'tickets' => Ticket::where('created_at', '>=', $last7Days)->count(),
                'revenue' => VipPayment::where('status', 'paid')->where('paid_at', '>=', $last7Days)->sum('amount'),
            ],
            'thirty_days' => [
                'users' => User::where('created_at', '>=', $last30Days)->count(),
                'quizzes' => Quiz::where('created_at', '>=', $last30Days)->count(),
                'submissions' => Submission::where('created_at', '>=', $last30Days)->count(),
                'tickets' => Ticket::where('created_at', '>=', $last30Days)->count(),
                'revenue' => VipPayment::where('status', 'paid')->where('paid_at', '>=', $last30Days)->sum('amount'),
            ],
        ];

        $staleTickets = Ticket::with('user')
            ->whereIn('status', ['open', 'in_progress'])
            ->orderByRaw("CASE WHEN priority = 'vip' THEN 0 ELSE 1 END")
            ->oldest()
            ->limit(6)
            ->get();
        $paymentIssues = VipPayment::with('user')
            ->whereIn('status', ['pending', 'failed'])
            ->latest()
            ->limit(6)
            ->get();
        $closingQuizzes = Quiz::with(['teacher', 'classModel', 'course'])
            ->withCount('questions')
            ->where('status', 'published')
            ->whereNotNull('end_at')
            ->whereBetween('end_at', [now(), now()->addDays(7)])
            ->orderBy('end_at')
            ->limit(6)
            ->get();
        $overdueAssignments = Assignment::with(['teacher', 'class', 'course'])
            ->withCount('submissions')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->latest('due_at')
            ->limit(6)
            ->get();

        $systemChecks = [
            ['label' => 'Storage', 'ok' => is_writable(storage_path()), 'detail' => storage_path()],
            ['label' => 'Mail', 'ok' => filled(config('mail.default')), 'detail' => config('mail.default') ?: 'Chưa cấu hình'],
            ['label' => 'Queue', 'ok' => filled(config('queue.default')), 'detail' => config('queue.default') ?: 'Chưa cấu hình'],
            ['label' => 'Cache', 'ok' => filled(config('cache.default')), 'detail' => config('cache.default') ?: 'Chưa cấu hình'],
            ['label' => 'VNPay', 'ok' => filled(config('services.vnpay.tmn_code')) && filled(config('services.vnpay.hash_secret')), 'detail' => filled(config('services.vnpay.tmn_code')) ? 'Đã có mã merchant' : 'Thiếu mã merchant'],
            ['label' => 'AI', 'ok' => filled(config('services.openai.api_key')), 'detail' => filled(config('services.openai.api_key')) ? 'Đã cấu hình API key' : 'Chưa cấu hình API key'],
        ];

        return view('pages.admin.dashboard', compact(
            'stats',
            'recentUsers',
            'recentTickets',
            'recentQuizzes',
            'attemptCount',
            'avgScore',
            'growth',
            'staleTickets',
            'paymentIssues',
            'closingQuizzes',
            'overdueAssignments',
            'systemChecks',
        ));
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = (string) config('services.admin.username', 'admin');
        $password = (string) config('services.admin.password', 'password');

        if (! hash_equals($username, $validated['username']) || ! hash_equals($password, $validated['password'])) {
            return back()->withErrors(['username' => 'Tài khoản hoặc mật khẩu admin không đúng.'])->withInput($request->only('username'));
        }

        Auth::guard('web')->logout();
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, true);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);
        $request->session()->regenerateToken();

        return redirect()->route('admin.dashboard');
    }

    public function search(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $queryText = Str::of((string) $request->query('q', ''))->trim()->limit(100, '')->toString();
        $like = "%{$queryText}%";

        $groups = collect();
        if ($queryText !== '') {
            $groups = collect([
                [
                    'label' => 'Người dùng',
                    'route' => route('admin.users', ['q' => $queryText, 'state' => 'all']),
                    'items' => User::withTrashed()
                        ->where(fn ($query) => $query->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone', 'like', $like))
                        ->latest()
                        ->limit(6)
                        ->get()
                        ->map(fn ($user) => [
                            'title' => $user->name,
                            'description' => trim(implode(' · ', array_filter([$user->email, $user->role, $user->phone]))),
                            'href' => route('admin.users.show', $user->id),
                            'badge' => $user->trashed() ? 'Đã khóa' : 'Hoạt động',
                        ]),
                ],
                [
                    'label' => 'Lớp học',
                    'route' => route('admin.classes', ['q' => $queryText, 'state' => 'all']),
                    'items' => ClassModel::withTrashed()
                        ->with('teacher')
                        ->where(fn ($query) => $query->where('name', 'like', $like)
                            ->orWhere('code', 'like', $like)
                            ->orWhere('subject', 'like', $like)
                            ->orWhere('grade_level', 'like', $like))
                        ->latest()
                        ->limit(6)
                        ->get()
                        ->map(fn ($class) => [
                            'title' => $class->name,
                            'description' => trim(implode(' · ', array_filter([$class->code, $class->teacher?->name, $class->subject]))),
                            'href' => route('admin.classes.show', $class->id),
                            'badge' => $class->status,
                        ]),
                ],
                [
                    'label' => 'Khóa học',
                    'route' => route('admin.courses', ['q' => $queryText, 'state' => 'all']),
                    'items' => Course::withTrashed()
                        ->with(['teacher', 'classModel'])
                        ->where(fn ($query) => $query->where('name', 'like', $like)
                            ->orWhere('description', 'like', $like))
                        ->latest()
                        ->limit(6)
                        ->get()
                        ->map(fn ($course) => [
                            'title' => $course->name,
                            'description' => trim(implode(' · ', array_filter([$course->teacher?->name, $course->classModel?->name]))),
                            'href' => route('admin.courses.show', $course->id),
                            'badge' => $course->status,
                        ]),
                ],
                [
                    'label' => 'Bài kiểm tra',
                    'route' => route('admin.quizzes', ['q' => $queryText, 'state' => 'all']),
                    'items' => Quiz::withTrashed()
                        ->with('teacher')
                        ->where(fn ($query) => $query->where('title', 'like', $like)
                            ->orWhere('description', 'like', $like))
                        ->latest()
                        ->limit(6)
                        ->get()
                        ->map(fn ($quiz) => [
                            'title' => $quiz->title,
                            'description' => trim(implode(' · ', array_filter([$quiz->teacher?->name, $quiz->duration_minutes ? $quiz->duration_minutes.' phút' : null]))),
                            'href' => route('admin.quizzes.show', $quiz->id),
                            'badge' => $quiz->status,
                        ]),
                ],
                [
                    'label' => 'Bài tập',
                    'route' => route('admin.assignments', ['q' => $queryText, 'state' => 'all']),
                    'items' => Assignment::withTrashed()
                        ->with(['teacher', 'class', 'course'])
                        ->where(fn ($query) => $query->where('title', 'like', $like)
                            ->orWhere('description', 'like', $like))
                        ->latest()
                        ->limit(6)
                        ->get()
                        ->map(fn ($assignment) => [
                            'title' => $assignment->title,
                            'description' => trim(implode(' · ', array_filter([$assignment->teacher?->name, $assignment->class?->name, $assignment->course?->name]))),
                            'href' => route('admin.assignments.show', $assignment->id),
                            'badge' => $assignment->type,
                        ]),
                ],
                [
                    'label' => 'Câu hỏi',
                    'route' => route('admin.questions', ['q' => $queryText, 'state' => 'all']),
                    'items' => Question::withTrashed()
                        ->with(['teacher', 'quiz'])
                        ->where(fn ($query) => $query->where('content', 'like', $like)
                            ->orWhere('subject', 'like', $like)
                            ->orWhere('correct_answer', 'like', $like))
                        ->latest()
                        ->limit(6)
                        ->get()
                        ->map(fn ($question) => [
                            'title' => Str::limit($question->content, 90),
                            'description' => trim(implode(' · ', array_filter([$question->teacher?->name, $question->quiz?->title, $question->subject]))),
                            'href' => route('admin.questions', ['q' => $queryText]),
                            'badge' => $question->type,
                        ]),
                ],
                [
                    'label' => 'Hỗ trợ',
                    'route' => route('admin.tickets', ['q' => $queryText]),
                    'items' => Ticket::with('user')
                        ->where(fn ($query) => $query->where('subject', 'like', $like)
                            ->orWhere('description', 'like', $like)
                            ->orWhere('admin_response', 'like', $like))
                        ->latest()
                        ->limit(6)
                        ->get()
                        ->map(fn ($ticket) => [
                            'title' => $ticket->subject,
                            'description' => trim(implode(' · ', array_filter([$ticket->user?->email, $ticket->category, $ticket->priority]))),
                            'href' => route('admin.tickets', ['q' => $queryText]),
                            'badge' => $ticket->status,
                        ]),
                ],
                [
                    'label' => 'Khuyến mãi',
                    'route' => route('admin.promotions', ['q' => $queryText, 'state' => 'all']),
                    'items' => Promotion::withTrashed()
                        ->where(fn ($query) => $query->where('code', 'like', $like)
                            ->orWhere('name', 'like', $like)
                            ->orWhere('description', 'like', $like))
                        ->latest()
                        ->limit(6)
                        ->get()
                        ->map(fn ($promotion) => [
                            'title' => $promotion->code,
                            'description' => $promotion->name,
                            'href' => route('admin.promotions', ['q' => $queryText]),
                            'badge' => $promotion->status,
                        ]),
                ],
            ])->map(function (array $group) {
                $group['count'] = $group['items']->count();

                return $group;
            })->filter(fn (array $group) => $group['count'] > 0)->values();
        }

        $total = $groups->sum('count');

        return view('pages.admin.search', compact('queryText', 'groups', 'total'));
    }

    public function analytics(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return view('pages.admin.analytics', $this->adminAnalyticsPayload($request));
    }

    public function exportAnalytics(Request $request): StreamedResponse|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $data = $this->adminAnalyticsPayload($request);
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('VietQuiz')
            ->setTitle('VietQuiz Admin Analytics');

        foreach ($this->analyticsExportSheets($data) as $index => $sheetData) {
            $sheet = $index === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $this->writeSheet($sheet, $sheetData['title'], $sheetData['rows']);
        }

        $spreadsheet->setActiveSheetIndex(0);
        $fileName = 'vietquiz-admin-thong-ke-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function users(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $state = $request->query('state', 'active');

        $users = User::query()
            ->when($state === 'all' || $state === 'deleted', fn ($query) => $query->withTrashed())
            ->with('vipSubscription')
            ->withCount(['classes', 'courses', 'tickets'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"));
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->query('role')))
            ->when($request->query('vip') === 'active', fn ($query) => $query->whereHas('vipSubscription', fn ($q) => $q->where('status', 'active')))
            ->when($state === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when($state === 'active', fn ($query) => $query->whereNull('deleted_at'))
            ->when($request->query('sort') === 'name', fn ($query) => $query->orderBy('name'))
            ->when($request->query('sort') === 'oldest', fn ($query) => $query->oldest())
            ->when(! in_array($request->query('sort'), ['name', 'oldest'], true), fn ($query) => $query->latest())
            ->paginate(18)
            ->withQueryString();

        $summary = [
            'total' => User::withTrashed()->count(),
            'active' => User::count(),
            'deleted' => User::onlyTrashed()->count(),
            'teachers' => User::where('role', 'teacher')->count(),
            'students' => User::where('role', 'student')->count(),
        ];

        return view('pages.admin.users', compact('users', 'summary'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,teacher,student'],
            'phone' => ['nullable', 'string', 'max:20'],
            'subject' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'can_switch_role' => ['nullable', 'boolean'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['can_switch_role'] = (bool) ($validated['can_switch_role'] ?? false);
        $validated['last_active_role'] = in_array($validated['role'], ['teacher', 'student'], true)
            ? $validated['role']
            : null;

        User::create($validated);

        return redirect()->route('admin.users')->with('success', 'Đã tạo tài khoản người dùng.');
    }

    public function showUser(Request $request, int $id): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $user = User::withTrashed()
            ->with([
                'vipSubscription',
                'createdClasses.students',
                'createdCourses.students',
                'quizzes.questions',
                'assignments.submissions',
                'classes.teacher',
                'courses.teacher',
                'submissions.assignment',
                'grades.gradable',
                'notifications' => fn ($query) => $query->latest()->limit(12),
                'tickets' => fn ($query) => $query->latest()->limit(12),
            ])
            ->withCount([
                'createdClasses', 'createdCourses', 'quizzes', 'assignments',
                'classes', 'courses', 'submissions', 'grades', 'notifications', 'tickets',
            ])
            ->findOrFail($id);

        $attempts = $user->quizAttempts()
            ->with('teacher')
            ->orderByDesc('quiz_user.updated_at')
            ->limit(20)
            ->get();

        return view('pages.admin.user-show', compact('user', 'attempts'));
    }

    public function updateUser(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'role' => 'required|in:admin,teacher,student',
            'phone' => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8|max:255',
            'can_switch_role' => 'nullable|boolean',
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['can_switch_role'] = (bool) ($validated['can_switch_role'] ?? false);
        $validated['last_active_role'] = in_array($validated['role'], ['teacher', 'student'], true)
            ? $validated['role']
            : null;

        User::withTrashed()->findOrFail($id)->forceFill($validated)->save();

        return back()->with('success', 'Đã cập nhật tài khoản.');
    }

    public function deleteUser(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        User::findOrFail($id)->delete();
        return back()->with('success', 'Đã khóa tài khoản.');
    }

    public function restoreUser(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        User::withTrashed()->findOrFail($id)->restore();
        return back()->with('success', 'Đã khôi phục tài khoản.');
    }

    public function classes(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $state = $request->query('state', 'active');

        $classes = ClassModel::query()
            ->when($state === 'all' || $state === 'deleted', fn ($query) => $query->withTrashed())
            ->with('teacher')
            ->withCount(['students', 'courses', 'quizzes', 'assignments'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('grade_level', 'like', "%{$search}%"));
            })
            ->when($request->filled('teacher_id'), fn ($query) => $query->where('teacher_id', $request->query('teacher_id')))
            ->when($request->filled('subject'), fn ($query) => $query->where('subject', $request->query('subject')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($state === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when($state === 'active', fn ($query) => $query->whereNull('deleted_at'))
            ->when($request->query('sort') === 'name', fn ($query) => $query->orderBy('name'))
            ->when($request->query('sort') === 'students', fn ($query) => $query->orderByDesc('students_count'))
            ->when($request->query('sort') === 'oldest', fn ($query) => $query->oldest())
            ->when(! in_array($request->query('sort'), ['name', 'students', 'oldest'], true), fn ($query) => $query->latest())
            ->paginate(18)
            ->withQueryString();

        $teachers = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name', 'email']);
        $subjects = ClassModel::withTrashed()
            ->whereNotNull('subject')
            ->where('subject', '!=', '')
            ->distinct()
            ->orderBy('subject')
            ->pluck('subject');
        $summary = [
            'total' => ClassModel::withTrashed()->count(),
            'active' => ClassModel::where('status', 'active')->count(),
            'archived' => ClassModel::where('status', 'archived')->count(),
            'deleted' => ClassModel::onlyTrashed()->count(),
            'students' => DB::table('class_user')->distinct('user_id')->count('user_id'),
        ];

        return view('pages.admin.classes', compact('classes', 'teachers', 'subjects', 'summary'));
    }

    public function storeClass(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:classes,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'subject' => ['nullable', 'string', 'max:255'],
            'grade_level' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'icon' => ['nullable', 'string', 'max:10'],
            'status' => ['required', 'in:active,archived'],
        ]);

        abort_unless(User::whereKey($validated['teacher_id'])->where('role', 'teacher')->exists(), 422);

        $validated['code'] = filled($validated['code'] ?? null)
            ? Str::upper((string) $validated['code'])
            : $this->generateClassCode();

        ClassModel::create($validated);

        return redirect()->route('admin.classes')->with('success', 'Đã tạo lớp học.');
    }

    public function showClass(Request $request, int $id): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $class = ClassModel::withTrashed()
            ->with(['teacher', 'students', 'courses.teacher', 'quizzes.teacher', 'assignments.teacher'])
            ->withCount(['students', 'courses', 'quizzes', 'assignments'])
            ->findOrFail($id);
        $teachers = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name', 'email']);
        $availableStudents = User::where('role', 'student')
            ->whereDoesntHave('classes', fn ($query) => $query->where('classes.id', $class->id))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'email']);

        return view('pages.admin.class-show', compact('class', 'teachers', 'availableStudents'));
    }

    public function updateClass(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('classes', 'code')->ignore($id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'subject' => ['nullable', 'string', 'max:255'],
            'grade_level' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'icon' => ['nullable', 'string', 'max:10'],
            'status' => ['required', 'in:active,archived'],
        ]);
        abort_unless(User::whereKey($validated['teacher_id'])->where('role', 'teacher')->exists(), 422);

        $validated['code'] = Str::upper($validated['code']);

        ClassModel::withTrashed()->findOrFail($id)->update($validated);
        return back()->with('success', 'Đã cập nhật lớp học.');
    }

    public function addClassStudent(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate(['student_id' => ['required', 'exists:users,id']]);
        $student = User::where('role', 'student')->findOrFail($validated['student_id']);
        ClassModel::withTrashed()->findOrFail($id)->students()->syncWithoutDetaching([
            $student->id => ['joined_at' => now()],
        ]);

        return back()->with('success', 'Đã thêm học sinh vào lớp.');
    }

    public function removeClassStudent(Request $request, int $id, int $studentId): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        ClassModel::withTrashed()->findOrFail($id)->students()->detach($studentId);

        return back()->with('success', 'Đã gỡ học sinh khỏi lớp.');
    }

    public function deleteClass(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        ClassModel::findOrFail($id)->delete();
        return back()->with('success', 'Đã đưa lớp học vào thùng rác.');
    }

    public function restoreClass(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        ClassModel::withTrashed()->findOrFail($id)->restore();
        return back()->with('success', 'Đã khôi phục lớp học.');
    }

    public function courses(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $state = $request->query('state', 'active');

        $courses = Course::query()
            ->when($state === 'all' || $state === 'deleted', fn ($query) => $query->withTrashed())
            ->with(['teacher', 'classModel'])
            ->withCount(['students', 'quizzes', 'assignments'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"));
            })
            ->when($request->filled('teacher_id'), fn ($query) => $query->where('teacher_id', $request->query('teacher_id')))
            ->when($request->filled('class_id'), fn ($query) => $query->where('class_id', $request->query('class_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->query('scope') === 'unlinked', fn ($query) => $query->whereNull('class_id'))
            ->when($request->query('scope') === 'empty_students', fn ($query) => $query->doesntHave('students'))
            ->when($request->query('scope') === 'empty_content', fn ($query) => $query->doesntHave('quizzes')->doesntHave('assignments'))
            ->when($state === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when($state === 'active', fn ($query) => $query->whereNull('deleted_at'))
            ->when($request->query('sort') === 'name', fn ($query) => $query->orderBy('name'))
            ->when($request->query('sort') === 'students', fn ($query) => $query->orderByDesc('students_count'))
            ->when($request->query('sort') === 'content', fn ($query) => $query->orderByDesc('quizzes_count')->orderByDesc('assignments_count'))
            ->when($request->query('sort') === 'oldest', fn ($query) => $query->oldest())
            ->when(! in_array($request->query('sort'), ['name', 'students', 'content', 'oldest'], true), fn ($query) => $query->latest())
            ->paginate(18)
            ->withQueryString();

        $teachers = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name', 'email']);
        $classes = ClassModel::orderBy('name')->get(['id', 'name', 'code', 'teacher_id']);
        $summary = [
            'total' => Course::withTrashed()->count(),
            'published' => Course::where('status', 'published')->count(),
            'draft' => Course::where('status', 'draft')->count(),
            'deleted' => Course::onlyTrashed()->count(),
            'students' => DB::table('course_user')->distinct('user_id')->count('user_id'),
            'unlinked' => Course::whereNull('class_id')->count(),
            'empty_students' => Course::doesntHave('students')->count(),
            'empty_content' => Course::doesntHave('quizzes')->doesntHave('assignments')->count(),
        ];

        return view('pages.admin.courses', compact('courses', 'teachers', 'classes', 'summary'));
    }

    public function storeCourse(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:users,id'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'cover_image' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'icon' => ['nullable', 'string', 'max:10'],
            'status' => ['required', 'in:draft,published'],
        ]);
        abort_unless(User::whereKey($validated['teacher_id'])->where('role', 'teacher')->exists(), 422);

        Course::create($validated);

        return redirect()->route('admin.courses')->with('success', 'Đã tạo khóa học.');
    }

    public function showCourse(Request $request, int $id): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $course = Course::withTrashed()
            ->with(['teacher', 'classModel.students', 'students', 'quizzes.questions', 'assignments.submissions'])
            ->withCount(['students', 'quizzes', 'assignments'])
            ->findOrFail($id);
        $teachers = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name', 'email']);
        $classes = ClassModel::orderBy('name')->get(['id', 'name', 'teacher_id']);
        $availableStudents = User::where('role', 'student')
            ->whereDoesntHave('courses', fn ($query) => $query->where('courses.id', $course->id))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'email']);

        return view('pages.admin.course-show', compact('course', 'teachers', 'classes', 'availableStudents'));
    }

    public function updateCourse(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:users,id'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'cover_image' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'icon' => ['nullable', 'string', 'max:10'],
            'status' => ['required', 'in:draft,published'],
        ]);
        abort_unless(User::whereKey($validated['teacher_id'])->where('role', 'teacher')->exists(), 422);

        Course::withTrashed()->findOrFail($id)->update($validated);
        return back()->with('success', 'Đã cập nhật khóa học.');
    }

    public function addCourseStudent(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate(['student_id' => ['required', 'exists:users,id']]);
        $student = User::where('role', 'student')->findOrFail($validated['student_id']);
        Course::withTrashed()->findOrFail($id)->students()->syncWithoutDetaching([
            $student->id => ['enrolled_at' => now()],
        ]);

        return back()->with('success', 'Đã ghi danh học sinh vào khóa học.');
    }

    public function syncCourseStudents(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $course = Course::withTrashed()->with('classModel.students')->findOrFail($id);
        if (! $course->classModel) {
            return back()->withErrors(['course' => 'Khóa học chưa gắn lớp để đồng bộ học sinh.']);
        }

        $students = $course->classModel->students
            ->mapWithKeys(fn ($student) => [$student->id => ['enrolled_at' => now()]])
            ->all();
        $course->students()->syncWithoutDetaching($students);

        return back()->with('success', 'Đã đồng bộ học sinh từ lớp sang khóa học.');
    }

    public function removeCourseStudent(Request $request, int $id, int $studentId): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Course::withTrashed()->findOrFail($id)->students()->detach($studentId);

        return back()->with('success', 'Đã gỡ học sinh khỏi khóa học.');
    }

    public function deleteCourse(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        Course::findOrFail($id)->delete();
        return back()->with('success', 'Đã đưa khóa học vào thùng rác.');
    }

    public function restoreCourse(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        Course::withTrashed()->findOrFail($id)->restore();
        return back()->with('success', 'Đã khôi phục khóa học.');
    }

    public function quizzes(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $state = $request->query('state', 'active');

        $quizzes = Quiz::query()
            ->when($state === 'all' || $state === 'deleted', fn ($query) => $query->withTrashed())
            ->with(['teacher', 'course', 'classModel'])
            ->withCount([
                'questions',
                'attempts',
                'attempts as submitted_attempts_count' => fn ($query) => $query->whereNotNull('quiz_user.submitted_at'),
            ])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"));
            })
            ->when($request->filled('teacher_id'), fn ($query) => $query->where('teacher_id', $request->query('teacher_id')))
            ->when($request->filled('class_id'), fn ($query) => $query->where('class_id', $request->query('class_id')))
            ->when($request->filled('course_id'), fn ($query) => $query->where('course_id', $request->query('course_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('quiz_type'), fn ($query) => $query->where('quiz_type', $request->query('quiz_type')))
            ->when($request->query('scope') === 'no_questions', fn ($query) => $query->doesntHave('questions'))
            ->when($request->query('scope') === 'unassigned', fn ($query) => $query->whereNull('class_id')->whereNull('course_id')->whereNull('assigned_students'))
            ->when($request->query('scope') === 'scheduled', fn ($query) => $query->where('status', 'published')->where('start_at', '>', now()))
            ->when($request->query('scope') === 'expired', fn ($query) => $query->whereNotNull('end_at')->where('end_at', '<', now()))
            ->when($request->query('scope') === 'ungraded', fn ($query) => $query->whereExists(function ($subQuery) {
                $subQuery->selectRaw(1)
                    ->from('quiz_user')
                    ->whereColumn('quiz_user.quiz_id', 'quizzes.id')
                    ->whereNotNull('quiz_user.submitted_at')
                    ->where('quiz_user.is_graded', false);
            }))
            ->when($state === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when($state === 'active', fn ($query) => $query->whereNull('deleted_at'))
            ->when($request->query('sort') === 'title', fn ($query) => $query->orderBy('title'))
            ->when($request->query('sort') === 'questions', fn ($query) => $query->orderByDesc('questions_count'))
            ->when($request->query('sort') === 'attempts', fn ($query) => $query->orderByDesc('submitted_attempts_count'))
            ->when($request->query('sort') === 'oldest', fn ($query) => $query->oldest())
            ->when(! in_array($request->query('sort'), ['title', 'questions', 'attempts', 'oldest'], true), fn ($query) => $query->latest())
            ->paginate(18)
            ->withQueryString();

        $teachers = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name', 'email']);
        $classes = ClassModel::orderBy('name')->get(['id', 'name', 'code', 'teacher_id']);
        $courses = Course::orderBy('name')->get(['id', 'name', 'teacher_id']);
        $summary = [
            'total' => Quiz::withTrashed()->count(),
            'published' => Quiz::where('status', 'published')->count(),
            'draft' => Quiz::where('status', 'draft')->count(),
            'closed' => Quiz::where('status', 'closed')->count(),
            'deleted' => Quiz::onlyTrashed()->count(),
            'attempts' => DB::table('quiz_user')->whereNotNull('submitted_at')->count(),
            'ungraded' => DB::table('quiz_user')->whereNotNull('submitted_at')->where('is_graded', false)->count(),
            'no_questions' => Quiz::doesntHave('questions')->count(),
            'scheduled' => Quiz::where('status', 'published')->where('start_at', '>', now())->count(),
        ];

        return view('pages.admin.quizzes', compact('quizzes', 'teachers', 'classes', 'courses', 'summary'));
    }

    public function storeQuiz(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $this->validateAdminQuiz($request);
        $this->ensureQuizTeacherScope($validated);

        Quiz::create($validated);

        return redirect()->route('admin.quizzes')->with('success', 'Đã tạo bài kiểm tra.');
    }

    public function showQuiz(Request $request, int $id): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $quiz = Quiz::withTrashed()
            ->with(['teacher', 'course', 'classModel', 'folder', 'questions' => fn ($query) => $query->orderBy('order')->orderBy('id')])
            ->withCount('questions')
            ->findOrFail($id);
        $attempts = DB::table('quiz_user')
            ->join('users', 'users.id', '=', 'quiz_user.user_id')
            ->where('quiz_user.quiz_id', $quiz->id)
            ->select('quiz_user.*', 'users.name as student_name', 'users.email as student_email')
            ->orderByDesc('quiz_user.updated_at')
            ->paginate(20)
            ->withQueryString();
        $attemptSummary = [
            'total' => DB::table('quiz_user')->where('quiz_id', $quiz->id)->count(),
            'submitted' => DB::table('quiz_user')->where('quiz_id', $quiz->id)->whereNotNull('submitted_at')->count(),
            'ungraded' => DB::table('quiz_user')->where('quiz_id', $quiz->id)->whereNotNull('submitted_at')->where('is_graded', false)->count(),
            'avg_score' => DB::table('quiz_user')->where('quiz_id', $quiz->id)->whereNotNull('submitted_at')->avg('score'),
        ];
        $teachers = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name', 'email']);
        $classes = ClassModel::orderBy('name')->get(['id', 'name', 'code', 'teacher_id']);
        $courses = Course::orderBy('name')->get(['id', 'name', 'teacher_id']);

        return view('pages.admin.quiz-show', compact('quiz', 'attempts', 'attemptSummary', 'teachers', 'classes', 'courses'));
    }

    public function updateQuiz(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $quiz = Quiz::withTrashed()->findOrFail($id);
        $validated = $this->validateAdminQuiz($request, partial: true);
        $merged = array_merge($quiz->only([
            'teacher_id', 'class_id', 'course_id', 'title', 'description', 'duration_minutes',
            'time_limit', 'total_points', 'passing_score', 'max_attempts', 'status',
            'start_at', 'end_at', 'shuffle_questions', 'shuffle_answers', 'is_shuffle',
            'show_result', 'quiz_type', 'anti_cheat_enabled',
        ]), $validated);
        $this->ensureQuizTeacherScope($merged);

        $quiz->update($validated);
        return back()->with('success', 'Đã cập nhật bài kiểm tra.');
    }

    private function validateAdminQuiz(Request $request, bool $partial = false): array
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
        ]);

        foreach (['shuffle_questions', 'shuffle_answers', 'is_shuffle', 'show_result', 'anti_cheat_enabled'] as $booleanField) {
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

        return $validated;
    }

    private function ensureQuizTeacherScope(array $data): void
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
    }

    public function resetQuizAttempt(Request $request, int $quizId, int $studentId): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        DB::table('quiz_user')->where('quiz_id', $quizId)->where('user_id', $studentId)->delete();

        return back()->with('success', 'Đã đặt lại lượt làm bài kiểm tra của học sinh.');
    }

    public function deleteQuiz(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        Quiz::findOrFail($id)->delete();
        return back()->with('success', 'Đã đưa bài kiểm tra vào thùng rác.');
    }

    public function restoreQuiz(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        Quiz::withTrashed()->findOrFail($id)->restore();
        return back()->with('success', 'Đã khôi phục bài kiểm tra.');
    }

    public function questions(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $state = $request->query('state', 'active');

        $questions = Question::query()
            ->when($state === 'all' || $state === 'deleted', fn ($query) => $query->withTrashed())
            ->with(['teacher', 'quiz', 'folder'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $query->where(fn ($q) => $q->where('content', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('correct_answer', 'like', "%{$search}%"));
            })
            ->when($request->filled('teacher_id'), fn ($query) => $query->where('teacher_id', $request->query('teacher_id')))
            ->when($request->filled('quiz_id'), fn ($query) => $query->where('quiz_id', $request->query('quiz_id')))
            ->when($request->filled('folder_id'), fn ($query) => $query->where('folder_id', $request->query('folder_id')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->query('type')))
            ->when($request->query('scope') === 'bank', fn ($query) => $query->whereNull('quiz_id'))
            ->when($request->query('scope') === 'quiz', fn ($query) => $query->whereNotNull('quiz_id'))
            ->when($request->query('scope') === 'uncategorized', fn ($query) => $query->whereNull('quiz_id')->whereNull('folder_id'))
            ->when($request->query('quality') === 'missing_explanation', fn ($query) => $query->where(fn ($q) => $q->whereNull('explanation')->orWhere('explanation', '')))
            ->when($request->query('quality') === 'missing_options', fn ($query) => $query->where('type', 'multiple_choice')->where(fn ($q) => $q->whereNull('options')->orWhereJsonLength('options', '<', 2)))
            ->when($request->query('quality') === 'zero_points', fn ($query) => $query->where('points', '<=', 0))
            ->when($request->query('state') === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when($state === 'active', fn ($query) => $query->whereNull('deleted_at'))
            ->when($request->query('sort') === 'content', fn ($query) => $query->orderBy('content'))
            ->when($request->query('sort') === 'points', fn ($query) => $query->orderByDesc('points'))
            ->when($request->query('sort') === 'oldest', fn ($query) => $query->oldest())
            ->when(! in_array($request->query('sort'), ['content', 'points', 'oldest'], true), fn ($query) => $query->latest())
            ->paginate(18)
            ->withQueryString();

        $teachers = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name', 'email']);
        $quizzes = Quiz::with('teacher')->orderBy('title')->limit(200)->get(['id', 'title', 'teacher_id']);
        $folders = QuestionFolder::with('teacher')->withCount('questions')->orderBy('name')->get();
        $summary = [
            'total' => Question::withTrashed()->count(),
            'bank' => Question::whereNull('quiz_id')->count(),
            'quiz' => Question::whereNotNull('quiz_id')->count(),
            'deleted' => Question::onlyTrashed()->count(),
            'folders' => QuestionFolder::count(),
            'missing_explanation' => Question::where(fn ($query) => $query->whereNull('explanation')->orWhere('explanation', ''))->count(),
            'missing_options' => Question::where('type', 'multiple_choice')->where(fn ($query) => $query->whereNull('options')->orWhereJsonLength('options', '<', 2))->count(),
        ];

        return view('pages.admin.questions', compact('questions', 'teachers', 'quizzes', 'folders', 'summary'));
    }

    public function storeQuestionFolder(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        QuestionFolder::create($this->validateQuestionFolder($request));

        return back()->with('success', 'Đã tạo thư mục câu hỏi.');
    }

    public function updateQuestionFolder(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        QuestionFolder::findOrFail($id)->update($this->validateQuestionFolder($request, $id));

        return back()->with('success', 'Đã cập nhật thư mục câu hỏi.');
    }

    public function deleteQuestionFolder(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $folder = QuestionFolder::withCount('questions')->findOrFail($id);
        if ($folder->questions_count > 0) {
            return back()->withErrors(['folder' => 'Thư mục vẫn còn câu hỏi. Hãy chuyển câu hỏi sang nơi khác trước khi xóa.']);
        }

        $folder->delete();

        return back()->with('success', 'Đã xóa thư mục câu hỏi.');
    }

    public function storeQuestion(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Question::create($this->validateQuestion($request));

        return back()->with('success', 'Đã tạo câu hỏi.');
    }

    public function updateQuestion(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Question::withTrashed()->findOrFail($id)->update($this->validateQuestion($request));

        return back()->with('success', 'Đã cập nhật câu hỏi.');
    }

    public function deleteQuestion(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Question::findOrFail($id)->delete();

        return back()->with('success', 'Đã đưa câu hỏi vào thùng rác.');
    }

    public function restoreQuestion(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Question::withTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Đã khôi phục câu hỏi.');
    }

    public function assignments(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $state = $request->query('state', 'active');

        $assignments = Assignment::query()
            ->when($state === 'all' || $state === 'deleted', fn ($query) => $query->withTrashed())
            ->with([
                'teacher',
                'class' => fn ($query) => $query->withCount('students'),
                'course' => fn ($query) => $query->withCount('students'),
            ])
            ->withCount([
                'submissions',
                'submissions as graded_submissions_count' => fn ($query) => $query->whereHas('grades'),
            ])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"));
            })
            ->when($request->filled('teacher_id'), fn ($query) => $query->where('teacher_id', $request->query('teacher_id')))
            ->when($request->filled('class_id'), fn ($query) => $query->where('class_id', $request->query('class_id')))
            ->when($request->filled('course_id'), fn ($query) => $query->where('course_id', $request->query('course_id')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->query('type')))
            ->when($request->query('scope') === 'overdue', fn ($query) => $query->whereNotNull('due_at')->where('due_at', '<', now()))
            ->when($request->query('scope') === 'open', fn ($query) => $query->where(fn ($q) => $q->whereNull('due_at')->orWhere('due_at', '>=', now())))
            ->when($request->query('scope') === 'unassigned', fn ($query) => $query->whereNull('class_id')->whereNull('course_id'))
            ->when($request->query('scope') === 'no_submissions', fn ($query) => $query->doesntHave('submissions'))
            ->when($request->query('scope') === 'grading', fn ($query) => $query->whereHas('submissions', fn ($q) => $q->doesntHave('grades')))
            ->when($state === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when($state === 'active', fn ($query) => $query->whereNull('deleted_at'))
            ->when($request->query('sort') === 'title', fn ($query) => $query->orderBy('title'))
            ->when($request->query('sort') === 'due', fn ($query) => $query->orderByRaw('due_at is null')->orderBy('due_at'))
            ->when($request->query('sort') === 'submissions', fn ($query) => $query->orderByDesc('submissions_count'))
            ->when($request->query('sort') === 'oldest', fn ($query) => $query->oldest())
            ->when(! in_array($request->query('sort'), ['title', 'due', 'submissions', 'oldest'], true), fn ($query) => $query->latest())
            ->paginate(18)
            ->withQueryString();

        $teachers = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name', 'email']);
        $classes = ClassModel::orderBy('name')->get(['id', 'name', 'code', 'teacher_id']);
        $courses = Course::orderBy('name')->get(['id', 'name', 'teacher_id']);
        $summary = [
            'total' => Assignment::withTrashed()->count(),
            'open' => Assignment::where(fn ($query) => $query->whereNull('due_at')->orWhere('due_at', '>=', now()))->count(),
            'overdue' => Assignment::whereNotNull('due_at')->where('due_at', '<', now())->count(),
            'deleted' => Assignment::onlyTrashed()->count(),
            'submissions' => Submission::count(),
            'ungraded' => Submission::doesntHave('grades')->count(),
            'no_submissions' => Assignment::doesntHave('submissions')->count(),
            'unassigned' => Assignment::whereNull('class_id')->whereNull('course_id')->count(),
        ];

        return view('pages.admin.assignments', compact('assignments', 'teachers', 'classes', 'courses', 'summary'));
    }

    public function storeAssignment(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Assignment::create($this->validateAssignment($request));

        return redirect()->route('admin.assignments')->with('success', 'Đã tạo bài tập.');
    }

    public function showAssignment(Request $request, int $id): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $assignment = Assignment::withTrashed()
            ->with(['teacher', 'class.students', 'course.students', 'submissions.student', 'submissions.grades.grader'])
            ->withCount('submissions')
            ->findOrFail($id);

        $submittedStudentIds = $assignment->submissions->pluck('student_id')->all();
        $targetStudents = collect();
        if ($assignment->class) {
            $targetStudents = $targetStudents->merge($assignment->class->students);
        }
        if ($assignment->course) {
            $targetStudents = $targetStudents->merge($assignment->course->students);
        }
        $missingStudents = $targetStudents
            ->unique('id')
            ->reject(fn ($student) => in_array($student->id, $submittedStudentIds, true))
            ->values();

        $teachers = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name', 'email']);
        $classes = ClassModel::orderBy('name')->get(['id', 'name', 'code', 'teacher_id']);
        $courses = Course::orderBy('name')->get(['id', 'name', 'teacher_id']);
        $targetCount = $targetStudents->unique('id')->count();
        $gradedSubmissions = $assignment->submissions->filter(fn ($submission) => $submission->grades->isNotEmpty())->count();

        return view('pages.admin.assignment-show', compact('assignment', 'missingStudents', 'teachers', 'classes', 'courses', 'targetCount', 'gradedSubmissions'));
    }

    public function updateAssignment(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Assignment::withTrashed()->findOrFail($id)->update($this->validateAssignment($request));

        return back()->with('success', 'Đã cập nhật bài tập.');
    }

    public function deleteAssignment(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        Assignment::findOrFail($id)->delete();
        return back()->with('success', 'Đã đưa bài tập vào thùng rác.');
    }

    public function restoreAssignment(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        Assignment::withTrashed()->findOrFail($id)->restore();
        return back()->with('success', 'Đã khôi phục bài tập.');
    }

    public function submissions(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $submissions = Submission::with(['student', 'assignment.teacher', 'assignment.class', 'assignment.course', 'grades.grader'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('assignment', fn ($q) => $q->where('title', 'like', "%{$search}%"))
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhere('attachment', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('student_id'), fn ($query) => $query->where('student_id', $request->query('student_id')))
            ->when($request->filled('assignment_id'), fn ($query) => $query->where('assignment_id', $request->query('assignment_id')))
            ->when($request->filled('teacher_id'), fn ($query) => $query->whereHas('assignment', fn ($q) => $q->where('teacher_id', $request->query('teacher_id'))))
            ->when($request->filled('class_id'), fn ($query) => $query->whereHas('assignment', fn ($q) => $q->where('class_id', $request->query('class_id'))))
            ->when($request->filled('course_id'), fn ($query) => $query->whereHas('assignment', fn ($q) => $q->where('course_id', $request->query('course_id'))))
            ->when($request->query('status') === 'graded', fn ($query) => $query->whereHas('grades'))
            ->when($request->query('status') === 'ungraded', fn ($query) => $query->doesntHave('grades'))
            ->when($request->query('scope') === 'late', fn ($query) => $query->whereExists(function ($subQuery) {
                $subQuery->selectRaw(1)
                    ->from('assignments')
                    ->whereColumn('assignments.id', 'submissions.assignment_id')
                    ->whereNotNull('assignments.due_at')
                    ->whereColumn('submissions.submitted_at', '>', 'assignments.due_at');
            }))
            ->when($request->query('scope') === 'on_time', fn ($query) => $query->where(function ($q) {
                $q->whereDoesntHave('assignment', fn ($assignment) => $assignment->whereNotNull('due_at'))
                    ->orWhereExists(function ($subQuery) {
                        $subQuery->selectRaw(1)
                            ->from('assignments')
                            ->whereColumn('assignments.id', 'submissions.assignment_id')
                            ->where(function ($assignmentQuery) {
                                $assignmentQuery->whereNull('assignments.due_at')
                                    ->orWhereColumn('submissions.submitted_at', '<=', 'assignments.due_at');
                            });
                    });
            }))
            ->when($request->query('scope') === 'attachment', fn ($query) => $query->whereNotNull('attachment')->where('attachment', '<>', ''))
            ->when($request->query('scope') === 'text', fn ($query) => $query->whereNotNull('content')->where('content', '<>', ''))
            ->when($request->query('sort') === 'student', fn ($query) => $query->orderBy(User::select('name')->whereColumn('users.id', 'submissions.student_id')))
            ->when($request->query('sort') === 'oldest', fn ($query) => $query->oldest('submitted_at'))
            ->when(! in_array($request->query('sort'), ['student', 'oldest'], true), fn ($query) => $query->latest('submitted_at'))
            ->paginate(18)
            ->withQueryString();

        $teachers = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name', 'email']);
        $students = User::where('role', 'student')->orderBy('name')->limit(200)->get(['id', 'name', 'email']);
        $assignments = Assignment::orderBy('title')->limit(200)->get(['id', 'title', 'teacher_id', 'total_points']);
        $classes = ClassModel::orderBy('name')->get(['id', 'name', 'code']);
        $courses = Course::orderBy('name')->get(['id', 'name']);
        $summary = [
            'total' => Submission::count(),
            'graded' => Submission::whereHas('grades')->count(),
            'ungraded' => Submission::doesntHave('grades')->count(),
            'late' => Submission::whereExists(function ($query) {
                $query->selectRaw(1)
                    ->from('assignments')
                    ->whereColumn('assignments.id', 'submissions.assignment_id')
                    ->whereNotNull('assignments.due_at')
                    ->whereColumn('submissions.submitted_at', '>', 'assignments.due_at');
            })->count(),
            'attachments' => Submission::whereNotNull('attachment')->where('attachment', '<>', '')->count(),
        ];

        return view('pages.admin.submissions', compact('submissions', 'teachers', 'students', 'assignments', 'classes', 'courses', 'summary'));
    }

    public function gradeSubmission(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $submission = Submission::with('assignment')->findOrFail($id);
        $maxScore = (int) ($submission->assignment?->total_points ?: 100);
        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:'.$maxScore],
            'feedback' => ['nullable', 'string', 'max:3000'],
        ]);

        Grade::updateOrCreate(
            [
                'student_id' => $submission->student_id,
                'gradable_type' => Submission::class,
                'gradable_id' => $submission->id,
            ],
            [
                'score' => $validated['score'],
                'feedback' => $validated['feedback'] ?? null,
                'grader_id' => null,
                'graded_at' => now(),
            ]
        );

        return back()->with('success', 'Đã lưu điểm bài nộp.');
    }

    public function deleteSubmission(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $submission = Submission::with('grades')->findOrFail($id);
        foreach ($submission->grades as $grade) {
            $grade->delete();
        }
        $submission->delete();

        return back()->with('success', 'Đã xóa bài nộp.');
    }

    public function grades(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $grades = Grade::with([
            'student',
            'grader',
            'gradable' => fn ($morphTo) => $morphTo->morphWith([
                Submission::class => ['assignment'],
            ]),
        ])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhere('feedback', 'like', "%{$search}%")
                        ->orWhereHasMorph('gradable', [Quiz::class], fn ($q) => $q->where('title', 'like', "%{$search}%"))
                        ->orWhereHasMorph('gradable', [Submission::class], fn ($q) => $q->whereHas('assignment', fn ($assignment) => $assignment->where('title', 'like', "%{$search}%")));
                });
            })
            ->when($request->filled('student_id'), fn ($query) => $query->where('student_id', $request->query('student_id')))
            ->when($request->filled('grader_id'), fn ($query) => $query->where('grader_id', $request->query('grader_id')))
            ->when($request->query('type') === 'quiz', fn ($query) => $query->where('gradable_type', Quiz::class))
            ->when($request->query('type') === 'assignment', fn ($query) => $query->where('gradable_type', Submission::class))
            ->when($request->query('quality') === 'missing_feedback', fn ($query) => $query->where(fn ($q) => $q->whereNull('feedback')->orWhere('feedback', '')))
            ->when($request->query('band') === 'excellent', fn ($query) => $query->where('score', '>=', 80))
            ->when($request->query('band') === 'pass', fn ($query) => $query->whereBetween('score', [50, 79]))
            ->when($request->query('band') === 'low', fn ($query) => $query->where('score', '<', 50))
            ->when($request->query('sort') === 'score_desc', fn ($query) => $query->orderByDesc('score'))
            ->when($request->query('sort') === 'score_asc', fn ($query) => $query->orderBy('score'))
            ->when($request->query('sort') === 'student', fn ($query) => $query->orderBy(User::select('name')->whereColumn('users.id', 'grades.student_id')))
            ->when($request->query('sort') === 'oldest', fn ($query) => $query->oldest('graded_at'))
            ->when(! in_array($request->query('sort'), ['score_desc', 'score_asc', 'student', 'oldest'], true), fn ($query) => $query->latest('graded_at'))
            ->paginate(18)
            ->withQueryString();

        $students = User::where('role', 'student')->orderBy('name')->limit(200)->get(['id', 'name', 'email']);
        $graders = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name', 'email']);
        $summary = [
            'total' => Grade::count(),
            'quiz' => Grade::where('gradable_type', Quiz::class)->count(),
            'assignment' => Grade::where('gradable_type', Submission::class)->count(),
            'avg' => round((float) Grade::avg('score'), 1),
            'missing_feedback' => Grade::where(fn ($query) => $query->whereNull('feedback')->orWhere('feedback', ''))->count(),
        ];

        return view('pages.admin.grades', compact('grades', 'students', 'graders', 'summary'));
    }

    public function updateGrade(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $grade = Grade::with('gradable')->findOrFail($id);
        $maxScore = $this->gradeMaxScore($grade);
        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:'.$maxScore],
            'feedback' => ['nullable', 'string', 'max:3000'],
        ]);
        $validated['graded_at'] = now();
        $grade->update($validated);

        if ($grade->gradable_type === Quiz::class) {
            DB::table('quiz_user')
                ->where('quiz_id', $grade->gradable_id)
                ->where('user_id', $grade->student_id)
                ->update(['score' => (int) $validated['score'], 'is_graded' => true]);
        }

        return back()->with('success', 'Đã cập nhật điểm.');
    }

    public function deleteGrade(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $grade = Grade::findOrFail($id);
        if ($grade->gradable_type === Quiz::class) {
            DB::table('quiz_user')
                ->where('quiz_id', $grade->gradable_id)
                ->where('user_id', $grade->student_id)
                ->update(['is_graded' => false]);
        }
        $grade->delete();

        return back()->with('success', 'Đã xóa điểm.');
    }

    private function gradeMaxScore(Grade $grade): int
    {
        if ($grade->gradable_type === Quiz::class) {
            return (int) ($grade->gradable?->total_points ?: 100);
        }

        if ($grade->gradable_type === Submission::class) {
            return (int) ($grade->gradable?->assignment?->total_points ?: 100);
        }

        return 1000;
    }

    public function notifications(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $state = $request->query('state', 'active');

        $notifications = Notification::query()
            ->when($state === 'all' || $state === 'deleted', fn ($query) => $query->withTrashed())
            ->with('user')
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->query('type')))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->query('user_id')))
            ->when($request->filled('role'), fn ($query) => $query->whereHas('user', fn ($q) => $q->where('role', $request->query('role'))))
            ->when($request->query('state') === 'read', fn ($query) => $query->where('is_read', true))
            ->when($request->query('state') === 'unread', fn ($query) => $query->where('is_read', false))
            ->when($request->query('state') === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when($state === 'active', fn ($query) => $query->whereNull('deleted_at'))
            ->when($request->query('scope') === 'with_url', fn ($query) => $query->whereNotNull('data->url'))
            ->when($request->query('scope') === 'system', fn ($query) => $query->whereIn('type', ['admin_broadcast', 'system', 'vip']))
            ->when($request->query('scope') === 'learning', fn ($query) => $query->whereIn('type', ['quiz', 'assignment', 'grade', 'grading', 'class', 'course', 'reminder']))
            ->when($request->query('sort') === 'oldest', fn ($query) => $query->oldest())
            ->when($request->query('sort') === 'recipient', fn ($query) => $query->orderBy(User::select('name')->whereColumn('users.id', 'notifications.user_id')))
            ->when($request->query('sort') === 'type', fn ($query) => $query->orderBy('type')->latest())
            ->when(! in_array($request->query('sort'), ['oldest', 'recipient', 'type'], true), fn ($query) => $query->latest())
            ->paginate(18)
            ->withQueryString();
        $users = User::orderBy('name')->limit(200)->get(['id', 'name', 'email', 'role']);
        $types = Notification::query()->select('type')->distinct()->orderBy('type')->pluck('type')->filter()->values();
        $summary = [
            'total' => Notification::withTrashed()->count(),
            'active' => Notification::count(),
            'unread' => Notification::where('is_read', false)->count(),
            'read' => Notification::where('is_read', true)->count(),
            'deleted' => Notification::onlyTrashed()->count(),
            'broadcast' => Notification::where('type', 'admin_broadcast')->count(),
            'today' => Notification::whereDate('created_at', today())->count(),
        ];

        return view('pages.admin.notifications', compact('notifications', 'users', 'types', 'summary'));
    }

    public function storeNotification(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate([
            'target' => ['required', 'in:user,teacher,student,all'],
            'user_id' => ['nullable', 'required_if:target,user', 'exists:users,id'],
            'type' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:3000'],
            'url' => ['nullable', 'string', 'max:1000'],
        ]);

        $query = User::query();
        if ($validated['target'] === 'user') {
            $query->where('id', $validated['user_id']);
        } elseif (in_array($validated['target'], ['teacher', 'student'], true)) {
            $query->where('role', $validated['target']);
        }

        $count = 0;
        $query->chunkById(100, function ($users) use ($validated, &$count) {
            foreach ($users as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'type' => $validated['type'] ?: 'admin_broadcast',
                    'title' => $validated['title'],
                    'body' => $validated['body'] ?? null,
                    'data' => array_filter([
                        'target' => $validated['target'],
                        'url' => $validated['url'] ?? null,
                        'created_by' => 'admin',
                    ]),
                    'is_read' => false,
                ]);
                $count++;
            }
        });

        return back()->with('success', "Đã gửi {$count} thông báo.");
    }

    public function deleteNotification(Request $request, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Notification::findOrFail($id)->delete();

        return back()->with('success', 'Đã đưa thông báo vào thùng rác.');
    }

    public function updateNotificationReadState(Request $request, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate([
            'is_read' => ['required', 'boolean'],
        ]);

        Notification::withTrashed()->findOrFail($id)->update(['is_read' => $validated['is_read']]);

        return back()->with('success', 'Đã cập nhật trạng thái đọc.');
    }

    public function restoreNotification(Request $request, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Notification::withTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Đã khôi phục thông báo.');
    }

    public function tickets(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $tickets = Ticket::with('user')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->query('category')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->query('priority')))
            ->when($request->filled('role'), fn ($query) => $query->whereHas('user', fn ($q) => $q->where('role', $request->query('role'))))
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $query->where(fn ($q) => $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('admin_response', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
            })
            ->when($request->query('scope') === 'unanswered', fn ($query) => $query->whereNull('admin_response'))
            ->when($request->query('scope') === 'answered', fn ($query) => $query->whereNotNull('admin_response'))
            ->when($request->query('scope') === 'active', fn ($query) => $query->whereIn('status', ['open', 'in_progress']))
            ->when($request->query('scope') === 'stale', fn ($query) => $query->whereIn('status', ['open', 'in_progress'])->where('updated_at', '<=', now()->subDays(2)))
            ->when($request->query('scope') === 'vip', fn ($query) => $query->where('priority', 'vip'))
            ->when($request->query('sort') === 'oldest', fn ($query) => $query->oldest())
            ->when($request->query('sort') === 'updated', fn ($query) => $query->orderByDesc('updated_at'))
            ->when($request->query('sort') === 'sender', fn ($query) => $query->orderBy(User::select('name')->whereColumn('users.id', 'tickets.user_id')))
            ->when($request->query('sort') === 'priority', fn ($query) => $query->orderByRaw("CASE WHEN priority = 'vip' THEN 0 ELSE 1 END")->latest())
            ->when(! in_array($request->query('sort'), ['oldest', 'updated', 'sender', 'priority'], true), fn ($query) => $query->orderByRaw("CASE WHEN status = 'open' THEN 0 WHEN status = 'in_progress' THEN 1 WHEN status = 'resolved' THEN 2 ELSE 3 END")->orderByRaw("CASE WHEN priority = 'vip' THEN 0 ELSE 1 END")->latest())
            ->paginate(18)
            ->withQueryString();

        $summary = [
            'open' => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'resolved' => Ticket::where('status', 'resolved')->count(),
            'closed' => Ticket::where('status', 'closed')->count(),
            'vip' => Ticket::where('priority', 'vip')->whereIn('status', ['open', 'in_progress'])->count(),
            'unanswered' => Ticket::whereIn('status', ['open', 'in_progress'])->whereNull('admin_response')->count(),
            'stale' => Ticket::whereIn('status', ['open', 'in_progress'])->where('updated_at', '<=', now()->subDays(2))->count(),
            'today' => Ticket::whereDate('created_at', today())->count(),
        ];
        $categories = [
            'technical' => 'Kỹ thuật',
            'account' => 'Tài khoản',
            'quiz' => 'Bài kiểm tra',
            'grades' => 'Điểm số',
            'classes' => 'Lớp học',
            'quizzes' => 'Bài kiểm tra',
            'questions' => 'Ngân hàng câu hỏi',
            'grading' => 'Chấm điểm',
            'students' => 'Học sinh',
            'analytics' => 'Báo cáo',
            'other' => 'Khác',
        ];

        return view('pages.admin.tickets', compact('tickets', 'summary', 'categories'));
    }

    public function respondTicket(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
            'priority' => 'nullable|in:normal,vip',
            'category' => 'nullable|string|max:80',
            'admin_response' => 'nullable|string|max:3000',
        ]);

        $ticket = Ticket::findOrFail($id);
        $previousResponse = $ticket->admin_response;
        $ticket->update($validated);

        if (! empty($validated['admin_response']) && $validated['admin_response'] !== $previousResponse) {
            Notification::create([
                'user_id' => $ticket->user_id,
                'type' => 'support_ticket',
                'title' => 'Yêu cầu hỗ trợ đã được phản hồi',
                'body' => "Yêu cầu \"{$ticket->subject}\" đã có phản hồi từ quản trị viên.",
                'data' => [
                    'ticket_id' => $ticket->id,
                    'status' => $ticket->status,
                    'url' => route($ticket->user?->isTeacher() ? 'teacher.help' : 'student.help'),
                ],
                'is_read' => false,
            ]);
        }

        return back()->with('success', 'Đã cập nhật yêu cầu hỗ trợ.');
    }

    public function vip(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $subscriptions = VipSubscription::with('user')
            ->when($request->filled('sub_q'), function ($query) use ($request) {
                $search = trim((string) $request->query('sub_q'));
                $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('sub_plan'), fn ($query) => $query->where('plan', $request->query('sub_plan')))
            ->when($request->filled('sub_status'), fn ($query) => $query->where('status', $request->query('sub_status')))
            ->when($request->filled('sub_role'), fn ($query) => $query->whereHas('user', fn ($q) => $q->where('role', $request->query('sub_role'))))
            ->when($request->query('sub_scope') === 'expiring', fn ($query) => $query->where('status', 'active')->whereNotNull('expires_at')->whereBetween('expires_at', [now(), now()->addDays(7)]))
            ->when($request->query('sub_scope') === 'overdue', fn ($query) => $query->where('status', 'active')->whereNotNull('expires_at')->where('expires_at', '<', now()))
            ->when($request->query('sub_sort') === 'expires', fn ($query) => $query->orderByRaw('expires_at is null')->orderBy('expires_at'))
            ->when($request->query('sub_sort') === 'user', fn ($query) => $query->orderBy(User::select('name')->whereColumn('users.id', 'vip_subscriptions.user_id')))
            ->when($request->query('sub_sort') === 'oldest', fn ($query) => $query->oldest())
            ->when(! in_array($request->query('sub_sort'), ['expires', 'user', 'oldest'], true), fn ($query) => $query->latest())
            ->paginate(12, ['*'], 'subscriptions_page')
            ->withQueryString();

        $payments = VipPayment::with(['user', 'subscription'])
            ->when($request->filled('pay_q'), function ($query) use ($request) {
                $search = trim((string) $request->query('pay_q'));
                $query->where(fn ($q) => $q->where('txn_ref', 'like', "%{$search}%")
                    ->orWhere('vnp_transaction_no', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
            })
            ->when($request->filled('pay_plan'), fn ($query) => $query->where('plan', $request->query('pay_plan')))
            ->when($request->filled('pay_status'), fn ($query) => $query->where('status', $request->query('pay_status')))
            ->when($request->filled('pay_role'), fn ($query) => $query->whereHas('user', fn ($q) => $q->where('role', $request->query('pay_role'))))
            ->when($request->query('pay_scope') === 'needs_reconcile', fn ($query) => $query->where('status', 'paid')->whereNull('vip_subscription_id'))
            ->when($request->query('pay_scope') === 'recent_paid', fn ($query) => $query->where('status', 'paid')->where('paid_at', '>=', now()->subDays(7)))
            ->when($request->query('pay_sort') === 'amount', fn ($query) => $query->orderByDesc('amount'))
            ->when($request->query('pay_sort') === 'paid_at', fn ($query) => $query->orderByDesc('paid_at'))
            ->when($request->query('pay_sort') === 'oldest', fn ($query) => $query->oldest())
            ->when(! in_array($request->query('pay_sort'), ['amount', 'paid_at', 'oldest'], true), fn ($query) => $query->latest())
            ->paginate(12, ['*'], 'payments_page')
            ->withQueryString();

        $summary = [
            'active' => VipSubscription::where('status', 'active')->count(),
            'expiring' => VipSubscription::where('status', 'active')->whereNotNull('expires_at')->whereBetween('expires_at', [now(), now()->addDays(7)])->count(),
            'overdue' => VipSubscription::where('status', 'active')->whereNotNull('expires_at')->where('expires_at', '<', now())->count(),
            'pending' => VipPayment::where('status', 'pending')->count(),
            'paid' => VipPayment::where('status', 'paid')->count(),
            'failed' => VipPayment::whereIn('status', ['failed', 'cancelled'])->count(),
            'revenue_30d' => VipPayment::where('status', 'paid')->where('paid_at', '>=', now()->subDays(30))->sum('amount'),
            'revenue_total' => VipPayment::where('status', 'paid')->sum('amount'),
        ];
        $eligibleUsers = User::whereIn('role', ['teacher', 'student'])->orderBy('name')->limit(200)->get(['id', 'name', 'email', 'role']);
        $this->ensureVipPlansExist();
        $vipPlans = VipPlan::orderBy('audience')->orderBy('sort_order')->get();
        $vipPromotions = Promotion::query()
            ->whereNotNull('vip_plan')
            ->latest()
            ->limit(8)
            ->get();

        return view('pages.admin.vip', compact('subscriptions', 'payments', 'summary', 'eligibleUsers', 'vipPlans', 'vipPromotions'));
    }

    public function updateVipPlan(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $plan = VipPlan::findOrFail($id);
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:0', 'max:100000000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        $plan->update($validated);

        return back()->with('success', 'Đã cập nhật giá gói VIP.');
    }

    public function storeSubscription(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'plan' => ['required', Rule::in(['monthly', 'yearly', 'lifetime'])],
            'status' => ['required', Rule::in(['active', 'expired', 'cancelled'])],
            'started_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:started_at'],
        ]);

        abort_unless(User::whereKey($validated['user_id'])->whereIn('role', ['teacher', 'student'])->exists(), 422);

        $startedAt = isset($validated['started_at']) ? \Illuminate\Support\Carbon::parse($validated['started_at']) : now();
        VipSubscription::updateOrCreate(
            ['user_id' => $validated['user_id']],
            [
                'plan' => $validated['plan'],
                'status' => $validated['status'],
                'started_at' => $startedAt,
                'expires_at' => $this->vipSubscriptionExpiresAt($validated['plan'], $startedAt, $validated['expires_at'] ?? null),
            ]
        );

        return back()->with('success', 'Đã cấp quyền VIP.');
    }

    public function updateSubscription(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $subscription = VipSubscription::findOrFail($id);
        $validated = $request->validate([
            'plan' => ['required', Rule::in(['monthly', 'yearly', 'lifetime'])],
            'status' => ['required', Rule::in(['active', 'expired', 'cancelled'])],
            'started_at' => ['required', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:started_at'],
        ]);

        $startedAt = \Illuminate\Support\Carbon::parse($validated['started_at']);
        $subscription->update([
            'plan' => $validated['plan'],
            'status' => $validated['status'],
            'started_at' => $startedAt,
            'expires_at' => $this->vipSubscriptionExpiresAt($validated['plan'], $startedAt, $validated['expires_at'] ?? null),
        ]);
        return back()->with('success', 'Đã cập nhật gói VIP.');
    }

    public function updatePayment(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate(['status' => 'required|in:pending,paid,failed,cancelled']);
        $payment = VipPayment::findOrFail($id);
        if ($validated['status'] === 'paid') {
            $this->activateSubscriptionFromPayment($payment);
        } else {
            $payment->update(['status' => $validated['status']]);
        }

        return back()->with('success', 'Đã cập nhật thanh toán.');
    }

    private function activateSubscriptionFromPayment(VipPayment $payment): void
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

    private function vipSubscriptionExpiresAt(string $plan, \Illuminate\Support\Carbon $startedAt, ?string $manualExpiresAt = null): ?\Illuminate\Support\Carbon
    {
        if ($plan === 'lifetime') {
            return null;
        }

        if ($manualExpiresAt) {
            return \Illuminate\Support\Carbon::parse($manualExpiresAt);
        }

        return $plan === 'yearly' ? $startedAt->copy()->addYear() : $startedAt->copy()->addMonth();
    }

    private function ensureVipPlansExist(): void
    {
        foreach (VipPlan::defaults() as $plan) {
            VipPlan::firstOrCreate(
                ['audience' => $plan['audience'], 'plan' => $plan['plan']],
                $plan
            );
        }
    }

    public function promotions(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $state = $request->query('state', 'active');

        $promotions = Promotion::query()
            ->when($state === 'all' || $state === 'deleted', fn ($query) => $query->withTrashed())
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('discount_type'), fn ($query) => $query->where('discount_type', $request->query('discount_type')))
            ->when($request->filled('vip_plan'), fn ($query) => $query->where('vip_plan', $request->query('vip_plan')))
            ->when($request->query('scope') === 'vip', fn ($query) => $query->whereNotNull('vip_plan'))
            ->when($request->query('scope') === 'general', fn ($query) => $query->whereNull('vip_plan'))
            ->when($request->query('scope') === 'running', fn ($query) => $query->where('status', 'active')
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit')))
            ->when($request->query('scope') === 'scheduled', fn ($query) => $query->where('status', 'active')->whereNotNull('starts_at')->where('starts_at', '>', now()))
            ->when($request->query('scope') === 'expired', fn ($query) => $query->whereNotNull('ends_at')->where('ends_at', '<', now()))
            ->when($request->query('scope') === 'exhausted', fn ($query) => $query->whereNotNull('usage_limit')->whereColumn('used_count', '>=', 'usage_limit'))
            ->when($request->query('state') === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when($state === 'active', fn ($query) => $query->whereNull('deleted_at'))
            ->when($request->query('sort') === 'code', fn ($query) => $query->orderBy('code'))
            ->when($request->query('sort') === 'ending', fn ($query) => $query->orderByRaw('ends_at is null')->orderBy('ends_at'))
            ->when($request->query('sort') === 'usage', fn ($query) => $query->orderByDesc('used_count'))
            ->when($request->query('sort') === 'value', fn ($query) => $query->orderByDesc('discount_value'))
            ->when($request->query('sort') === 'oldest', fn ($query) => $query->oldest())
            ->when(! in_array($request->query('sort'), ['code', 'ending', 'usage', 'value', 'oldest'], true), fn ($query) => $query->latest())
            ->paginate(18)
            ->withQueryString();

        $summary = [
            'total' => Promotion::withTrashed()->count(),
            'active' => Promotion::where('status', 'active')->count(),
            'inactive' => Promotion::where('status', 'inactive')->count(),
            'running' => Promotion::where('status', 'active')
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->where(fn ($query) => $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
                ->count(),
            'scheduled' => Promotion::where('status', 'active')->whereNotNull('starts_at')->where('starts_at', '>', now())->count(),
            'expired' => Promotion::whereNotNull('ends_at')->where('ends_at', '<', now())->count(),
            'exhausted' => Promotion::whereNotNull('usage_limit')->whereColumn('used_count', '>=', 'usage_limit')->count(),
            'vip' => Promotion::whereNotNull('vip_plan')->count(),
            'deleted' => Promotion::onlyTrashed()->count(),
        ];

        return view('pages.admin.promotions', compact('promotions', 'summary'));
    }

    public function storePromotion(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Promotion::create($this->validatePromotion($request));

        return back()->with('success', 'Đã tạo khuyến mãi.');
    }

    public function updatePromotion(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Promotion::withTrashed()->findOrFail($id)->update($this->validatePromotion($request, $id));

        return back()->with('success', 'Đã cập nhật khuyến mãi.');
    }

    public function deletePromotion(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Promotion::findOrFail($id)->delete();

        return back()->with('success', 'Đã đưa khuyến mãi vào thùng rác.');
    }

    public function restorePromotion(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Promotion::withTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Đã khôi phục khuyến mãi.');
    }

    public function trash(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $map = $this->trashMap();
        $type = array_key_exists($request->query('type', 'all'), $map) ? $request->query('type', 'all') : 'all';
        $queryText = trim((string) $request->query('q', ''));
        $age = $request->query('age', 'all');
        $sort = $request->query('sort', 'latest');

        $allItems = $this->adminTrashItems($map);
        $summary = [
            'total' => $allItems->count(),
            'today' => $allItems->where('age_days', 0)->count(),
            'week' => $allItems->where('age_days', '<=', 7)->count(),
            'expiring' => $allItems->where('is_expiring', true)->count(),
            'old' => $allItems->where('age_days', '>', 30)->count(),
        ];
        $typeCounts = collect($map)->map(fn ($config, $key) => $allItems->where('type', $key)->count())->all();

        $items = $allItems
            ->when($type !== 'all', fn ($items) => $items->where('type', $type))
            ->when($queryText !== '', function ($items) use ($queryText) {
                $needle = Str::lower($queryText);

                return $items->filter(fn ($item) => Str::contains(Str::lower($item->search_text), $needle));
            })
            ->when($age === 'today', fn ($items) => $items->where('age_days', 0))
            ->when($age === 'week', fn ($items) => $items->filter(fn ($item) => $item->age_days <= 7))
            ->when($age === 'month', fn ($items) => $items->filter(fn ($item) => $item->age_days <= 30))
            ->when($age === 'old', fn ($items) => $items->filter(fn ($item) => $item->age_days > 30));

        $items = match ($sort) {
            'oldest' => $items->sortBy('deleted_at'),
            'type' => $items->sortBy([['type_label', 'asc'], ['deleted_at', 'desc']]),
            'name' => $items->sortBy('title'),
            default => $items->sortByDesc('deleted_at'),
        };

        $items = $items->values();

        return view('pages.admin.trash', compact('items', 'summary', 'typeCounts', 'type', 'map', 'queryText', 'age', 'sort'));
    }

    public function restoreTrashItem(Request $request, string $type, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $map = $this->trashMap();
        abort_unless(isset($map[$type]), 404);

        $map[$type]['model']::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Đã khôi phục dữ liệu.');
    }

    public function forceDeleteTrashItem(Request $request, string $type, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $map = $this->trashMap();
        abort_unless(isset($map[$type]), 404);

        $map[$type]['model']::onlyTrashed()->findOrFail($id)->forceDelete();

        return back()->with('success', 'Da xoa vinh vien du lieu.');
    }

    public function restoreAllTrashItems(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $map = $this->trashMap();
        $type = (string) $request->input('type', 'all');
        $targets = array_key_exists($type, $map) ? [$type => $map[$type]] : $map;

        foreach ($targets as $config) {
            $config['model']::onlyTrashed()->restore();
        }

        return redirect()->route('admin.trash')->with('success', 'Da khoi phuc tat ca du lieu trong thung rac.');
    }

    public function forceDeleteAllTrashItems(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $map = $this->trashMap();
        $type = (string) $request->input('type', 'all');
        $targets = array_key_exists($type, $map) ? [$type => $map[$type]] : $map;

        foreach ($targets as $config) {
            $config['model']::onlyTrashed()->forceDelete();
        }

        return redirect()->route('admin.trash')->with('success', 'Da xoa vinh vien tat ca du lieu trong thung rac.');
    }

    public function restoreSelectedTrashItems(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $count = $this->performSelectedTrashAction($request, 'restore');

        return back()->with('success', "Da khoi phuc {$count} muc da chon.");
    }

    public function forceDeleteSelectedTrashItems(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $count = $this->performSelectedTrashAction($request, 'forceDelete');

        return back()->with('success', "Da xoa vinh vien {$count} muc da chon.");
    }

    private function adminAnalyticsPayload(Request $request): array
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

    private function analyticsExportSheets(array $data): array
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

    private function writeSheet($sheet, string $title, array $rows): void
    {
        $sheet->setTitle(mb_substr($title, 0, 31));
        $sheet->fromArray($rows, null, 'A1');
        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$highestColumn}1")->getFont()->setBold(true);
        foreach (range('A', $highestColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function generateClassCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (ClassModel::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    private function validateQuestion(Request $request): array
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

    private function validateAssignment(Request $request): array
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

    private function validateQuestionFolder(Request $request, ?int $id = null): array
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

    private function validatePromotion(Request $request, ?int $id = null): array
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

    private function trashMap(): array
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

    private function adminTrashItems(array $map)
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

    private function toAdminTrashItem($item, string $type, array $config): object
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

    private function adminTrashDescription($item, string $type): ?string
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

    private function adminTrashOwner($item, string $type): ?string
    {
        return match ($type) {
            'users' => $item->email,
            'classes', 'courses', 'quizzes', 'questions', 'assignments' => $item->teacher?->name,
            'notifications' => $item->user?->name,
            'promotions' => $item->name,
            default => null,
        };
    }

    private function adminTrashDetailRoute(string $type, int|string $id): ?string
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

    private function performSelectedTrashAction(Request $request, string $action): int
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

    private function isAdmin(Request $request): bool
    {
        if (Auth::guard('web')->check()) {
            $request->session()->forget(self::SESSION_KEY);

            return false;
        }

        return (bool) $request->session()->get(self::SESSION_KEY, false);
    }

    private function requireAdmin(Request $request): ?RedirectResponse
    {
        return $this->isAdmin($request) ? null : redirect()->route('admin.dashboard');
    }
}
