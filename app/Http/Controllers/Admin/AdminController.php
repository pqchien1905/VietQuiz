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
use App\Models\VipSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

        $quizzes = Quiz::withTrashed()
            ->with(['teacher', 'course', 'classModel'])
            ->withCount('questions')
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%' . $request->query('q') . '%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->latest()
            ->paginate(18)
            ->withQueryString();

        return view('pages.admin.quizzes', compact('quizzes'));
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
        $teachers = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name', 'email']);
        $classes = ClassModel::orderBy('name')->get(['id', 'name']);
        $courses = Course::orderBy('name')->get(['id', 'name']);

        return view('pages.admin.quiz-show', compact('quiz', 'attempts', 'teachers', 'classes', 'courses'));
    }

    public function updateQuiz(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        $validated = $request->validate([
            'teacher_id' => ['nullable', 'exists:users,id'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'passing_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['required', 'in:draft,published,closed'],
        ]);
        Quiz::withTrashed()->findOrFail($id)->update(array_filter($validated, fn ($value) => $value !== null));
        return back()->with('success', 'Đã cập nhật bài kiểm tra.');
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

        $questions = Question::withTrashed()
            ->with(['teacher', 'quiz', 'folder'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->query('q');
                $query->where(fn ($q) => $q->where('content', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%"));
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->query('type')))
            ->when($request->query('scope') === 'bank', fn ($query) => $query->whereNull('quiz_id'))
            ->when($request->query('scope') === 'quiz', fn ($query) => $query->whereNotNull('quiz_id'))
            ->when($request->query('state') === 'deleted', fn ($query) => $query->onlyTrashed())
            ->latest()
            ->paginate(18)
            ->withQueryString();

        $teachers = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name', 'email']);
        $quizzes = Quiz::orderBy('title')->limit(100)->get(['id', 'title', 'teacher_id']);
        $folders = QuestionFolder::with('teacher')->orderBy('name')->get();
        $summary = [
            'total' => Question::withTrashed()->count(),
            'bank' => Question::whereNull('quiz_id')->count(),
            'quiz' => Question::whereNotNull('quiz_id')->count(),
            'deleted' => Question::onlyTrashed()->count(),
        ];

        return view('pages.admin.questions', compact('questions', 'teachers', 'quizzes', 'folders', 'summary'));
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

        $assignments = Assignment::withTrashed()
            ->with(['teacher', 'class', 'course'])
            ->withCount('submissions')
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%' . $request->query('q') . '%'))
            ->latest()
            ->paginate(18)
            ->withQueryString();

        return view('pages.admin.assignments', compact('assignments'));
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

        return view('pages.admin.assignment-show', compact('assignment', 'missingStudents'));
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
                $search = $request->query('q');
                $query->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('assignment', fn ($q) => $q->where('title', 'like', "%{$search}%"));
            })
            ->latest('submitted_at')
            ->paginate(18)
            ->withQueryString();

        return view('pages.admin.submissions', compact('submissions'));
    }

    public function grades(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $grades = Grade::with(['student', 'grader', 'gradable'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->query('q');
                $query->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->latest('graded_at')
            ->paginate(18)
            ->withQueryString();

        return view('pages.admin.grades', compact('grades'));
    }

    public function updateGrade(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:1000'],
            'feedback' => ['nullable', 'string', 'max:3000'],
        ]);
        $validated['graded_at'] = now();
        Grade::findOrFail($id)->update($validated);

        return back()->with('success', 'Đã cập nhật điểm.');
    }

    public function notifications(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $notifications = Notification::withTrashed()
            ->with('user')
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->query('q');
                $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%"));
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->query('type')))
            ->when($request->query('state') === 'unread', fn ($query) => $query->where('is_read', false))
            ->when($request->query('state') === 'deleted', fn ($query) => $query->onlyTrashed())
            ->latest()
            ->paginate(18)
            ->withQueryString();
        $users = User::orderBy('name')->limit(100)->get(['id', 'name', 'email', 'role']);

        return view('pages.admin.notifications', compact('notifications', 'users'));
    }

    public function storeNotification(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate([
            'target' => ['required', 'in:user,teacher,student,all'],
            'user_id' => ['nullable', 'required_if:target,user', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:3000'],
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
                    'type' => 'admin_broadcast',
                    'title' => $validated['title'],
                    'body' => $validated['body'] ?? null,
                    'data' => ['target' => $validated['target']],
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
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->query('q');
                $query->where(fn ($q) => $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(18)
            ->withQueryString();

        $summary = [
            'open' => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'resolved' => Ticket::where('status', 'resolved')->count(),
            'closed' => Ticket::where('status', 'closed')->count(),
        ];

        return view('pages.admin.tickets', compact('tickets', 'summary'));
    }

    public function respondTicket(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
            'admin_response' => 'nullable|string|max:3000',
        ]);

        $ticket = Ticket::findOrFail($id);
        $ticket->update($validated);

        if (! empty($validated['admin_response'])) {
            Notification::create([
                'user_id' => $ticket->user_id,
                'type' => 'support_ticket',
                'title' => 'Yêu cầu hỗ trợ đã được phản hồi',
                'body' => "Yêu cầu \"{$ticket->subject}\" đã có phản hồi từ quản trị viên.",
                'data' => ['ticket_id' => $ticket->id, 'status' => $ticket->status],
                'is_read' => false,
            ]);
        }

        return back()->with('success', 'Đã cập nhật yêu cầu hỗ trợ.');
    }

    public function vip(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $subscriptions = VipSubscription::with('user')->latest()->paginate(12, ['*'], 'subscriptions_page')->withQueryString();
        $payments = VipPayment::with('user')->latest()->paginate(12, ['*'], 'payments_page')->withQueryString();

        return view('pages.admin.vip', compact('subscriptions', 'payments'));
    }

    public function updateSubscription(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        VipSubscription::findOrFail($id)->update($request->validate([
            'plan' => 'required|in:monthly,yearly,lifetime',
            'status' => 'required|in:active,expired,cancelled',
        ]));
        return back()->with('success', 'Đã cập nhật gói VIP.');
    }

    public function updatePayment(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate(['status' => 'required|in:pending,paid,failed,cancelled']);
        $payment = VipPayment::findOrFail($id);
        $payment->update([
            'status' => $validated['status'],
            'paid_at' => $validated['status'] === 'paid' ? ($payment->paid_at ?? now()) : $payment->paid_at,
        ]);

        return back()->with('success', 'Đã cập nhật thanh toán.');
    }

    public function promotions(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $promotions = Promotion::withTrashed()
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->query('q');
                $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->query('state') === 'deleted', fn ($query) => $query->onlyTrashed())
            ->latest()
            ->paginate(18)
            ->withQueryString();

        $summary = [
            'total' => Promotion::withTrashed()->count(),
            'active' => Promotion::where('status', 'active')->count(),
            'inactive' => Promotion::where('status', 'inactive')->count(),
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

        $type = $request->query('type', 'all');
        $items = collect();
        $map = $this->trashMap();

        foreach ($map as $key => $config) {
            if ($type !== 'all' && $type !== $key) {
                continue;
            }

            $config['model']::onlyTrashed()
                ->latest('deleted_at')
                ->limit(40)
                ->get()
                ->each(function ($item) use ($key, $config, $items) {
                    $items->push([
                        'type' => $key,
                        'label' => $config['label'],
                        'id' => $item->getKey(),
                        'title' => $item->{$config['title']} ?? ('#' . $item->getKey()),
                        'deleted_at' => $item->deleted_at,
                    ]);
                });
        }

        $items = $items->sortByDesc('deleted_at')->values();
        $summary = collect($map)->map(fn ($config) => $config['model']::onlyTrashed()->count())->all();

        return view('pages.admin.trash', compact('items', 'summary', 'type'));
    }

    public function restoreTrashItem(Request $request, string $type, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $map = $this->trashMap();
        abort_unless(isset($map[$type]), 404);

        $map[$type]['model']::withTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Đã khôi phục dữ liệu.');
    }

    public function system(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $system = [
            'PHP' => PHP_VERSION,
            'Laravel' => app()->version(),
            'Môi trường' => [
                'local' => 'Cục bộ',
                'production' => 'Sản xuất',
                'staging' => 'Thử nghiệm',
                'testing' => 'Kiểm thử',
            ][app()->environment()] ?? app()->environment(),
            'Chế độ gỡ lỗi' => config('app.debug') ? 'Bật' : 'Tắt',
            'Hàng đợi' => [
                'sync' => 'Đồng bộ',
                'database' => 'Cơ sở dữ liệu',
                'redis' => 'Redis',
            ][config('queue.default')] ?? config('queue.default'),
            'Bộ nhớ đệm' => [
                'array' => 'Bộ nhớ tạm',
                'file' => 'Tệp',
                'database' => 'Cơ sở dữ liệu',
                'redis' => 'Redis',
            ][config('cache.default')] ?? config('cache.default'),
            'Cơ sở dữ liệu' => [
                'mysql' => 'MySQL',
                'sqlite' => 'SQLite',
                'pgsql' => 'PostgreSQL',
                'sqlsrv' => 'SQL Server',
            ][config('database.default')] ?? config('database.default'),
            'Quyền ghi lưu trữ' => is_writable(storage_path()) ? 'Có' : 'Không',
        ];
        $totals = [
            'Câu hỏi' => Question::count(),
            'Thông báo' => Notification::count(),
            'Lượt làm bài kiểm tra' => DB::table('quiz_user')->count(),
            'Ghi danh khóa học' => DB::table('course_user')->count(),
            'Thành viên lớp' => DB::table('class_user')->count(),
        ];

        return view('pages.admin.system', compact('system', 'totals'));
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

        $validated['options'] = array_values(array_filter($validated['options'] ?? [], fn ($option) => trim((string) $option) !== ''));
        if ($validated['type'] !== 'multiple_choice') {
            $validated['options'] = [];
        }
        $validated['points'] = $validated['points'] ?? 1;

        return $validated;
    }

    private function validatePromotion(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('promotions', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'used_count' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'in:active,inactive'],
        ]);
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
