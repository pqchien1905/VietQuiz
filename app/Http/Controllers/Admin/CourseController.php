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

class CourseController extends AdminBaseController
{
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

        return redirect()->route('admin.courses')->with('success', 'ÄÃ£ táº¡o khÃ³a há»c.');
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
        return back()->with('success', 'ÄÃ£ cáº­p nháº­t khÃ³a há»c.');
    }

    public function addCourseStudent(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate(['student_id' => ['required', 'exists:users,id']]);
        $student = User::where('role', 'student')->findOrFail($validated['student_id']);
        Course::withTrashed()->findOrFail($id)->students()->syncWithoutDetaching([
            $student->id => ['enrolled_at' => now()],
        ]);

        return back()->with('success', 'ÄÃ£ ghi danh há»c sinh vÃ o khÃ³a há»c.');
    }

    public function syncCourseStudents(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $course = Course::withTrashed()->with('classModel.students')->findOrFail($id);
        if (! $course->classModel) {
            return back()->withErrors(['course' => 'KhÃ³a há»c chÆ°a gáº¯n lá»›p Ä‘á»ƒ Ä‘á»“ng bá»™ há»c sinh.']);
        }

        $students = $course->classModel->students
            ->mapWithKeys(fn ($student) => [$student->id => ['enrolled_at' => now()]])
            ->all();
        $course->students()->syncWithoutDetaching($students);

        return back()->with('success', 'ÄÃ£ Ä‘á»“ng bá»™ há»c sinh tá»« lá»›p sang khÃ³a há»c.');
    }

    public function removeCourseStudent(Request $request, int $id, int $studentId): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Course::withTrashed()->findOrFail($id)->students()->detach($studentId);

        return back()->with('success', 'ÄÃ£ gá»¡ há»c sinh khá»i khÃ³a há»c.');
    }

    public function deleteCourse(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        Course::findOrFail($id)->delete();
        return back()->with('success', 'ÄÃ£ Ä‘Æ°a khÃ³a há»c vÃ o thÃ¹ng rÃ¡c.');
    }

    public function restoreCourse(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;
        Course::withTrashed()->findOrFail($id)->restore();
        return back()->with('success', 'ÄÃ£ khÃ´i phá»¥c khÃ³a há»c.');
    }
}

