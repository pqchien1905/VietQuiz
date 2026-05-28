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

class QuizController extends AdminBaseController
{
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
            'show_result', 'quiz_type', 'anti_cheat_enabled', 'assigned_students',
            'public_to_all_students',
        ]), $validated);
        $this->ensureQuizTeacherScope($merged);

        $quiz->update($validated);
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
        return back()->with('success', 'Đã xóa bài kiểm tra khỏi danh sách hoạt động.');
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

        return back()->with('success', 'Đã xóa câu hỏi khỏi danh sách hoạt động.');
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
        return back()->with('success', 'Đã xóa bài tập khỏi danh sách hoạt động.');
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
}

