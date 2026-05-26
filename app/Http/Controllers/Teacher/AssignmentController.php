<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Notification;
use App\Models\Submission;
use App\Models\User;
use App\Services\AssignmentAiGrader;
use App\Support\CollectionPaginator;
use App\Support\UploadedFileStorage;
use App\Support\VipFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status', 'all'),
            'class_id' => $request->query('class_id'),
            'course_id' => $request->query('course_id'),
            'type' => $request->query('type'),
        ];

        $query = Assignment::where('teacher_id', $user->id)
            ->with([
                'course:id,name',
                'class' => fn ($query) => $query->select('id', 'name', 'subject')->withCount('students'),
            ])
            ->withCount([
                'submissions',
                'submissions as graded_submissions_count' => fn ($query) => $query->whereHas('grades'),
            ])
            ->latest();

        if ($filters['q'] !== '') {
            $search = $filters['q'];
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('class', fn ($classQuery) => $classQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('course', fn ($courseQuery) => $courseQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($filters['class_id'] !== null && $filters['class_id'] !== '') {
            $query->where('class_id', (int) $filters['class_id']);
        }

        if ($filters['course_id'] !== null && $filters['course_id'] !== '') {
            $query->where('course_id', (int) $filters['course_id']);
        }

        if (in_array($filters['type'], ['file', 'text', 'online'], true)) {
            $query->where('type', $filters['type']);
        }

        $allAssignments = $query->get()->map(function (Assignment $assignment) {
            $assignment->computed_status = $this->assignmentStatus($assignment);

            return $assignment;
        });

        $statusCounts = [
            'all' => $allAssignments->count(),
            'active' => $allAssignments->where('computed_status', 'active')->count(),
            'grading' => $allAssignments->where('computed_status', 'grading')->count(),
            'overdue' => $allAssignments->where('computed_status', 'overdue')->count(),
            'completed' => $allAssignments->where('computed_status', 'completed')->count(),
        ];
        $summary = [
            'total_submissions' => $allAssignments->sum('submissions_count'),
            'graded_submissions' => $allAssignments->sum('graded_submissions_count'),
            'expected_submissions' => $allAssignments->sum(fn ($assignment) => $assignment->class?->students_count ?? 0),
        ];

        $assignments = $allAssignments;
        if (in_array($filters['status'], ['active', 'grading', 'completed', 'overdue'], true)) {
            $assignments = $allAssignments
                ->filter(fn (Assignment $assignment) => $assignment->computed_status === $filters['status'])
                ->values();
        }
        $assignments = CollectionPaginator::make($assignments->values(), $request, 10);

        $classes = $user->createdClasses()->orderBy('name')->get();
        $courses = $user->createdCourses()->orderBy('name')->get();

        return view('pages.teacher.assignments', compact('assignments', 'classes', 'courses', 'filters', 'statusCounts', 'summary'));
    }

    public function store(Request $request)
    {
        if ($uploadErrorResponse = $this->attachmentUploadErrorResponse($request)) {
            return $uploadErrorResponse;
        }

        $validated = $request->validate($this->rules($request), $this->validationMessages());
        $this->authorizeClassAndCourse($request, $validated);

        $validated['teacher_id'] = $request->user()->id;
        $validated['type'] = $this->normalizeType($validated['type'] ?? 'file');
        $validated['total_points'] = $validated['total_points'] ?? 100;

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = UploadedFileStorage::storeWithOriginalName($request->file('attachment'), 'assignments');
        }

        $assignment = Assignment::create($validated);
        $this->notifyStudents($request, $assignment);

        return back()->with('success', 'Tạo bài tập thành công!');
    }

    public function show(Request $request, Assignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);

        $assignment->load([
            'course:id,name',
            'class:id,name,subject',
            'class.students:id,name,email',
            'submissions' => fn ($query) => $query
                ->with([
                    'student:id,name,email',
                    'grades' => fn ($gradeQuery) => $gradeQuery->latest('graded_at'),
                ])
                ->latest('submitted_at'),
        ]);

        $students = $assignment->class?->students ?? collect();
        $submissionsByStudent = $assignment->submissions->keyBy('student_id');

        $allRoster = $students->map(function (User $student) use ($submissionsByStudent) {
            $submission = $submissionsByStudent->get($student->id);
            return (object) [
                'student' => $student,
                'submission' => $submission,
                'grade' => $submission?->grades->first(),
            ];
        })->values();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'submission_status' => (string) $request->query('submission_status', 'all'),
            'grading_status' => (string) $request->query('grading_status', 'all'),
            'sort' => (string) $request->query('sort', 'pending_first'),
        ];

        $roster = $allRoster
            ->when($filters['q'] !== '', function ($collection) use ($filters) {
                $keyword = Str::lower($filters['q']);
                return $collection->filter(function ($row) use ($keyword) {
                    $name = Str::lower((string) ($row->student?->name ?? ''));
                    $email = Str::lower((string) ($row->student?->email ?? ''));
                    return str_contains($name, $keyword) || str_contains($email, $keyword);
                });
            })
            ->when($filters['submission_status'] === 'submitted', fn ($collection) => $collection->filter(fn ($row) => $row->submission !== null))
            ->when($filters['submission_status'] === 'not_submitted', fn ($collection) => $collection->filter(fn ($row) => $row->submission === null))
            ->when($filters['grading_status'] === 'graded', fn ($collection) => $collection->filter(fn ($row) => $row->grade !== null))
            ->when($filters['grading_status'] === 'pending', fn ($collection) => $collection->filter(fn ($row) => $row->submission !== null && $row->grade === null));

        $roster = (match ($filters['sort']) {
            'name_asc' => $roster->sortBy(fn ($row) => mb_strtolower((string) ($row->student?->name ?? ''))),
            'name_desc' => $roster->sortByDesc(fn ($row) => mb_strtolower((string) ($row->student?->name ?? ''))),
            'submitted_newest' => $roster->sortByDesc(fn ($row) => optional($row->submission?->submitted_at)->timestamp ?? 0),
            'submitted_oldest' => $roster->sortBy(fn ($row) => optional($row->submission?->submitted_at)->timestamp ?? PHP_INT_MAX),
            default => $roster->sortBy(fn ($row) => [
                $row->submission === null ? 2 : ($row->grade === null ? 0 : 1),
                mb_strtolower((string) ($row->student?->name ?? '')),
            ]),
        })->values();

        $submittedCount = $allRoster->filter(fn ($row) => $row->submission !== null)->count();
        $gradedCount = $allRoster->filter(fn ($row) => $row->grade !== null)->count();

        return view('pages.teacher.assignment-detail', [
            'assignment' => $assignment,
            'students' => $students,
            'roster' => $roster,
            'allRoster' => $allRoster,
            'filters' => $filters,
            'submittedCount' => $submittedCount,
            'gradedCount' => $gradedCount,
        ]);
    }

    public function update(Request $request, Assignment $assignment)
    {
        abort_unless($assignment->teacher_id === $request->user()->id, 403);

        if ($uploadErrorResponse = $this->attachmentUploadErrorResponse($request)) {
            return $uploadErrorResponse;
        }

        $validated = $request->validate(
            $this->rules($request) + [
                'remove_attachment' => 'nullable|boolean',
            ],
            $this->validationMessages()
        );
        $this->authorizeClassAndCourse($request, $validated);

        $validated['type'] = $this->normalizeType($validated['type'] ?? $assignment->type);

        if ($request->boolean('remove_attachment') && $assignment->attachment) {
            Storage::delete($assignment->attachment);
            $validated['attachment'] = null;
        }

        if ($request->hasFile('attachment')) {
            if ($assignment->attachment) {
                Storage::delete($assignment->attachment);
            }
            $validated['attachment'] = UploadedFileStorage::storeWithOriginalName($request->file('attachment'), 'assignments');
        }

        unset($validated['remove_attachment']);
        $assignment->update($validated);

        return back()->with('success', 'Cập nhật bài tập thành công!');
    }

    public function destroy(Request $request, Assignment $assignment)
    {
        abort_unless($assignment->teacher_id === $request->user()->id, 403);
        $assignment->delete();

        return redirect()->route('teacher.assignments')->with('success', 'Đã xóa bài tập!');
    }

    public function previewAttachment(Request $request, Assignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);
        $this->abortMissingAttachment($assignment);

        $path = $this->attachmentPath($assignment);
        $extension = strtolower(pathinfo($assignment->attachment, PATHINFO_EXTENSION));
        $mime = Storage::mimeType($assignment->attachment) ?: 'application/octet-stream';
        $previewType = $this->previewType($extension, $mime);
        $convertedPdfAvailable = false;
        $previewText = null;
        $spreadsheetRows = [];

        if (in_array($previewType, ['word', 'spreadsheet', 'presentation'], true)) {
            $convertedPdfAvailable = $this->convertedPdfPath($assignment) !== null;
        }

        if (! $convertedPdfAvailable && in_array($previewType, ['text', 'word', 'presentation'], true)) {
            $previewText = $this->extractReadableText($path, $extension);
        } elseif (! $convertedPdfAvailable && $previewType === 'spreadsheet') {
            $spreadsheetRows = $this->extractSpreadsheetRows($path);
        }

        return view('pages.teacher.assignment-attachment-preview', [
            'assignment' => $assignment,
            'extension' => $extension,
            'mime' => $mime,
            'previewType' => $previewType,
            'convertedPdfAvailable' => $convertedPdfAvailable,
            'previewText' => $previewText,
            'spreadsheetRows' => $spreadsheetRows,
            'filename' => basename($assignment->attachment),
        ]);
    }

    public function inlineAttachment(Request $request, Assignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);
        $this->abortMissingAttachment($assignment);

        $path = $this->attachmentPath($assignment);
        $extension = strtolower(pathinfo($assignment->attachment, PATHINFO_EXTENSION));
        $mime = Storage::mimeType($assignment->attachment) ?: 'application/octet-stream';

        abort_unless(in_array($this->previewType($extension, $mime), ['pdf', 'image', 'text'], true), 415);

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes(basename($assignment->attachment)).'"',
        ]);
    }

    public function convertedAttachment(Request $request, Assignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);
        $this->abortMissingAttachment($assignment);

        $pdfPath = $this->convertedPdfPath($assignment);
        abort_unless($pdfPath !== null, 415);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.addslashes(pathinfo($assignment->attachment, PATHINFO_FILENAME).'.pdf').'"',
        ]);
    }

    public function downloadAttachment(Request $request, Assignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);
        $this->abortMissingAttachment($assignment);

        return Storage::download($assignment->attachment, basename($assignment->attachment));
    }

    public function gradingBoard(Request $request, Assignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);

        $assignment->load([
            'course:id,name',
            'class:id,name,subject',
            'class.students:id,name,email',
            'submissions' => fn ($query) => $query
                ->with([
                    'student:id,name,email',
                    'grades' => fn ($gradeQuery) => $gradeQuery->latest('graded_at'),
                ])
                ->latest('submitted_at'),
        ]);

        $submissionsByStudent = $assignment->submissions->keyBy('student_id');
        $allRoster = ($assignment->class?->students ?? collect())->map(function (User $student) use ($submissionsByStudent) {
            $submission = $submissionsByStudent->get($student->id);
            return (object) [
                'student' => $student,
                'submission' => $submission,
                'grade' => $submission?->grades->first(),
            ];
        })->values();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'submission_status' => (string) $request->query('submission_status', 'all'),
            'grading_status' => (string) $request->query('grading_status', 'all'),
            'sort' => (string) $request->query('sort', 'pending_first'),
        ];

        $roster = $allRoster
            ->when($filters['q'] !== '', function ($collection) use ($filters) {
                $keyword = Str::lower($filters['q']);
                return $collection->filter(function ($row) use ($keyword) {
                    $name = Str::lower((string) ($row->student?->name ?? ''));
                    $email = Str::lower((string) ($row->student?->email ?? ''));

                    return str_contains($name, $keyword) || str_contains($email, $keyword);
                });
            })
            ->when($filters['submission_status'] === 'submitted', fn ($collection) => $collection->filter(fn ($row) => $row->submission !== null))
            ->when($filters['submission_status'] === 'not_submitted', fn ($collection) => $collection->filter(fn ($row) => $row->submission === null))
            ->when($filters['grading_status'] === 'graded', fn ($collection) => $collection->filter(fn ($row) => $row->grade !== null))
            ->when($filters['grading_status'] === 'pending', fn ($collection) => $collection->filter(fn ($row) => $row->submission !== null && $row->grade === null));

        $roster = (match ($filters['sort']) {
            'name_asc' => $roster->sortBy(fn ($row) => mb_strtolower((string) ($row->student?->name ?? ''))),
            'name_desc' => $roster->sortByDesc(fn ($row) => mb_strtolower((string) ($row->student?->name ?? ''))),
            'submitted_newest' => $roster->sortByDesc(fn ($row) => optional($row->submission?->submitted_at)->timestamp ?? 0),
            'submitted_oldest' => $roster->sortBy(fn ($row) => optional($row->submission?->submitted_at)->timestamp ?? PHP_INT_MAX),
            default => $roster->sortBy(fn ($row) => [
                $row->submission === null ? 2 : ($row->grade === null ? 0 : 1),
                mb_strtolower((string) ($row->student?->name ?? '')),
            ]),
        })->values();

        $selectedStudentId = (int) $request->query('student_id', 0);
        $selectedRow = $roster->first(fn ($row) => $row->student->id === $selectedStudentId);

        if (! $selectedRow) {
            $selectedRow = $roster->first(fn ($row) => $row->submission !== null) ?? $roster->first();
        }

        $selectedSubmission = $selectedRow?->submission;

        return view('pages.teacher.assignment-grading-board', [
            'assignment' => $assignment,
            'roster' => $roster,
            'allRoster' => $allRoster,
            'filters' => $filters,
            'selectedSubmission' => $selectedSubmission,
            'selectedStudent' => $selectedRow?->student,
            'selectedGrade' => $selectedRow?->grade,
        ]);
    }

    public function gradingSubmission(Request $request, Assignment $assignment, Submission $submission)
    {
        $this->authorizeAssignment($request, $assignment);
        abort_unless((int) $submission->assignment_id === (int) $assignment->id, 404);

        $assignment->load([
            'course:id,name',
            'class:id,name,subject',
        ]);
        $submission->load([
            'student:id,name,email',
            'grades' => fn ($query) => $query->latest('graded_at'),
        ]);

        return view('pages.teacher.assignment-grading-submission', [
            'assignment' => $assignment,
            'submission' => $submission,
            'grade' => $submission->grades->first(),
        ]);
    }

    public function generateAiGrade(
        Request $request,
        Assignment $assignment,
        Submission $submission,
        AssignmentAiGrader $grader
    ): JsonResponse {
        $this->authorizeAssignment($request, $assignment);
        abort_unless((int) $submission->assignment_id === (int) $assignment->id, 404);

        if (! VipFeature::isVip($request->user())) {
            return response()->json([
                'success' => false,
                'message' => VipFeature::aiGradingMessage(),
            ], 403);
        }

        $validated = $request->validate([
            'rubric' => 'nullable|string|max:2000',
        ], [
            'rubric.max' => 'Tiêu chí AI không được vượt quá :max ký tự.',
        ]);

        try {
            $assignment->loadMissing(['course:id,name', 'class:id,name,subject']);
            $submission->loadMissing(['student:id,name,email']);
            $suggestion = $grader->grade($assignment, $submission, $validated['rubric'] ?? null);

            return response()->json([
                'success' => true,
                'score' => $suggestion['score'],
                'feedback' => $suggestion['feedback'],
                'summary' => $suggestion['summary'],
                'warnings' => $suggestion['warnings'],
            ]);
        } catch (Throwable $exception) {
            Log::warning('Assignment AI grading failed.', [
                'assignment_id' => $assignment->id,
                'submission_id' => $submission->id,
                'teacher_id' => $request->user()->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không tạo được gợi ý chấm bằng AI. Vui lòng thử lại hoặc chấm thủ công.',
            ], 422);
        }
    }

    private function rules(Request $request): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'class_id' => ['required', 'integer', Rule::exists('classes', 'id')->where('teacher_id', $request->user()->id)],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')->where('teacher_id', $request->user()->id)],
            'due_at' => [
                'nullable',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    try {
                        if ($value && \Carbon\Carbon::parse($value)->lt(now()->startOfMinute())) {
                            $fail('Hạn nộp phải là thời điểm hiện tại hoặc sau hiện tại.');
                        }
                    } catch (Throwable) {
                        // The date rule reports invalid date formats.
                    }
                },
            ],
            'total_points' => 'nullable|integer|min:1|max:10000',
            'type' => ['nullable', Rule::in(['file', 'text', 'online', 'essay', 'code', 'project', 'practice'])],
            'attachment' => 'nullable|file|max:102400|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,png,jpg,jpeg,webp,txt',
        ];
    }

    private function validationMessages(): array
    {
        return [
            'attachment.uploaded' => 'Tải tệp đính kèm thất bại. Vui lòng kiểm tra lại dung lượng/tên tệp và thử lại.',
            'attachment.file' => 'Tệp đính kèm không hợp lệ.',
            'attachment.max' => 'Tệp đính kèm không được vượt quá 100MB.',
            'attachment.mimes' => 'Định dạng tệp không được hỗ trợ.',
        ];
    }

    private function authorizeAssignment(Request $request, Assignment $assignment): void
    {
        abort_unless($assignment->teacher_id === $request->user()->id, 403);
    }

    private function abortMissingAttachment(Assignment $assignment): void
    {
        abort_unless($assignment->attachment && Storage::exists($assignment->attachment), 404);
    }

    private function attachmentPath(Assignment $assignment): string
    {
        return $this->normalizeFilesystemPath(Storage::path($assignment->attachment));
    }

    private function convertedPdfPath(Assignment $assignment): ?string
    {
        $extension = strtolower(pathinfo($assignment->attachment, PATHINFO_EXTENSION));
        if (! in_array($extension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true)) {
            return null;
        }

        $sourcePath = $this->attachmentPath($assignment);
        $modified = Storage::lastModified($assignment->attachment);
        $hash = substr(sha1($assignment->id.'|'.$assignment->attachment.'|'.$modified), 0, 16);
        $relativePath = "previews/assignments/{$assignment->id}-{$hash}.pdf";

        if (Storage::exists($relativePath)) {
            return Storage::path($relativePath);
        }

        return $this->convertOfficeFileToPdf($sourcePath, $relativePath);
    }

    private function convertOfficeFileToPdf(string $sourcePath, string $targetRelativePath): ?string
    {
        $sourcePath = $this->normalizeFilesystemPath($sourcePath);
        $binary = $this->libreOfficeBinary();
        if ($binary === null) {
            Log::warning('LibreOffice binary not found for assignment preview conversion.', [
                'source' => $sourcePath,
                'target' => $targetRelativePath,
            ]);
            return null;
        }

        $tempDir = $this->normalizeFilesystemPath(storage_path('app/preview-tmp/'.Str::uuid()->toString()));
        $outputDir = $this->normalizeFilesystemPath($tempDir.DIRECTORY_SEPARATOR.'out');

        if (! is_dir($outputDir) && ! mkdir($outputDir, 0777, true) && ! is_dir($outputDir)) {
            return null;
        }

        try {
            $profileDir = $this->normalizeFilesystemPath($tempDir.DIRECTORY_SEPARATOR.'profile');
            if (! is_dir($profileDir)) {
                mkdir($profileDir, 0777, true);
            }

            $process = new Process([
                $binary,
                '--headless',
                '-env:UserInstallation=file:///'.str_replace('\\', '/', $profileDir),
                '--norestore',
                '--nofirststartwizard',
                '--convert-to',
                'pdf',
                '--outdir',
                $outputDir,
                $sourcePath,
            ]);
            $process->setWorkingDirectory($this->libreOfficeWorkingDirectory($binary));
            $process->setTimeout(60);
            $process->run();

            $convertedPath = $this->normalizeFilesystemPath($outputDir.DIRECTORY_SEPARATOR.pathinfo($sourcePath, PATHINFO_FILENAME).'.pdf');
            if (! is_file($convertedPath)) {
                $matches = glob($outputDir.DIRECTORY_SEPARATOR.'*.pdf') ?: [];
                $convertedPath = $matches[0] ?? null;
            }

            if (! $convertedPath || ! is_file($convertedPath)) {
                Log::warning('LibreOffice conversion did not produce PDF in temp directory.', [
                    'source' => $sourcePath,
                    'binary' => $binary,
                    'stdout' => $process->getOutput(),
                    'stderr' => $process->getErrorOutput(),
                ]);
                return $this->convertOfficeFileToPdfDirect($binary, $sourcePath, $targetRelativePath);
            }

            Storage::makeDirectory(dirname($targetRelativePath));
            $targetAbsolutePath = $this->normalizeFilesystemPath(Storage::path($targetRelativePath));
            if (! @copy($convertedPath, $targetAbsolutePath)) {
                Log::warning('Failed copying converted PDF from temp output. Trying direct conversion fallback.', [
                    'source' => $sourcePath,
                    'binary' => $binary,
                    'temp_pdf' => $convertedPath,
                    'target' => $targetAbsolutePath,
                ]);
                return $this->convertOfficeFileToPdfDirect($binary, $sourcePath, $targetRelativePath);
            }

            return $targetAbsolutePath;
        } catch (Throwable $exception) {
            Log::warning('LibreOffice conversion failed in temp mode. Trying direct conversion fallback.', [
                'source' => $sourcePath,
                'binary' => $binary,
                'target' => $targetRelativePath,
                'error' => $exception->getMessage(),
            ]);
            return $this->convertOfficeFileToPdfDirect($binary, $sourcePath, $targetRelativePath);
        } finally {
            $this->deleteDirectory($tempDir);
        }
    }

    private function convertOfficeFileToPdfDirect(string $binary, string $sourcePath, string $targetRelativePath): ?string
    {
        $sourcePath = $this->normalizeFilesystemPath($sourcePath);
        Storage::makeDirectory(dirname($targetRelativePath));
        $targetAbsolutePath = $this->normalizeFilesystemPath(Storage::path($targetRelativePath));
        $targetDir = $this->normalizeFilesystemPath(dirname($targetAbsolutePath));
        $profileDir = $this->normalizeFilesystemPath(storage_path('app/preview-tmp/'.Str::uuid()->toString().'/profile'));

        try {
            if (! is_dir($profileDir)) {
                mkdir($profileDir, 0777, true);
            }

            $process = new Process([
                $binary,
                '--headless',
                '-env:UserInstallation=file:///'.str_replace('\\', '/', $profileDir),
                '--norestore',
                '--nofirststartwizard',
                '--convert-to',
                'pdf',
                '--outdir',
                $targetDir,
                $sourcePath,
            ]);
            $process->setWorkingDirectory($this->libreOfficeWorkingDirectory($binary));
            $process->setTimeout(60);
            $process->run();

            $directOutput = $this->normalizeFilesystemPath($targetDir.DIRECTORY_SEPARATOR.pathinfo($sourcePath, PATHINFO_FILENAME).'.pdf');
            if (! is_file($directOutput)) {
                Log::warning('LibreOffice direct conversion did not produce output file.', [
                    'source' => $sourcePath,
                    'binary' => $binary,
                    'target_dir' => $targetDir,
                    'stdout' => $process->getOutput(),
                    'stderr' => $process->getErrorOutput(),
                ]);
                return null;
            }

            if (realpath($directOutput) !== realpath($targetAbsolutePath) && ! @copy($directOutput, $targetAbsolutePath)) {
                Log::warning('LibreOffice direct conversion created file but failed to copy to hashed target.', [
                    'source' => $sourcePath,
                    'binary' => $binary,
                    'direct_output' => $directOutput,
                    'target' => $targetAbsolutePath,
                ]);
                return null;
            }

            return $targetAbsolutePath;
        } catch (Throwable $exception) {
            Log::warning('LibreOffice direct conversion failed.', [
                'source' => $sourcePath,
                'binary' => $binary,
                'target' => $targetAbsolutePath,
                'error' => $exception->getMessage(),
            ]);
            return null;
        } finally {
            $this->deleteDirectory(dirname($profileDir));
        }
    }

    private function libreOfficeBinary(): ?string
    {
        $candidates = array_values(array_filter([
            config('services.libreoffice.path'),
            env('LIBREOFFICE_PATH'),
            'soffice',
            'libreoffice',
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files\\LibreOffice\\program\\soffice.com',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.com',
        ]));

        foreach ($candidates as $candidate) {
            $candidate = $this->normalizeBinaryPath($candidate);
            if ($this->looksLikeAbsoluteWindowsPath($candidate) && ! is_file($candidate)) {
                continue;
            }

            $candidate = $this->consoleLibreOfficeBinary($candidate);

            try {
                $process = new Process([$candidate, '--version']);
                $process->setTimeout(10);
                $process->run();

                if ($process->isSuccessful()) {
                    return $candidate;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    private function looksLikeAbsoluteWindowsPath(string $path): bool
    {
        return (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }

    private function normalizeBinaryPath(string $path): string
    {
        if ($this->looksLikeAbsoluteWindowsPath($path)) {
            return str_replace('/', DIRECTORY_SEPARATOR, $path);
        }

        return $path;
    }

    private function normalizeFilesystemPath(string $path): string
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $real = realpath($normalized);

        return $real !== false ? $real : $normalized;
    }

    private function consoleLibreOfficeBinary(string $candidate): string
    {
        if (str_ends_with(strtolower($candidate), 'soffice.exe')) {
            $consoleBinary = dirname($candidate).DIRECTORY_SEPARATOR.'soffice.com';
            if (is_file($consoleBinary)) {
                return $consoleBinary;
            }
        }

        return $candidate;
    }

    private function libreOfficeWorkingDirectory(string $binary): string
    {
        if ($this->looksLikeAbsoluteWindowsPath($binary)) {
            $dir = dirname($binary);
            if (is_dir($dir)) {
                return $dir;
            }
        }

        return base_path();
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = array_diff(scandir($directory) ?: [], ['.', '..']);
        foreach ($items as $item) {
            $path = $directory.DIRECTORY_SEPARATOR.$item;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }

        @rmdir($directory);
    }

    private function previewType(string $extension, string $mime): string
    {
        if ($extension === 'pdf' || $mime === 'application/pdf') {
            return 'pdf';
        }

        if (str_starts_with($mime, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return 'image';
        }

        if (str_starts_with($mime, 'text/') || in_array($extension, ['txt', 'csv', 'md', 'log'], true)) {
            return 'text';
        }

        if (in_array($extension, ['docx', 'doc'], true)) {
            return 'word';
        }

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            return 'spreadsheet';
        }

        if (in_array($extension, ['pptx', 'ppt'], true)) {
            return 'presentation';
        }

        return 'download';
    }

    private function extractReadableText(string $path, string $extension): ?string
    {
        try {
            return match ($extension) {
                'txt', 'csv', 'md', 'log' => Str::limit((string) file_get_contents($path), 30000, "\n..."),
                'docx' => $this->extractTextFromOfficeZip($path, ['word/document.xml', 'word/header1.xml', 'word/footer1.xml']),
                'pptx' => $this->extractTextFromOfficeZip($path, $this->presentationParts($path)),
                default => null,
            };
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<int, string>  $parts
     */
    private function extractTextFromOfficeZip(string $path, array $parts): ?string
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return null;
        }

        $text = '';
        foreach ($parts as $part) {
            $xml = $zip->getFromName($part);
            if ($xml === false) {
                continue;
            }

            $xml = preg_replace('/<w:tab\/>|<a:tab\/>/', ' ', $xml) ?? $xml;
            $xml = preg_replace('/<\/w:p>|<\/a:p>/', "\n", $xml) ?? $xml;
            $xml = preg_replace('/<\/w:tr>|<\/a:tr>/', "\n", $xml) ?? $xml;
            $xml = preg_replace('/<\/w:tc>|<\/a:tc>/', "\t", $xml) ?? $xml;
            $text .= "\n".html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        }

        $zip->close();
        $text = trim(preg_replace('/\R{3,}/', "\n\n", preg_replace('/[ \t]+/', ' ', $text) ?? $text) ?? $text);

        return $text !== '' ? Str::limit($text, 30000, "\n...") : null;
    }

    /**
     * @return array<int, string>
     */
    private function presentationParts(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return [];
        }

        $parts = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (is_string($name) && preg_match('#^ppt/slides/slide\d+\.xml$#', $name)) {
                $parts[] = $name;
            }
        }
        $zip->close();

        natsort($parts);

        return array_values($parts);
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function extractSpreadsheetRows(string $path): array
    {
        try {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = [];
            foreach ($sheet->toArray(null, true, true, true) as $index => $row) {
                if ($index > 80) {
                    break;
                }
                $rows[] = array_values($row);
            }

            return $rows;
        } catch (Throwable) {
            return [];
        }
    }

    private function authorizeClassAndCourse(Request $request, array $validated): void
    {
        abort_unless(
            ClassModel::where('id', $validated['class_id'])->where('teacher_id', $request->user()->id)->exists(),
            403
        );

        if (! empty($validated['course_id'])) {
            abort_unless(
                Course::where('id', $validated['course_id'])->where('teacher_id', $request->user()->id)->exists(),
                403
            );
        }
    }

    private function normalizeType(string $type): string
    {
        return match ($type) {
            'text', 'essay' => 'text',
            'online', 'code', 'practice' => 'online',
            default => 'file',
        };
    }

    private function assignmentStatus(Assignment $assignment): string
    {
        $expected = (int) ($assignment->class?->students_count ?? 0);
        $submitted = (int) ($assignment->submissions_count ?? 0);
        $graded = (int) ($assignment->graded_submissions_count ?? 0);

        if ($expected > 0 && $submitted >= $expected && $graded >= $submitted) {
            return 'completed';
        }

        if ($submitted > 0 && $graded < $submitted) {
            return 'grading';
        }

        if ($assignment->due_at && $assignment->due_at->isPast()) {
            return 'overdue';
        }

        return 'active';
    }

    private function notifyStudents(Request $request, Assignment $assignment): void
    {
        $studentIds = $assignment->class?->students()->pluck('users.id') ?? collect();

        foreach ($studentIds->unique() as $studentId) {
            Notification::create([
                'user_id' => $studentId,
                'audience_role' => 'student',
                'type' => 'assignment_assigned',
                'title' => 'Bài tập mới được giao',
                'body' => "Giáo viên {$request->user()->name} đã giao bài tập mới: \"{$assignment->title}\".",
                'data' => json_encode([
                    'assignment_id' => $assignment->id,
                    'class_id' => $assignment->class_id,
                    'teacher_id' => $request->user()->id,
                ]),
            ]);
        }
    }

    private function attachmentUploadErrorResponse(Request $request)
    {
        if (! $request->has('attachment') || $request->hasFile('attachment')) {
            return null;
        }

        $file = $request->file('attachment');
        $error = is_object($file) && method_exists($file, 'getError') ? $file->getError() : null;

        Log::warning('Assignment attachment upload failed before validation.', [
            'error_code' => $error,
            'content_length' => $request->server('CONTENT_LENGTH'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'memory_limit' => ini_get('memory_limit'),
            'max_input_time' => ini_get('max_input_time'),
        ]);

        return back()
            ->withErrors(['attachment' => $this->attachmentUploadErrorMessage($error)])
            ->withInput();
    }

    private function attachmentUploadErrorMessage(?int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File vượt quá giới hạn dung lượng cho phép trên máy chủ.',
            UPLOAD_ERR_PARTIAL => 'File tải lên chưa hoàn tất, vui lòng thử lại.',
            UPLOAD_ERR_NO_TMP_DIR => 'Máy chủ thiếu thư mục tạm để nhận file upload.',
            UPLOAD_ERR_CANT_WRITE => 'Máy chủ không thể ghi file tạm, vui lòng kiểm tra quyền ghi thư mục temp.',
            UPLOAD_ERR_EXTENSION => 'Upload bị chặn bởi extension của PHP trên máy chủ.',
            default => 'Tải tệp đính kèm thất bại. Vui lòng kiểm tra lại dung lượng/tên tệp và thử lại.',
        };
    }
}
