<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Notification;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Quiz;
use App\Support\CollectionPaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status', 'all'),
            'subject' => $request->query('subject', ''),
        ];
        $filters['status'] = in_array($filters['status'], ['all', 'active', 'archived'], true)
            ? $filters['status']
            : 'all';

        $classes = $user->classes()
            ->with(['teacher:id,name,email'])
            ->withCount(['students', 'courses', 'quizzes', 'assignments'])
            ->latest('class_user.joined_at')
            ->get();

        $classes = $this->attachLearningMetrics($classes, $user->id)
            ->map(function (ClassModel $class) {
                $class->display_color = $this->validCssColor($class->color) ? $class->color : '#2563eb';
                $class->display_icon = $class->icon ?: mb_strtoupper(mb_substr($class->name, 0, 1));
                $class->activity_status_label = $class->status === 'archived' ? 'Đã lưu trữ' : 'Đang học';
                $class->activity_status_badge = $class->status === 'archived' ? 'badge-outline' : 'badge-success';
                $class->has_pending_items = $class->pending_items_count > 0;

                return $class;
            });

        $subjects = $classes
            ->pluck('subject')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $summary = [
            'total' => $classes->count(),
            'active' => $classes->where('status', 'active')->count(),
            'archived' => $classes->where('status', 'archived')->count(),
            'courses' => $classes->sum('learning_courses_count'),
            'pending_items' => $classes->sum('pending_items_count'),
            'avg_progress' => $classes->count() > 0 ? round($classes->avg('progress_pct')) : 0,
        ];

        if ($filters['q'] !== '') {
            $needle = mb_strtolower($filters['q']);
            $classes = $classes->filter(function (ClassModel $class) use ($needle) {
                return str_contains(mb_strtolower($class->name), $needle)
                    || str_contains(mb_strtolower($class->code ?? ''), $needle)
                    || str_contains(mb_strtolower($class->subject ?? ''), $needle)
                    || str_contains(mb_strtolower($class->grade_level ?? ''), $needle)
                    || str_contains(mb_strtolower($class->teacher?->name ?? ''), $needle);
            });
        }

        if (in_array($filters['status'], ['active', 'archived'], true)) {
            $classes = $classes->where('status', $filters['status']);
        }

        if ($filters['subject'] !== '') {
            $classes = $classes->where('subject', $filters['subject']);
        }

        $classes = CollectionPaginator::make($classes->values(), $request, 9);

        return view('pages.student.classes', compact('classes', 'filters', 'summary', 'subjects'));
    }

    private function validCssColor(?string $color): bool
    {
        if (!$color) {
            return false;
        }

        return (bool) preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color);
    }

    public function show(Request $request, ClassModel $class)
    {
        $user = $request->user();
        abort_unless($user->classes()->where('classes.id', $class->id)->exists(), 403);

        $class->load([
            'teacher:id,name,email',
            'students:id,name,email',
            'courses' => fn ($query) => $query->withCount(['quizzes', 'assignments'])->latest(),
        ]);

        $class = $this->attachLearningMetrics(collect([$class]), $user->id)->first();
        $courseIds = $class->courses->pluck('id');

        $submittedQuizIds = $user->quizAttempts()
            ->whereNotNull('submitted_at')
            ->pluck('quizzes.id');

        $quizzes = Quiz::query()
            ->where('status', 'published')
            ->where(function ($query) use ($class, $courseIds) {
                $query->where('class_id', $class->id)
                    ->orWhereIn('course_id', $courseIds);
            })
            ->with(['course:id,name', 'classModel:id,name'])
            ->withCount('questions')
            ->orderByRaw('end_at is null')
            ->orderBy('end_at')
            ->latest()
            ->get()
            ->map(function (Quiz $quiz) use ($submittedQuizIds) {
                $quiz->is_completed = $submittedQuizIds->contains($quiz->id);
                $quiz->is_available = ! $quiz->is_completed
                    && (! $quiz->start_at || $quiz->start_at->isPast())
                    && (! $quiz->end_at || $quiz->end_at->isFuture());

                return $quiz;
            });

        $submittedAssignmentIds = $user->submissions()->pluck('assignment_id');

        $assignments = Assignment::query()
            ->where(function ($query) use ($class, $courseIds) {
                $query->where('class_id', $class->id)
                    ->orWhereIn('course_id', $courseIds);
            })
            ->with(['course:id,name', 'class:id,name'])
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->latest()
            ->get()
            ->map(function (Assignment $assignment) use ($submittedAssignmentIds) {
                $assignment->is_submitted = $submittedAssignmentIds->contains($assignment->id);
                $assignment->is_available = ! $assignment->is_submitted
                    && (! $assignment->due_at || $assignment->due_at->isFuture());

                return $assignment;
            });

        return view('pages.student.class-detail', compact('class', 'quizzes', 'assignments'));
    }

    public function leave(Request $request, ClassModel $class)
    {
        $user = $request->user();
        abort_unless($user->classes()->where('classes.id', $class->id)->exists(), 403);

        $user->classes()->detach($class->id);

        $courseIds = Course::where('class_id', $class->id)->pluck('id');
        if ($courseIds->isNotEmpty()) {
            $user->courses()->detach($courseIds);
        }

        return redirect()
            ->route('student.classes')
            ->with('success', "Đã rời lớp '{$class->name}'.");
    }

    public function showJoinForm(Request $request)
    {
        $user = $request->user();
        $prefillCode = Str::upper(preg_replace('/[^A-Z0-9]/i', '', (string) $request->query('code', '')));

        $enrolledClasses = $user->classes()
            ->with(['teacher:id,name,email'])
            ->withCount(['students', 'courses', 'quizzes', 'assignments'])
            ->orderBy('name')
            ->get()
            ->map(fn ($class) => [
                'id' => $class->id,
                'name' => $class->name,
                'code' => $class->code,
                'teacher' => $class->teacher?->name ?? 'Giáo viên',
                'subject' => $class->subject ?? 'Chưa phân môn',
                'grade_level' => $class->grade_level,
                'color' => $class->color ?? '#3b82f6',
                'students' => $class->students_count,
                'courses' => $class->courses_count,
                'quizzes' => $class->quizzes_count,
                'assignments' => $class->assignments_count,
                'joined_at' => $class->pivot?->joined_at
                    ? \Carbon\Carbon::parse($class->pivot->joined_at)->format('d/m/Y')
                    : null,
                'url' => route('student.classes.show', $class),
            ])
            ->values();

        $pendingEnrollments = $user->classEnrollments()
            ->wherePivot('enrollment_status', 'pending')
            ->with(['teacher:id,name,email'])
            ->orderByPivot('requested_at', 'desc')
            ->get()
            ->map(fn ($class) => [
                'id' => $class->id,
                'name' => $class->name,
                'code' => $class->code,
                'teacher' => $class->teacher?->name ?? 'Giáo viên',
                'requested_at' => $class->pivot?->requested_at
                    ? \Carbon\Carbon::parse($class->pivot->requested_at)->format('d/m/Y H:i')
                    : null,
                'source' => $class->pivot?->enrollment_source === 'link' ? 'Link mời' : 'Mã lớp',
                'color' => $class->color ?? '#f59e0b',
            ])
            ->values();

        $summary = [
            'enrolled' => $enrolledClasses->count(),
            'courses' => $enrolledClasses->sum('courses'),
            'pending_items' => $enrolledClasses->sum('quizzes') + $enrolledClasses->sum('assignments'),
            'pending_requests' => $pendingEnrollments->count(),
        ];

        return view('pages.student.join-class', [
            'enrolledClasses' => $enrolledClasses,
            'pendingEnrollments' => $pendingEnrollments,
            'summary' => $summary,
            'prefillCode' => $prefillCode,
        ]);
    }

    public function cancelJoinRequest(Request $request, ClassModel $class)
    {
        $user = $request->user();

        $deleted = $user->classEnrollments()
            ->newPivotStatement()
            ->where('user_id', $user->id)
            ->where('class_id', $class->id)
            ->where('enrollment_status', 'pending')
            ->delete();

        return redirect()
            ->route('student.join-class')
            ->with(
                $deleted > 0 ? 'success' : 'info',
                $deleted > 0
                    ? "Đã hủy yêu cầu tham gia lớp '{$class->name}'."
                    : "Yêu cầu tham gia lớp '{$class->name}' không còn ở trạng thái chờ duyệt."
            );
    }

    public function joinByCode(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $code = $this->normalizeClassCode($validated['code']);
        if ($code === '') {
            return back()
                ->withErrors(['code' => 'Vui lòng nhập mã lớp hợp lệ.'])
                ->withInput();
        }

        $class = ClassModel::where('code', $code)->first();

        if (! $class) {
            return back()
                ->withErrors(['code' => 'Không tìm thấy lớp với mã này.'])
                ->withInput(['code' => $code]);
        }

        if ($class->status !== 'active') {
            return back()
                ->withErrors(['code' => 'Lớp này đã được lưu trữ nên không thể tham gia.'])
                ->withInput(['code' => $code]);
        }

        $user = $request->user();

        if ($user->role !== 'student') {
            return redirect()
                ->route('student.join.code', ['code' => Str::lower($code)])
                ->with('warning', 'Bạn cần dùng tài khoản học sinh để tham gia lớp.');
        }

        if ($user->classes()->where('classes.id', $class->id)->exists()) {
            $this->syncClassCourses($user, $class);

            return redirect()
                ->route('student.classes.show', $class)
                ->with('info', "Bạn đã tham gia lớp '{$class->name}' rồi.");
        }

        if ($this->hasPendingEnrollment($user, $class)) {
            return back()
                ->with('info', "Yêu cầu tham gia lớp '{$class->name}' đang chờ giáo viên phê duyệt.")
                ->withInput(['code' => $code]);
        }

        $this->createPendingEnrollment($user, $class, 'code');

        return redirect()
            ->route('student.join-class')
            ->with('success', "Đã gửi yêu cầu tham gia lớp '{$class->name}'. Vui lòng chờ giáo viên phê duyệt.");
    }

    public function joinByLink(Request $request, string $code)
    {
        $code = $this->normalizeClassCode($code);
        $class = ClassModel::with('teacher')->where('code', $code)->first();
        $user = $request->user();

        if (! $class) {
            if ($user->role === 'student') {
                return redirect()
                    ->route('student.join-class')
                    ->withErrors(['code' => 'Không tìm thấy lớp với mã này.']);
            }

            return view('pages.student.join-link', [
                'class' => null,
                'code' => $code,
            ]);
        }

        if ($class->status !== 'active') {
            if ($user->role === 'student') {
                return redirect()
                    ->route('student.join-class', ['code' => Str::lower($code)])
                    ->withErrors(['code' => 'Lớp này đã được lưu trữ nên không thể tham gia.']);
            }

            return view('pages.student.join-link', [
                'class' => $class,
                'code' => $code,
            ]);
        }

        if ($user->role !== 'student') {
            return view('pages.student.join-link', [
                'class' => $class,
                'code' => $code,
            ]);
        }

        if ($user->classes()->where('classes.id', $class->id)->exists()) {
            $this->syncClassCourses($user, $class);

            return redirect()
                ->route('student.join-class')
                ->with('info', "Bạn đã tham gia lớp '{$class->name}' rồi.");
        }

        if ($this->hasPendingEnrollment($user, $class)) {
            return redirect()
                ->route('student.join-class')
                ->with('info', "Yêu cầu tham gia lớp '{$class->name}' đang chờ giáo viên phê duyệt.");
        }

        $this->createPendingEnrollment($user, $class, 'link');

        return redirect()
            ->route('student.join-class')
            ->with('success', "Đã gửi yêu cầu tham gia lớp '{$class->name}'. Vui lòng chờ giáo viên phê duyệt.");
    }

    private function normalizeClassCode(string $code): string
    {
        return Str::upper(preg_replace('/[^A-Z0-9]/i', '', $code));
    }

    private function syncClassCourses($user, ClassModel $class): void
    {
        $courseIds = Course::where('class_id', $class->id)->pluck('id');
        if ($courseIds->isEmpty()) {
            return;
        }

        $payload = $courseIds
            ->mapWithKeys(fn ($courseId) => [$courseId => ['enrolled_at' => now()]])
            ->all();

        $user->courses()->syncWithoutDetaching($payload);
    }

    private function hasPendingEnrollment($user, ClassModel $class): bool
    {
        return $user->classEnrollments()
            ->where('classes.id', $class->id)
            ->wherePivot('enrollment_status', 'pending')
            ->exists();
    }

    private function createPendingEnrollment($user, ClassModel $class, string $source): void
    {
        $user->classEnrollments()->attach($class->id, [
            'joined_at' => now(),
            'enrollment_status' => 'pending',
            'enrollment_source' => $source,
            'requested_at' => now(),
        ]);

        Notification::create([
            'user_id' => $class->teacher_id,
            'audience_role' => 'teacher',
            'type' => 'class_join_request',
            'title' => 'Yêu cầu tham gia lớp mới',
            'body' => "{$user->name} muốn tham gia lớp \"{$class->name}\".",
            'data' => [
                'class_id' => $class->id,
                'student_id' => $user->id,
                'source' => $source,
            ],
            'is_read' => false,
        ]);
    }

    private function attachLearningMetrics(Collection $classes, int $studentId): Collection
    {
        if ($classes->isEmpty()) {
            return $classes;
        }

        $classIds = $classes->pluck('id')->all();
        $courses = Course::whereIn('class_id', $classIds)->get(['id', 'class_id']);
        $courseIds = $courses->pluck('id')->all();
        $courseIdsByClass = $courses->groupBy('class_id')->map(fn ($items) => $items->pluck('id'));

        $quizRows = Quiz::query()
            ->where('status', 'published')
            ->where(function ($query) use ($classIds, $courseIds) {
                $query->whereIn('class_id', $classIds)
                    ->orWhereIn('course_id', $courseIds);
            })
            ->get(['id', 'class_id', 'course_id']);

        $assignmentRows = Assignment::query()
            ->where(function ($query) use ($classIds, $courseIds) {
                $query->whereIn('class_id', $classIds)
                    ->orWhereIn('course_id', $courseIds);
            })
            ->get(['id', 'class_id', 'course_id']);

        $submittedQuizIds = DB::table('quiz_user')
            ->where('user_id', $studentId)
            ->whereNotNull('submitted_at')
            ->pluck('quiz_id');

        $submittedAssignmentIds = DB::table('submissions')
            ->where('student_id', $studentId)
            ->pluck('assignment_id');

        $scoreRows = DB::table('quiz_user')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_user.quiz_id')
            ->where('quiz_user.user_id', $studentId)
            ->whereNotNull('quiz_user.submitted_at')
            ->where(function ($query) use ($classIds, $courseIds) {
                $query->whereIn('quizzes.class_id', $classIds)
                    ->orWhereIn('quizzes.course_id', $courseIds);
            })
            ->select([
                'quizzes.class_id',
                'quizzes.course_id',
                'quiz_user.score',
                'quiz_user.total_points',
            ])
            ->get();

        return $classes->map(function (ClassModel $class) use (
            $courseIdsByClass,
            $quizRows,
            $assignmentRows,
            $submittedQuizIds,
            $submittedAssignmentIds,
            $scoreRows
        ) {
            $classCourseIds = $courseIdsByClass->get($class->id, collect());
            $classQuizzes = $quizRows->filter(fn ($quiz) =>
                (int) $quiz->class_id === (int) $class->id || $classCourseIds->contains($quiz->course_id)
            );
            $classAssignments = $assignmentRows->filter(fn ($assignment) =>
                (int) $assignment->class_id === (int) $class->id || $classCourseIds->contains($assignment->course_id)
            );

            $completedQuizzes = $classQuizzes->whereIn('id', $submittedQuizIds)->count();
            $completedAssignments = $classAssignments->whereIn('id', $submittedAssignmentIds)->count();
            $totalItems = $classQuizzes->count() + $classAssignments->count();
            $completedItems = $completedQuizzes + $completedAssignments;

            $classScores = $scoreRows->filter(fn ($row) =>
                (int) $row->class_id === (int) $class->id || $classCourseIds->contains($row->course_id)
            );
            $avgScore = $classScores
                ->filter(fn ($row) => (float) $row->total_points > 0)
                ->avg(fn ($row) => ((float) $row->score / (float) $row->total_points) * 100);

            $class->learning_courses_count = $classCourseIds->count();
            $class->learning_quizzes_count = $classQuizzes->count();
            $class->learning_assignments_count = $classAssignments->count();
            $class->completed_items_count = $completedItems;
            $class->total_items_count = $totalItems;
            $class->pending_items_count = max(0, $totalItems - $completedItems);
            $class->progress_pct = $totalItems > 0 ? (int) round($completedItems / $totalItems * 100) : 0;
            $class->avg_score = $avgScore !== null ? round($avgScore, 1) : null;

            return $class;
        });
    }
}

