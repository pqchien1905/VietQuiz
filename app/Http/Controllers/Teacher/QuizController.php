<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Mail\QuizAssigned;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Notification;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizViolation;
use App\Models\User;
use App\Services\AiQuestionGenerator;
use App\Services\DocumentTextExtractor;
use App\Services\QuestionFileImporter;
use App\Services\QuizAssignmentScopeValidator;
use App\Support\AiQuestionIntentGuard;
use App\Support\VipFeature;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Throwable;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $folders = $user->quizFolders()
            ->withCount('quizzes')
            ->orderBy('name')
            ->get();
        $activeFolder = $request->query('folder');
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $type = $request->query('type');
        $courseId = $request->query('course_id');

        $quizQuery = $user->quizzes()
            ->with('classModel', 'course', 'folder')
            ->withCount('questions')
            ->withCount(['attempts as attempts_count'])
            ->latest();

        if ($activeFolder === 'none') {
            $quizQuery->whereNull('folder_id');
        } elseif ($activeFolder !== null && $activeFolder !== 'all') {
            $folderId = (int) $activeFolder;
            $ownedFolder = $folders->firstWhere('id', $folderId);
            if ($ownedFolder) {
                $quizQuery->where('folder_id', $folderId);
            }
        }

        if ($search !== '') {
            $quizQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('course', function ($courseQuery) use ($search) {
                        $courseQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (in_array($status, ['draft', 'published', 'closed'], true)) {
            $quizQuery->where('status', $status);
        }

        if (in_array($type, ['exam', 'practice'], true)) {
            $quizQuery->where('quiz_type', $type);
        }

        if ($courseId !== null && $courseId !== '') {
            $quizQuery->where('course_id', (int) $courseId);
        }

        $allQuizzes = (clone $quizQuery)->get();
        $quizzes = (clone $quizQuery)->paginate(15)->withQueryString();

        $courses = $user->createdCourses()->get();

        $filters = [
            'q' => $search,
            'status' => $status,
            'type' => $type,
            'course_id' => $courseId,
        ];

        return view('pages.teacher.quizzes', [
            'quizzes' => $quizzes,
            'courses' => $courses,
            'folders' => $folders,
            'activeFolder' => $activeFolder,
            'filters' => $filters,
            'publishedCount' => $allQuizzes->where('status', 'published')->count(),
            'draftCount' => $allQuizzes->where('status', 'draft')->count(),
            'archivedCount' => $allQuizzes->where('status', 'closed')->count(),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $courses = $user->createdCourses()->get();
        $classes = $user->createdClasses()->with('students')->get();
        $folders = $user->quizFolders()->orderBy('name')->get();
        $questionFolders = $user->questionFolders()
            ->withCount('questions')
            ->orderBy('name')
            ->get();
        $bankQuestions = $user->questions()
            ->with(['folder:id,name', 'quiz:id,title'])
            ->orderByRaw('folder_id is null')
            ->orderBy('folder_id')
            ->latest()
            ->get();

        return view('pages.teacher.quiz-create', compact('courses', 'classes', 'folders', 'questionFolders', 'bankQuestions'));
    }

    public function storeFolder(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ], [
            'name.required' => 'Vui lòng nhập tên thư mục.',
            'name.max' => 'Tên thư mục không được vượt quá :max ký tự.',
        ]);

        $request->user()->quizFolders()->firstOrCreate([
            'name' => trim($validated['name']),
        ]);

        return back()->with('success', 'Đã tạo thư mục bài kiểm tra.');
    }

    public function moveToFolder(Request $request, Quiz $quiz)
    {
        $this->authorizeTeacher($request, $quiz);

        $validated = $request->validate([
            'folder_id' => 'nullable|integer|exists:quiz_folders,id',
        ], [
            'folder_id.exists' => 'Thư mục đã chọn không tồn tại.',
        ]);

        $folderId = $validated['folder_id'] ?? null;
        if ($folderId !== null) {
            $ownsFolder = $request->user()->quizFolders()->whereKey($folderId)->exists();
            abort_unless($ownsFolder, 403);
        }

        $quiz->update(['folder_id' => $folderId]);

        return back()->with('success', 'Đã chuyển bài kiểm tra vào thư mục.');
    }

    public function generateAiQuestions(Request $request, AiQuestionGenerator $generator, DocumentTextExtractor $extractor): JsonResponse
    {
        if (! VipFeature::isVip($request->user())) {
            return response()->json([
                'success' => false,
                'message' => VipFeature::aiMessage(),
            ], 403);
        }

        $validated = $request->validate([
            'topic' => 'nullable|required_without:source_file|string|min:3|max:500',
            'type' => 'required|in:mixed,multiple_choice,true_false,short_answer',
            'count' => 'required|integer|min:1|max:100',
            'difficulty' => 'required|in:easy,medium,hard',
            'grade' => 'nullable|string|max:100',
            'extra_context' => 'nullable|string|max:1000',
            'source_file' => 'nullable|file|max:15360|mimes:pdf,doc,docx,jpg,jpeg,png,webp',
        ], [
            'topic.required_without' => 'Vui lòng nhập chủ đề hoặc chọn file nguồn.',
            'topic.min' => 'Chủ đề phải có ít nhất :min ký tự.',
            'topic.max' => 'Chủ đề không được vượt quá :max ký tự.',
            'type.required' => 'Vui lòng chọn loại câu hỏi.',
            'type.in' => 'Loại câu hỏi không hợp lệ.',
            'count.required' => 'Vui lòng nhập số câu.',
            'count.integer' => 'Số câu phải là số nguyên.',
            'count.min' => 'Số câu phải từ :min trở lên.',
            'count.max' => 'Chỉ được tạo tối đa :max câu mỗi lần.',
            'difficulty.required' => 'Vui lòng chọn mức độ.',
            'difficulty.in' => 'Mức độ không hợp lệ.',
            'grade.max' => 'Khối/lớp không được vượt quá :max ký tự.',
            'extra_context.max' => 'Yêu cầu bổ sung không được vượt quá :max ký tự.',
            'source_file.file' => 'File nguồn không hợp lệ.',
            'source_file.max' => 'File nguồn không được vượt quá 15MB.',
            'source_file.mimes' => 'File nguồn phải là PDF, Word hoặc ảnh.',
        ]);

        AiQuestionIntentGuard::ensureMeaningfulRequest(
            $validated,
            $request->hasFile('source_file')
        );

        try {
            if ($request->hasFile('source_file')) {
                $sourceFile = $request->file('source_file');
                $extension = strtolower($sourceFile->getClientOriginalExtension());
                $validated['topic'] = $validated['topic']
                    ?? pathinfo($sourceFile->getClientOriginalName(), PATHINFO_FILENAME);

                if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $contents = file_get_contents($sourceFile->getRealPath());
                    if ($contents === false || $contents === '') {
                        throw new \RuntimeException('Không đọc được file ảnh.');
                    }

                    $validated['image_data_url'] = sprintf(
                        'data:%s;base64,%s',
                        $sourceFile->getMimeType() ?: 'image/'.($extension === 'jpg' ? 'jpeg' : $extension),
                        base64_encode($contents)
                    );
                    $validated['extra_context'] = trim(($validated['extra_context'] ?? '')."\n\nHay doc noi dung trong anh va so hoa thanh cau hoi.");
                } else {
                    $documentText = $extractor->extract($sourceFile);
                    $documentText = mb_substr($documentText, 0, 12000);

                    $validated['extra_context'] = trim(($validated['extra_context'] ?? '')."\n\nNoi dung tai lieu de tao cau hoi:\n".$documentText);
                }
            }

            return response()->json([
                'success' => true,
                'questions' => $generator->generate($validated),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage() ?: 'Không thể tạo câu hỏi bằng AI.',
            ], 422);
        }
    }

    public function importQuestionsFile(Request $request, QuestionFileImporter $importer): JsonResponse
    {
        $validated = $request->validate([
            'source_file' => 'required|file|max:15360|mimes:pdf,doc,docx',
        ]);

        try {
            return response()->json([
                'success' => true,
                'questions' => $importer->import($request->file('source_file')),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage() ?: 'Không import được câu hỏi từ file.',
            ], 422);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'folder_id' => 'nullable|integer|exists:quiz_folders,id',
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')->where('teacher_id', $request->user()->id)],
            'class_id' => ['nullable', 'integer', Rule::exists('classes', 'id')->where('teacher_id', $request->user()->id)],
            'time_limit' => 'nullable|integer|min:1',
            'max_attempts' => 'nullable|integer|min:1|max:20',
            'unlimited_attempts' => 'nullable|boolean',
            'end_at' => 'nullable|date',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'is_shuffle' => 'boolean',
            'show_result' => 'boolean',
            'is_published' => 'boolean',
            'quiz_type' => 'nullable|in:exam,practice',
            'anti_cheat_enabled' => 'boolean',
            'assignment_type' => 'required|in:class,everyone,specific',
            'assigned_students' => 'nullable|array',
            'assigned_students.*' => ['integer', Rule::exists('users', 'id')->where('role', 'student')],
            'questions' => 'required|array|min:1',
            'questions.*.content' => 'required|string',
            'questions.*.type' => 'required|in:multiple_choice,true_false,short_answer',
            'questions.*.options' => 'nullable|array',
            'questions.*.correct_answer' => 'required|string',
            'questions.*.points' => 'nullable|numeric|min:0',
            'questions.*.explanation' => 'nullable|string',
        ]);

        if (! VipFeature::canUseQuizQuestionCount($request->user(), count($validated['questions']))) {
            return back()->withInput()->with('error', VipFeature::quizQuestionLimitMessage());
        }

        $assignmentType = $validated['assignment_type'];
        $this->validateQuizAssignmentScope($assignmentType, $validated);
        if ($assignmentType === 'specific') {
            $this->validateAssignedStudentsInAssignmentScope($validated);
        }
        if (! empty($validated['folder_id'])) {
            $ownsFolder = $request->user()->quizFolders()->whereKey($validated['folder_id'])->exists();
            abort_unless($ownsFolder, 403);
        }

        $quiz = Quiz::create([
            'teacher_id' => $request->user()->id,
            'folder_id' => $validated['folder_id'] ?? null,
            'course_id' => $assignmentType === 'everyone' ? null : ($validated['course_id'] ?? null),
            'class_id' => in_array($assignmentType, ['class', 'specific'], true) ? ($validated['class_id'] ?? null) : null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'time_limit' => $validated['time_limit'] ?? null,
            'max_attempts' => ($validated['unlimited_attempts'] ?? false)
                ? null
                : ($validated['max_attempts'] ?? 1),
            'end_at' => $validated['end_at'] ?? null,
            'passing_score' => $validated['passing_score'] ?? 50,
            'is_shuffle' => $validated['is_shuffle'] ?? false,
            'show_result' => $validated['show_result'] ?? false,
            'quiz_type' => $validated['quiz_type'] ?? 'exam',
            'anti_cheat_enabled' => ($validated['quiz_type'] ?? 'exam') === 'exam'
                ? ($validated['anti_cheat_enabled'] ?? false)
                : false,
            'assigned_students' => $assignmentType === 'specific'
                ? ($validated['assigned_students'] ?? [])
                : null,
            'public_to_all_students' => $assignmentType === 'everyone',
            'status' => ($validated['is_published'] ?? false) ? 'published' : 'draft',
        ]);

        // Tổng điểm luôn = 10, mỗi câu = 10 / tổng số câu
        $totalQuestions = count($validated['questions']);
        $pointsPerQuestion = $totalQuestions > 0 ? round(10 / $totalQuestions, 2) : 1;

        foreach ($validated['questions'] as $i => $qData) {
            Question::create([
                'quiz_id' => $quiz->id,
                'teacher_id' => $request->user()->id,
                'type' => $qData['type'],
                'content' => $qData['content'],
                'options' => isset($qData['options']) ? json_encode($qData['options']) : null,
                'correct_answer' => $qData['correct_answer'],
                'points' => $pointsPerQuestion,
                'explanation' => $qData['explanation'] ?? null,
                'order' => $i + 1,
            ]);
        }

        // Notify students if quiz is published immediately
        if ($quiz->status === 'published') {
            $this->notifyStudentsAboutQuiz($quiz, $request->user());
        }

        return redirect()->route('teacher.quiz-detail', $quiz)
            ->with('success', 'Tạo bài kiểm tra thành công!');
    }

    public function show(Request $request, Quiz $quiz)
    {
        $this->authorizeTeacher($request, $quiz);

        $quiz->load([
            'questions' => fn ($q) => $q->orderBy('order')->orderBy('id'),
            'course',
            'folder',
            'classModel.students:id,name,email',
            'attempts' => fn ($q) => $q->latest('quiz_user.submitted_at'),
        ]);
        $quiz->loadCount(['questions']);
        $quiz->loadCount(['attempts as attempts_count']);

        $submittedAttempts = $quiz->attempts->filter(fn ($attempt) => $attempt->pivot->submitted_at !== null);
        $avgScore = $submittedAttempts->count() > 0
            ? round($submittedAttempts->avg(fn ($a) => $a->pivot->total_points > 0
                ? round(($a->pivot->score / $a->pivot->total_points) * 100)
                : 0))
            : 0;

        $violationSummary = QuizViolation::query()
            ->where('quiz_id', $quiz->id)
            ->with('user:id,name,email')
            ->latest('occurred_at')
            ->get()
            ->groupBy('user_id')
            ->map(function ($logs) {
                $latest = $logs->first();

                return [
                    'student' => $latest->user,
                    'total' => $logs->count(),
                    'events' => $logs->countBy('event_type')->sortDesc(),
                    'latest_at' => $latest->occurred_at,
                ];
            })
            ->sortByDesc('total')
            ->values();

        return view('pages.teacher.quiz-detail', compact('quiz', 'avgScore', 'violationSummary'));
    }

    public function edit(Request $request, Quiz $quiz)
    {
        $this->authorizeTeacher($request, $quiz);

        $user = $request->user();
        $courses = $user->createdCourses()->get();
        $classes = $user->createdClasses()->with('students')->get();
        $folders = $user->quizFolders()->orderBy('name')->get();
        $questionFolders = $user->questionFolders()
            ->withCount('questions')
            ->orderBy('name')
            ->get();
        $bankQuestions = $user->questions()
            ->with(['folder:id,name', 'quiz:id,title'])
            ->where(function ($query) use ($quiz) {
                $query->whereNull('quiz_id')
                    ->orWhere('quiz_id', '!=', $quiz->id);
            })
            ->orderByRaw('folder_id is null')
            ->orderBy('folder_id')
            ->latest()
            ->get();

        $quiz->load([
            'questions' => fn ($q) => $q->orderBy('order')->orderBy('id'),
            'course',
            'classModel',
            'folder',
        ]);

        return view('pages.teacher.quiz-create', compact('courses', 'classes', 'folders', 'questionFolders', 'bankQuestions', 'quiz'));
    }

    public function update(Request $request, Quiz $quiz)
    {
        $this->authorizeTeacher($request, $quiz);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'folder_id' => 'nullable|integer|exists:quiz_folders,id',
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')->where('teacher_id', $request->user()->id)],
            'class_id' => ['nullable', 'integer', Rule::exists('classes', 'id')->where('teacher_id', $request->user()->id)],
            'time_limit' => 'nullable|integer|min:1',
            'max_attempts' => 'nullable|integer|min:1|max:20',
            'unlimited_attempts' => 'nullable|boolean',
            'end_at' => 'nullable|date',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'is_shuffle' => 'boolean',
            'show_result' => 'boolean',
            'is_published' => 'boolean',
            'quiz_type' => 'nullable|in:exam,practice',
            'anti_cheat_enabled' => 'boolean',
            'assignment_type' => 'required|in:class,everyone,specific',
            'assigned_students' => 'nullable|array',
            'assigned_students.*' => ['integer', Rule::exists('users', 'id')->where('role', 'student')],
            'questions' => 'required|array|min:1',
            'questions.*.content' => 'required|string',
            'questions.*.type' => 'required|in:multiple_choice,true_false,short_answer',
            'questions.*.options' => 'nullable|array',
            'questions.*.correct_answer' => 'required|string',
            'questions.*.points' => 'nullable|numeric|min:0',
            'questions.*.explanation' => 'nullable|string',
        ]);

        if (! VipFeature::canUseQuizQuestionCount($request->user(), count($validated['questions']))) {
            return back()->withInput()->with('error', VipFeature::quizQuestionLimitMessage());
        }

        $assignmentType = $validated['assignment_type'];
        $this->validateQuizAssignmentScope($assignmentType, $validated);
        if ($assignmentType === 'specific') {
            $this->validateAssignedStudentsInAssignmentScope($validated);
        }
        if (! empty($validated['folder_id'])) {
            $ownsFolder = $request->user()->quizFolders()->whereKey($validated['folder_id'])->exists();
            abort_unless($ownsFolder, 403);
        }

        $wasPublished = $quiz->status === 'published';

        $quiz->update([
            'folder_id' => $validated['folder_id'] ?? null,
            'course_id' => $assignmentType === 'everyone' ? null : ($validated['course_id'] ?? null),
            'class_id' => in_array($assignmentType, ['class', 'specific'], true) ? ($validated['class_id'] ?? null) : null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'time_limit' => $validated['time_limit'] ?? null,
            'max_attempts' => ($validated['unlimited_attempts'] ?? false)
                ? null
                : ($validated['max_attempts'] ?? 1),
            'end_at' => $validated['end_at'] ?? null,
            'passing_score' => $validated['passing_score'] ?? 50,
            'is_shuffle' => $validated['is_shuffle'] ?? false,
            'show_result' => $validated['show_result'] ?? false,
            'quiz_type' => $validated['quiz_type'] ?? 'exam',
            'anti_cheat_enabled' => ($validated['quiz_type'] ?? 'exam') === 'exam'
                ? ($validated['anti_cheat_enabled'] ?? false)
                : false,
            'assigned_students' => $assignmentType === 'specific'
                ? ($validated['assigned_students'] ?? [])
                : null,
            'public_to_all_students' => $assignmentType === 'everyone',
            'status' => ($validated['is_published'] ?? false) ? 'published' : 'draft',
        ]);

        $totalQuestions = count($validated['questions']);
        $pointsPerQuestion = $totalQuestions > 0 ? round(10 / $totalQuestions, 2) : 1;

        $quiz->questions()->delete();
        foreach ($validated['questions'] as $i => $qData) {
            Question::create([
                'quiz_id' => $quiz->id,
                'teacher_id' => $request->user()->id,
                'type' => $qData['type'],
                'content' => $qData['content'],
                'options' => isset($qData['options']) ? array_values(array_filter($qData['options'], fn ($o) => $o !== null && trim((string) $o) !== '')) : null,
                'correct_answer' => $qData['correct_answer'],
                'points' => $pointsPerQuestion,
                'explanation' => $qData['explanation'] ?? null,
                'order' => $i + 1,
            ]);
        }

        if (! $wasPublished && $quiz->status === 'published') {
            $this->notifyStudentsAboutQuiz($quiz->fresh(), $request->user());
        }

        return redirect()->route('teacher.quiz-detail', $quiz)
            ->with('success', 'Cập nhật bài kiểm tra thành công!');
    }

    public function destroy(Request $request, Quiz $quiz)
    {
        $this->authorizeTeacher($request, $quiz);
        $quiz->delete();

        return redirect()->route('teacher.quizzes')
            ->with('success', 'Đã xóa bài kiểm tra!');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'quiz_ids' => 'required|array|min:1',
            'quiz_ids.*' => 'integer|exists:quizzes,id',
        ], [
            'quiz_ids.required' => 'Vui lòng chọn ít nhất một bài kiểm tra để xóa.',
            'quiz_ids.min' => 'Vui lòng chọn ít nhất một bài kiểm tra để xóa.',
        ]);

        $deletedCount = Quiz::where('teacher_id', $request->user()->id)
            ->whereIn('id', $validated['quiz_ids'])
            ->delete();

        return redirect()->route('teacher.quizzes')
            ->with('success', "Đã xóa {$deletedCount} bài kiểm tra.");
    }

    public function publish(Request $request, Quiz $quiz)
    {
        $this->authorizeTeacher($request, $quiz);
        if (! $quiz->hasAssignmentScope()) {
            return redirect()->back()->with('error', 'Vui lòng chọn phạm vi giao bài trước khi xuất bản.');
        }

        $quiz->update(['status' => 'published']);
        $this->notifyStudentsAboutQuiz($quiz, $request->user());

        return redirect()->back()->with('success', 'Đã xuất bản bài kiểm tra!');
    }

    public function unpublish(Request $request, Quiz $quiz)
    {
        $this->authorizeTeacher($request, $quiz);
        $quiz->update(['status' => 'draft']);

        return redirect()->back()->with('success', 'Đã gỡ xuất bản bài kiểm tra!');
    }

    private function authorizeTeacher(Request $request, Quiz $quiz): void
    {
        abort_unless($quiz->teacher_id === $request->user()->id, 403);
    }

    private function validateQuizAssignmentScope(string $assignmentType, array $validated): void
    {
        if ($assignmentType === 'specific' && empty($validated['assigned_students'])) {
            throw ValidationException::withMessages([
                'assigned_students' => 'Vui lòng chọn ít nhất một học sinh để giao bài.',
            ]);
        }

        if ($assignmentType === 'class' && empty($validated['class_id']) && empty($validated['course_id'])) {
            throw ValidationException::withMessages([
                'class_id' => 'Vui lòng chọn lớp học hoặc khóa học để giao bài.',
            ]);
        }
    }

    private function validateAssignedStudentsInAssignmentScope(array $validated): void
    {
        $invalidStudentIds = app(QuizAssignmentScopeValidator::class)->invalidAssignedStudentIds(
            $validated['assigned_students'] ?? [],
            isset($validated['class_id']) ? (int) $validated['class_id'] : null,
            isset($validated['course_id']) ? (int) $validated['course_id'] : null,
        );

        if ($invalidStudentIds !== []) {
            throw ValidationException::withMessages([
                'assigned_students' => 'Danh sách học sinh được chọn có học sinh không thuộc lớp hoặc khóa học đã chọn.',
            ]);
        }
    }

    private function notifyStudentsAboutQuiz(Quiz $quiz, $teacher): void
    {
        $studentIds = collect();

        if ($quiz->assigned_students && count($quiz->assigned_students) > 0) {
            $students = User::whereIn('id', $quiz->assigned_students)->get();
            $className = null;
        } elseif ($quiz->class_id) {
            $class = ClassModel::find($quiz->class_id);
            if ($class) {
                $students = $class->students()->get();
                $className = $class->name;
            } else {
                $students = collect();
                $className = null;
            }
        } elseif ($quiz->course_id) {
            $course = Course::find($quiz->course_id);
            if ($course) {
                $students = $course->students()->get();
                $className = $course->name;
            } else {
                $students = collect();
                $className = null;
            }
        } elseif ($quiz->public_to_all_students) {
            $students = User::where('role', 'student')->get();
            $className = 'Mọi người';
        } else {
            $students = collect();
            $className = null;
        }

        $dueDate = $quiz->end_at ? Carbon::parse($quiz->end_at)->format('d/m/Y H:i') : null;

        foreach ($students as $student) {
            Notification::create([
                'user_id' => $student->id,
                'audience_role' => 'student',
                'type' => 'quiz_assigned',
                'title' => $quiz->quiz_type === 'practice' ? 'Bài luyện tập mới' : 'Bài kiểm tra mới',
                'body' => "Giáo viên {$teacher->name} đã giao ".($quiz->quiz_type === 'practice' ? 'bài luyện tập' : 'bài kiểm tra').": \"{$quiz->title}\".",
                'data' => json_encode([
                    'quiz_id' => $quiz->id,
                    'class_id' => $quiz->class_id,
                    'teacher_id' => $teacher->id,
                ]),
            ]);

            if ($student->email) {
                try {
                    Mail::to($student->email)->queue(new QuizAssigned(
                        $student->name,
                        $quiz->title,
                        $className ?? 'Tất cả',
                        $dueDate,
                        route('student.quiz-take', $quiz),
                    ));
                } catch (\Exception $e) {
                    \Log::warning('Quiz email failed for student '.$student->id.': '.$e->getMessage());
                }
            }
        }
    }
}
