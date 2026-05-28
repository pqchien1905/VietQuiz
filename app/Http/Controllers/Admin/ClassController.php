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

class ClassController extends AdminBaseController
{
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
        return back()->with('success', 'Đã xóa lớp học khỏi danh sách hoạt động.');
    }

    public function restoreClass(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        ClassModel::withTrashed()->findOrFail($id)->restore();
        return back()->with('success', 'Đã khôi phục lớp học.');
    }
}

