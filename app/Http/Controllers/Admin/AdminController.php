<?php

namespace App\Http\Controllers\Admin;

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

class AdminController extends AdminBaseController
{
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
            ['label' => 'Mail', 'ok' => filled(config('mail.default')), 'detail' => config('mail.default') ?: 'ChÆ°a cáº¥u hÃ¬nh'],
            ['label' => 'Queue', 'ok' => filled(config('queue.default')), 'detail' => config('queue.default') ?: 'ChÆ°a cáº¥u hÃ¬nh'],
            ['label' => 'Cache', 'ok' => filled(config('cache.default')), 'detail' => config('cache.default') ?: 'ChÆ°a cáº¥u hÃ¬nh'],
            ['label' => 'VNPay', 'ok' => filled(config('services.vnpay.tmn_code')) && filled(config('services.vnpay.hash_secret')), 'detail' => filled(config('services.vnpay.tmn_code')) ? 'ÄÃ£ cÃ³ mÃ£ merchant' : 'Thiáº¿u mÃ£ merchant'],
            ['label' => 'AI', 'ok' => filled(config('services.openai.api_key')), 'detail' => filled(config('services.openai.api_key')) ? 'ÄÃ£ cáº¥u hÃ¬nh API key' : 'ChÆ°a cáº¥u hÃ¬nh API key'],
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
            'username' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $admin = User::where('email', $validated['username'])
            ->where('role', 'admin')
            ->first();

        if (! $admin || ! Hash::check($validated['password'], $admin->password)) {
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
                    'label' => 'NgÆ°á»i dÃ¹ng',
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
                            'description' => trim(implode(' Â· ', array_filter([$user->email, $user->role, $user->phone]))),
                            'href' => route('admin.users.show', $user->id),
                            'badge' => $user->trashed() ? 'ÄÃ£ khÃ³a' : 'Hoáº¡t Ä‘á»™ng',
                        ]),
                ],
                [
                    'label' => 'Lá»›p há»c',
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
                            'description' => trim(implode(' Â· ', array_filter([$class->code, $class->teacher?->name, $class->subject]))),
                            'href' => route('admin.classes.show', $class->id),
                            'badge' => $class->status,
                        ]),
                ],
                [
                    'label' => 'KhÃ³a há»c',
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
                            'description' => trim(implode(' Â· ', array_filter([$course->teacher?->name, $course->classModel?->name]))),
                            'href' => route('admin.courses.show', $course->id),
                            'badge' => $course->status,
                        ]),
                ],
                [
                    'label' => 'BÃ i kiá»ƒm tra',
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
                            'description' => trim(implode(' Â· ', array_filter([$quiz->teacher?->name, $quiz->duration_minutes ? $quiz->duration_minutes.' phÃºt' : null]))),
                            'href' => route('admin.quizzes.show', $quiz->id),
                            'badge' => $quiz->status,
                        ]),
                ],
                [
                    'label' => 'BÃ i táº­p',
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
                            'description' => trim(implode(' Â· ', array_filter([$assignment->teacher?->name, $assignment->class?->name, $assignment->course?->name]))),
                            'href' => route('admin.assignments.show', $assignment->id),
                            'badge' => $assignment->type,
                        ]),
                ],
                [
                    'label' => 'CÃ¢u há»i',
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
                            'description' => trim(implode(' Â· ', array_filter([$question->teacher?->name, $question->quiz?->title, $question->subject]))),
                            'href' => route('admin.questions', ['q' => $queryText]),
                            'badge' => $question->type,
                        ]),
                ],
                [
                    'label' => 'Há»— trá»£',
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
                            'description' => trim(implode(' Â· ', array_filter([$ticket->user?->email, $ticket->category, $ticket->priority]))),
                            'href' => route('admin.tickets', ['q' => $queryText]),
                            'badge' => $ticket->status,
                        ]),
                ],
                [
                    'label' => 'Khuyáº¿n mÃ£i',
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
}

