<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $classes = $user->createdClasses()
            ->orderBy('name')
            ->get(['id', 'name', 'subject']);

        $coursesQuery = $user->createdCourses()
            ->with('classModel:id,name,subject')
            ->withCount(['students', 'quizzes', 'assignments'])
            ->withMax('quizzes', 'created_at')
            ->withMax('assignments', 'created_at')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));

                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('classModel', function ($classQuery) use ($search) {
                            $classQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('subject', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('class_id'), function ($query) use ($request) {
                $query->where('class_id', $request->integer('class_id'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', (string) $request->string('status'));
            })
            ->latest();

        $allCourses = (clone $coursesQuery)->get();
        $courses = (clone $coursesQuery)->paginate(12)->withQueryString();

        $coursesData = $courses->getCollection()->map(fn ($course) => [
            'id' => $course->id,
            'name' => $course->name,
            'description' => $course->description ?? '',
            'class_id' => $course->class_id,
            'class_name' => $course->classModel?->name ?? '',
            'color' => $course->color ?? '#2563eb',
            'status' => $course->status ?? 'draft',
            'detail_url' => route('teacher.courses.show', $course),
            'update_url' => route('teacher.courses.update', $course),
            'delete_url' => route('teacher.courses.destroy', $course),
            'publish_url' => route('teacher.courses.publish', $course),
            'unpublish_url' => route('teacher.courses.unpublish', $course),
            'duplicate_url' => route('teacher.courses.duplicate', $course),
            'sync_students_url' => route('teacher.courses.sync-students', $course),
        ])->values();

        return view('pages.teacher.courses', [
            'courses' => $courses,
            'classes' => $classes,
            'coursesData' => $coursesData,
            'publishedCount' => $allCourses->where('status', 'published')->count(),
            'draftCount' => $allCourses->where('status', 'draft')->count(),
            'totalStudents' => $allCourses->sum('students_count'),
            'totalMaterials' => $allCourses->sum('quizzes_count') + $allCourses->sum('assignments_count'),
            'openModal' => session('open_modal'),
            'editCourseId' => session('edit_course_id'),
        ]);
    }

    public function show(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);

        $course->load([
            'classModel.students',
            'students',
            'quizzes.questions',
            'quizzes.attempts',
            'assignments.submissions',
        ]);
        $course->loadCount(['students', 'quizzes', 'assignments']);

        $classes = $request->user()->createdClasses()
            ->orderBy('name')
            ->get(['id', 'name', 'subject']);

        $quizAttempts = $course->quizzes->flatMap->attempts;
        $avgScore = $quizAttempts->count() > 0
            ? round($quizAttempts->avg(fn ($student) => $student->pivot->total_points > 0
                ? ($student->pivot->score / $student->pivot->total_points) * 100
                : 0))
            : null;

        $publishedQuizzes = $course->quizzes->where('status', 'published')->count();
        $draftQuizzes = $course->quizzes->where('status', 'draft')->count();
        $submittedAssignments = $course->assignments->sum(fn ($assignment) => $assignment->submissions->count());
        $latestActivity = collect([
            $course->updated_at,
            $course->quizzes->max('updated_at'),
            $course->assignments->max('updated_at'),
        ])->filter()->max();

        $courseData = [
            'id' => $course->id,
            'name' => $course->name,
            'description' => $course->description ?? '',
            'class_id' => $course->class_id,
            'class_name' => $course->classModel?->name ?? '',
            'color' => $course->color ?? '#2563eb',
            'status' => $course->status ?? 'draft',
            'update_url' => route('teacher.courses.update', $course),
            'delete_url' => route('teacher.courses.destroy', $course),
        ];

        return view('pages.teacher.course-detail', [
            'course' => $course,
            'classes' => $classes,
            'courseData' => $courseData,
            'avgScore' => $avgScore,
            'publishedQuizzes' => $publishedQuizzes,
            'draftQuizzes' => $draftQuizzes,
            'submittedAssignments' => $submittedAssignments,
            'latestActivity' => $latestActivity,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'class_id' => ['nullable', 'integer', Rule::exists('classes', 'id')->where('teacher_id', $user->id)],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'status' => 'nullable|in:draft,published',
        ], $this->messages());

        if ($validator->fails()) {
            return $this->validationBack($validator, 'create-modal');
        }

        $validated = $validator->validated();
        $validated['teacher_id'] = $user->id;
        $validated['color'] = $validated['color'] ?? '#2563eb';
        $validated['status'] = $validated['status'] ?? 'draft';

        $user->createdCourses()->create($validated);

        return back()->with('success', 'Tạo khóa học thành công.');
    }

    public function update(Request $request, Course $course)
    {
        abort_unless($course->teacher_id === $request->user()->id, 403);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'class_id' => ['nullable', 'integer', Rule::exists('classes', 'id')->where('teacher_id', $request->user()->id)],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'status' => 'nullable|in:draft,published',
        ], $this->messages());

        if ($validator->fails()) {
            return $this->validationBack($validator, 'edit-modal', $course->id);
        }

        $validated = $validator->validated();
        $course->update($validated);

        return back()->with('success', 'Cập nhật khóa học thành công.');
    }

    public function publish(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);

        $course->update(['status' => 'published']);

        return back()->with('success', 'Đã xuất bản khóa học.');
    }

    public function unpublish(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);

        $course->update(['status' => 'draft']);

        return back()->with('success', 'Đã chuyển khóa học về bản nháp.');
    }

    public function duplicate(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);

        $copy = $course->replicate(['status']);
        $copy->name = Str::limit($course->name . ' - Bản sao', 255, '');
        $copy->status = 'draft';
        $copy->push();

        return back()->with('success', 'Đã nhân bản khóa học thành bản nháp.');
    }

    public function syncStudents(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);

        if (!$course->class_id) {
            return back()->with('error', 'Khóa học cần được gắn với một lớp trước khi đồng bộ học sinh.');
        }

        $studentIds = $course->classModel?->students()->pluck('users.id') ?? collect();

        if ($studentIds->isEmpty()) {
            return back()->with('error', 'Lớp đang gắn chưa có học sinh để đồng bộ.');
        }

        $course->students()->syncWithoutDetaching(
            $studentIds->mapWithKeys(fn ($id) => [$id => ['enrolled_at' => now()]])->all()
        );

        return back()->with('success', 'Đã đồng bộ ' . $studentIds->count() . ' học sinh vào khóa học.');
    }

    public function removeStudent(Request $request, Course $course, User $student)
    {
        $this->authorizeCourse($request, $course);

        $course->students()->detach($student->id);

        return back()->with('success', 'Đã gỡ học sinh khỏi khóa học.');
    }

    public function destroy(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);

        $course->delete();

        return redirect()->route('teacher.courses')->with('success', 'Đã chuyển khóa học vào thùng rác.');
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên khóa học.',
            'name.max' => 'Tên khóa học không được vượt quá 255 ký tự.',
            'description.max' => 'Mô tả không được vượt quá 2000 ký tự.',
            'class_id.exists' => 'Lớp được chọn không hợp lệ.',
            'color.regex' => 'Màu khóa học không hợp lệ.',
            'status.in' => 'Trạng thái khóa học không hợp lệ.',
        ];
    }

    private function validationBack($validator, string $modal, ?int $courseId = null): RedirectResponse
    {
        return back()
            ->withErrors($validator)
            ->withInput()
            ->with('open_modal', $modal)
            ->with('edit_course_id', $courseId);
    }

    private function authorizeCourse(Request $request, Course $course): void
    {
        abort_unless($course->teacher_id === $request->user()->id, 403);
    }
}
