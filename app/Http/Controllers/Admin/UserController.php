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

class UserController extends AdminBaseController
{
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

        return redirect()->route('admin.users')->with('success', 'ÄÃ£ táº¡o tÃ i khoáº£n ngÆ°á»i dÃ¹ng.');
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

        return back()->with('success', 'ÄÃ£ cáº­p nháº­t tÃ i khoáº£n.');
    }

    public function deleteUser(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        User::findOrFail($id)->delete();
        return back()->with('success', 'ÄÃ£ khÃ³a tÃ i khoáº£n.');
    }

    public function restoreUser(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        User::withTrashed()->findOrFail($id)->restore();
        return back()->with('success', 'ÄÃ£ khÃ´i phá»¥c tÃ i khoáº£n.');
    }
}

