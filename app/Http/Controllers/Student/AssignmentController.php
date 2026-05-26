<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Submission;
use App\Support\CollectionPaginator;
use App\Support\UploadedFileStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class AssignmentController extends Controller
{
    private const SUBMISSION_ATTACHMENT_MAX_KB = 102400;

    public function index(Request $request)
    {
        $user = $request->user();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status', 'all'),
            'course_id' => $request->integer('course_id') ?: null,
            'class_id' => $request->integer('class_id') ?: null,
            'type' => $request->query('type', 'all'),
        ];

        if ($filters['course_id']) {
            abort_unless($user->courses()->where('courses.id', $filters['course_id'])->exists(), 404);
        }

        if ($filters['class_id']) {
            abort_unless($user->classes()->where('classes.id', $filters['class_id'])->exists(), 404);
        }

        $courses = $user->courses()->orderBy('name')->get(['courses.id', 'courses.name']);
        $classes = $user->classes()->orderBy('name')->get(['classes.id', 'classes.name']);

        $assignments = Assignment::query()
            ->where(function ($query) use ($user) {
                $query->whereHas('class', fn ($class) =>
                    $class->whereHas('students', fn ($students) => $students->where('users.id', $user->id))
                )
                    ->orWhereHas('course', fn ($course) =>
                        $course->whereHas('students', fn ($students) => $students->where('users.id', $user->id))
                    );
            })
            ->when($filters['course_id'], fn ($query) => $query->where('course_id', $filters['course_id']))
            ->when($filters['class_id'], fn ($query) => $query->where('class_id', $filters['class_id']))
            ->when(in_array($filters['type'], ['file', 'text', 'online'], true), fn ($query) => $query->where('type', $filters['type']))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $needle = $filters['q'];
                $query->where(function ($inner) use ($needle) {
                    $inner->where('title', 'like', "%{$needle}%")
                        ->orWhere('description', 'like', "%{$needle}%")
                        ->orWhereHas('teacher', fn ($teacher) => $teacher->where('name', 'like', "%{$needle}%"))
                        ->orWhereHas('class', fn ($class) => $class->where('name', 'like', "%{$needle}%"))
                        ->orWhereHas('course', fn ($course) => $course->where('name', 'like', "%{$needle}%"));
                });
            })
            ->with([
                'teacher:id,name,email',
                'class:id,name',
                'course:id,name,color,icon',
                'submissions' => fn ($query) => $query
                    ->where('student_id', $user->id)
                    ->with(['grades' => fn ($grades) => $grades->latest('graded_at')])
                    ->latest('submitted_at'),
            ])
            ->latest('created_at')
            ->get()
            ->map(function (Assignment $assignment) {
                $submission = $assignment->submissions->first();
                $grade = $submission?->grades->first();
                $isOverdue = ! $submission && $assignment->due_at && $assignment->due_at->isPast();

                $assignment->submission = $submission;
                $assignment->grade = $grade;
                $assignment->scope_name = $assignment->course?->name ?? $assignment->class?->name ?? 'Không rõ lớp';
                $assignment->score_pct = $grade && $assignment->total_points > 0
                    ? round(((float) $grade->score / (float) $assignment->total_points) * 100, 1)
                    : null;
                $assignment->due_label = $this->dueLabel($assignment);
                $assignment->due_tone = $this->dueTone($assignment);
                $assignment->can_submit = ! $grade && (! $assignment->due_at || $assignment->due_at->isFuture() || $submission);

                if ($grade) {
                    $assignment->status = 'graded';
                } elseif ($submission) {
                    $assignment->status = 'submitted';
                } elseif ($isOverdue) {
                    $assignment->status = 'overdue';
                } else {
                    $assignment->status = 'pending';
                }

                return $assignment;
            })
            ->sortBy(function (Assignment $assignment) {
                if (! $assignment->due_at) {
                    return [2, PHP_INT_MAX, -$assignment->created_at->timestamp];
                }

                if ($assignment->due_at->isPast()) {
                    return [1, -$assignment->due_at->timestamp, -$assignment->created_at->timestamp];
                }

                return [0, abs($assignment->due_at->timestamp - now()->timestamp), -$assignment->created_at->timestamp];
            })
            ->values();

        $summarySource = $assignments;
        $summary = [
            'total' => $summarySource->count(),
            'pending' => $summarySource->where('status', 'pending')->count(),
            'submitted' => $summarySource->where('status', 'submitted')->count(),
            'graded' => $summarySource->where('status', 'graded')->count(),
            'overdue' => $summarySource->where('status', 'overdue')->count(),
            'due_this_week' => $summarySource
                ->filter(fn ($assignment) => in_array($assignment->status, ['pending', 'submitted'], true)
                    && $assignment->due_at
                    && $assignment->due_at->isFuture()
                    && $assignment->due_at->diffInDays(now()) <= 7)
                ->count(),
            'avg_score' => $summarySource->where('status', 'graded')->whereNotNull('score_pct')->avg('score_pct'),
        ];

        if (in_array($filters['status'], ['pending', 'submitted', 'graded', 'overdue'], true)) {
            $assignments = $assignments->where('status', $filters['status']);
        }

        $assignments = CollectionPaginator::make($assignments->values(), $request, 10);

        return view('pages.student.assignments', compact(
            'assignments',
            'courses',
            'classes',
            'filters',
            'summary'
        ));
    }

    public function show(Request $request, Assignment $assignment)
    {
        $user = $request->user();

        $assignment->load(['teacher', 'class', 'course']);

        abort_unless($this->isAssignedToUser($assignment, $user), 403);

        $submission = Submission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $user->id)
            ->first();

        $grade = $submission?->grades()
            ->where('student_id', $user->id)
            ->latest('graded_at')
            ->first();

        $attachmentPreview = $this->attachmentPreviewData($assignment);
        $submissionAttachmentPreview = $submission?->attachment
            ? $this->submissionAttachmentPreviewData($submission)
            : null;
        $submissionAttachmentMaxKilobytes = $this->submissionAttachmentMaxKilobytes();
        $submissionAttachmentMaxBytes = $submissionAttachmentMaxKilobytes * 1024;
        $submissionAttachmentMaxLabel = $this->humanFileSize($submissionAttachmentMaxBytes);

        return view('pages.student.assignment-detail', compact(
            'assignment',
            'submission',
            'grade',
            'attachmentPreview',
            'submissionAttachmentPreview',
            'submissionAttachmentMaxKilobytes',
            'submissionAttachmentMaxBytes',
            'submissionAttachmentMaxLabel'
        ));
    }

    public function inlineAttachment(Request $request, Assignment $assignment)
    {
        $user = $request->user();
        abort_unless($this->isAssignedToUser($assignment, $user), 403);
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

    public function previewAttachment(Request $request, Assignment $assignment)
    {
        $user = $request->user();
        abort_unless($this->isAssignedToUser($assignment, $user), 403);
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

        return view('pages.student.assignment-attachment-preview', [
            'assignment' => $assignment,
            'extension' => $extension,
            'mime' => $mime,
            'previewType' => $previewType,
            'previewText' => $previewText,
            'spreadsheetRows' => $spreadsheetRows,
            'convertedPdfAvailable' => $convertedPdfAvailable,
            'filename' => basename($assignment->attachment),
            'backUrl' => route('student.assignment-detail', $assignment),
            'downloadUrl' => route('student.assignment.attachment.download', $assignment),
            'inlineUrl' => in_array($previewType, ['pdf', 'image', 'text'], true)
                ? route('student.assignment.attachment.inline', $assignment)
                : null,
            'convertedUrl' => $convertedPdfAvailable
                ? route('student.assignment.attachment.converted', $assignment)
                : null,
        ]);
    }

    public function convertedAttachment(Request $request, Assignment $assignment)
    {
        $user = $request->user();
        abort_unless($this->isAssignedToUser($assignment, $user), 403);
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
        $user = $request->user();
        abort_unless($this->isAssignedToUser($assignment, $user), 403);
        $this->abortMissingAttachment($assignment);

        return Storage::download($assignment->attachment, basename($assignment->attachment));
    }

    public function inlineSubmissionAttachment(Request $request, Submission $submission)
    {
        $this->authorizeSubmissionAttachment($request, $submission);

        $extension = strtolower(pathinfo($submission->attachment, PATHINFO_EXTENSION));
        $mime = Storage::mimeType($submission->attachment) ?: 'application/octet-stream';

        abort_unless(in_array($this->previewType($extension, $mime), ['pdf', 'image', 'text'], true), 415);

        return response()->file($this->normalizeFilesystemPath(Storage::path($submission->attachment)), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes(basename($submission->attachment)).'"',
        ]);
    }

    public function previewSubmissionAttachment(Request $request, Submission $submission)
    {
        $this->authorizeSubmissionAttachment($request, $submission);

        $assignment = $submission->assignment;
        abort_unless($assignment instanceof Assignment, 404);

        $path = $this->normalizeFilesystemPath(Storage::path($submission->attachment));
        $extension = strtolower(pathinfo($submission->attachment, PATHINFO_EXTENSION));
        $mime = Storage::mimeType($submission->attachment) ?: 'application/octet-stream';
        $previewType = $this->previewType($extension, $mime);
        $convertedPdfAvailable = false;
        $previewText = null;
        $spreadsheetRows = [];

        if (in_array($previewType, ['word', 'spreadsheet', 'presentation'], true)) {
            $convertedPdfAvailable = $this->convertedSubmissionPdfPath($submission) !== null;
        }

        if (! $convertedPdfAvailable && in_array($previewType, ['text', 'word', 'presentation'], true)) {
            $previewText = $this->extractReadableText($path, $extension);
        } elseif (! $convertedPdfAvailable && $previewType === 'spreadsheet') {
            $spreadsheetRows = $this->extractSpreadsheetRows($path);
        }

        return view('pages.student.assignment-attachment-preview', [
            'assignment' => $assignment,
            'extension' => $extension,
            'mime' => $mime,
            'previewType' => $previewType,
            'previewText' => $previewText,
            'spreadsheetRows' => $spreadsheetRows,
            'convertedPdfAvailable' => $convertedPdfAvailable,
            'filename' => basename($submission->attachment),
            'backUrl' => route('student.assignment-detail', $assignment),
            'downloadUrl' => route('student.submissions.attachment.download', $submission),
            'inlineUrl' => in_array($previewType, ['pdf', 'image', 'text'], true)
                ? route('student.submissions.attachment.inline', $submission)
                : null,
            'convertedUrl' => $convertedPdfAvailable
                ? route('student.submissions.attachment.converted', $submission)
                : null,
        ]);
    }

    public function convertedSubmissionAttachment(Request $request, Submission $submission)
    {
        $this->authorizeSubmissionAttachment($request, $submission);

        $pdfPath = $this->convertedSubmissionPdfPath($submission);
        abort_unless($pdfPath !== null, 415);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.addslashes(pathinfo($submission->attachment, PATHINFO_FILENAME).'.pdf').'"',
        ]);
    }

    public function downloadSubmissionAttachment(Request $request, Submission $submission)
    {
        $this->authorizeSubmissionAttachment($request, $submission);

        return Storage::download($submission->attachment, basename($submission->attachment));
    }

    public function submit(Request $request, Assignment $assignment)
    {
        $this->logSubmissionAttachmentRequest($request, $assignment);

        if ($uploadErrorResponse = $this->attachmentUploadErrorResponse($request)) {
            return $uploadErrorResponse;
        }

        $validated = $request->validate([
            'content' => 'nullable|string|max:10000',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,zip,png,jpg,jpeg,gif,webp|max:'.$this->submissionAttachmentMaxKilobytes(),
        ]);

        $user = $request->user();
        abort_unless($this->isAssignedToUser($assignment, $user), 403);

        $existing = Submission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $user->id)
            ->first();

        abort_if($assignment->due_at && $assignment->due_at->isPast() && ! $existing, 403);

        if ($existing?->grades()->exists()) {
            return back()->with('info', 'Bài tập đã được chấm điểm nên không thể cập nhật bài nộp.');
        }

        if (! $request->filled('content') && ! $request->hasFile('attachment') && ! $existing?->attachment) {
            return back()
                ->withErrors(['content' => 'Bạn cần nhập nội dung hoặc đính kèm file bài làm.'])
                ->withInput();
        }

        if ($existing) {
            $updates = [
                'content' => $validated['content'] ?? $existing->content,
                'submitted_at' => now(),
            ];

            if ($request->hasFile('attachment')) {
                if ($existing->attachment) {
                    Storage::delete($existing->attachment);
                }
                $updates['attachment'] = UploadedFileStorage::storeWithOriginalName($request->file('attachment'), 'submissions');
            }

            $existing->update($updates);

            return back()->with('success', 'Đã cập nhật bài nộp!');
        }

        $data = [
            'assignment_id' => $assignment->id,
            'student_id' => $user->id,
            'content' => $validated['content'] ?? null,
            'submitted_at' => now(),
        ];

        if ($request->hasFile('attachment')) {
            $data['attachment'] = UploadedFileStorage::storeWithOriginalName($request->file('attachment'), 'submissions');
        }

        Submission::create($data);

        return back()->with('success', 'Nộp bài thành công!');
    }

    private function isAssignedToUser(Assignment $assignment, $user): bool
    {
        return ($assignment->class_id && $user->classes()->where('classes.id', $assignment->class_id)->exists())
            || ($assignment->course_id && $user->courses()->where('courses.id', $assignment->course_id)->exists());
    }

    private function submissionAttachmentPreviewData(Submission $submission): ?array
    {
        if (! $submission->attachment || ! Storage::exists($submission->attachment)) {
            return null;
        }

        $extension = strtolower(pathinfo($submission->attachment, PATHINFO_EXTENSION));
        $mime = Storage::mimeType($submission->attachment) ?: 'application/octet-stream';
        $type = $this->previewType($extension, $mime);

        return [
            'extension' => $extension,
            'mime' => $mime,
            'type' => $type,
            'filename' => basename($submission->attachment),
            'preview_url' => route('student.submissions.attachment.preview', $submission),
            'inline_url' => in_array($type, ['pdf', 'image', 'text'], true)
                ? route('student.submissions.attachment.inline', $submission)
                : null,
            'download_url' => route('student.submissions.attachment.download', $submission),
        ];
    }

    private function authorizeSubmissionAttachment(Request $request, Submission $submission): void
    {
        abort_unless((int) $submission->student_id === (int) $request->user()->id, 403);
        abort_unless($submission->attachment && Storage::exists($submission->attachment), 404);

        $assignment = $submission->assignment;
        abort_unless($assignment && $this->isAssignedToUser($assignment, $request->user()), 403);
    }

    private function logSubmissionAttachmentRequest(Request $request, Assignment $assignment): void
    {
        $file = $request->file('attachment');

        Log::info('Student assignment submission upload request received.', [
            'assignment_id' => $assignment->id,
            'user_id' => $request->user()?->id,
            'has_attachment_input' => $request->has('attachment'),
            'has_file' => $request->hasFile('attachment'),
            'content_length' => $request->server('CONTENT_LENGTH'),
            'request_keys' => array_keys($request->except(['_token'])),
            'file_present' => $file !== null,
            'file_valid' => is_object($file) && method_exists($file, 'isValid') ? $file->isValid() : null,
            'file_error' => is_object($file) && method_exists($file, 'getError') ? $file->getError() : null,
            'file_name' => is_object($file) && method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : null,
            'file_size' => is_object($file) && method_exists($file, 'getSize') ? $file->getSize() : null,
            'files_keys' => array_keys($request->files->all()),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
        ]);
    }

    private function attachmentUploadErrorResponse(Request $request)
    {
        $error = $this->attachmentUploadErrorCode($request);

        if ($error === null && $this->requestExceedsPostMaxSize($request)) {
            $error = UPLOAD_ERR_INI_SIZE;
        }

        if ($error === null || in_array($error, [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE], true)) {
            return null;
        }

        Log::warning('Student submission attachment upload failed before validation.', [
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

    private function attachmentUploadErrorCode(Request $request): ?int
    {
        $file = $request->file('attachment');
        if ($file instanceof UploadedFile) {
            return $file->getError();
        }

        $file = $request->files->get('attachment');
        if ($file instanceof UploadedFile) {
            return $file->getError();
        }

        $rawFile = $_FILES['attachment'] ?? null;
        if (is_array($rawFile) && isset($rawFile['error'])) {
            return (int) $rawFile['error'];
        }

        return null;
    }

    private function requestExceedsPostMaxSize(Request $request): bool
    {
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        $postMaxBytes = $this->iniSizeToBytes((string) ini_get('post_max_size'));

        return $contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes;
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

    private function submissionAttachmentMaxKilobytes(): int
    {
        $limits = [self::SUBMISSION_ATTACHMENT_MAX_KB * 1024];

        foreach (['upload_max_filesize', 'post_max_size'] as $setting) {
            $bytes = $this->iniSizeToBytes((string) ini_get($setting));
            if ($bytes > 0) {
                $limits[] = $bytes;
            }
        }

        return max(1, (int) floor(min($limits) / 1024));
    }

    private function iniSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }

    private function humanFileSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            $megabytes = $bytes / 1024 / 1024;

            return rtrim(rtrim(number_format($megabytes, 1, '.', ''), '0'), '.').'MB';
        }

        if ($bytes >= 1024) {
            $kilobytes = $bytes / 1024;

            return rtrim(rtrim(number_format($kilobytes, 1, '.', ''), '0'), '.').'KB';
        }

        return $bytes.'B';
    }

    private function attachmentPreviewData(Assignment $assignment): ?array
    {
        if (! $assignment->attachment || ! Storage::exists($assignment->attachment)) {
            return null;
        }

        $extension = strtolower(pathinfo($assignment->attachment, PATHINFO_EXTENSION));
        $mime = Storage::mimeType($assignment->attachment) ?: 'application/octet-stream';
        $type = $this->previewType($extension, $mime);

        return [
            'extension' => $extension,
            'mime' => $mime,
            'type' => $type,
            'filename' => basename($assignment->attachment),
            'preview_url' => route('student.assignment.attachment.preview', $assignment),
            'inline_url' => in_array($type, ['pdf', 'image', 'text'], true)
                ? route('student.assignment.attachment.inline', $assignment)
                : null,
            'converted_url' => null,
            'download_url' => route('student.assignment.attachment.download', $assignment),
        ];
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

    private function convertedSubmissionPdfPath(Submission $submission): ?string
    {
        $extension = strtolower(pathinfo($submission->attachment, PATHINFO_EXTENSION));
        if (! in_array($extension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true)) {
            return null;
        }

        $sourcePath = $this->normalizeFilesystemPath(Storage::path($submission->attachment));
        $modified = Storage::lastModified($submission->attachment);
        $hash = substr(sha1($submission->id.'|'.$submission->attachment.'|'.$modified), 0, 16);
        $relativePath = "previews/submissions/{$submission->id}-{$hash}.pdf";

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
            Log::warning('LibreOffice binary not found for student assignment preview conversion.', [
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
            $safeSourcePath = $this->temporaryLibreOfficeSourcePath($sourcePath, $tempDir);
            if (! @copy($sourcePath, $safeSourcePath)) {
                Log::warning('Failed copying source file to LibreOffice temp input.', [
                    'source' => $sourcePath,
                    'temp_source' => $safeSourcePath,
                ]);

                return null;
            }

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
                $safeSourcePath,
            ]);
            $process->setWorkingDirectory($this->libreOfficeWorkingDirectory($binary));
            $process->setTimeout(60);
            $process->run();

            $convertedPath = $this->normalizeFilesystemPath($outputDir.DIRECTORY_SEPARATOR.pathinfo($safeSourcePath, PATHINFO_FILENAME).'.pdf');
            if (! is_file($convertedPath)) {
                $matches = glob($outputDir.DIRECTORY_SEPARATOR.'*.pdf') ?: [];
                $convertedPath = $matches[0] ?? null;
            }

            if (! $convertedPath || ! is_file($convertedPath)) {
                Log::warning('LibreOffice student conversion did not produce PDF in temp directory.', [
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
                return $this->convertOfficeFileToPdfDirect($binary, $sourcePath, $targetRelativePath);
            }

            return $targetAbsolutePath;
        } catch (Throwable $exception) {
            Log::warning('LibreOffice student conversion failed in temp mode. Trying direct conversion fallback.', [
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
            $tempDir = dirname($profileDir);
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $safeSourcePath = $this->temporaryLibreOfficeSourcePath($sourcePath, $tempDir);
            if (! @copy($sourcePath, $safeSourcePath)) {
                return null;
            }

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
                $safeSourcePath,
            ]);
            $process->setWorkingDirectory($this->libreOfficeWorkingDirectory($binary));
            $process->setTimeout(60);
            $process->run();

            $directOutput = $this->normalizeFilesystemPath($targetDir.DIRECTORY_SEPARATOR.pathinfo($safeSourcePath, PATHINFO_FILENAME).'.pdf');
            if (! is_file($directOutput)) {
                Log::warning('LibreOffice student direct conversion did not produce output file.', [
                    'source' => $sourcePath,
                    'temp_source' => $safeSourcePath,
                    'binary' => $binary,
                    'target_dir' => $targetDir,
                    'stdout' => $process->getOutput(),
                    'stderr' => $process->getErrorOutput(),
                ]);
                return null;
            }

            if (realpath($directOutput) !== realpath($targetAbsolutePath) && ! @copy($directOutput, $targetAbsolutePath)) {
                return null;
            }

            return $targetAbsolutePath;
        } catch (Throwable $exception) {
            Log::warning('LibreOffice student direct conversion failed.', [
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

    private function temporaryLibreOfficeSourcePath(string $sourcePath, string $tempDir): string
    {
        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        return $this->normalizeFilesystemPath($tempDir.DIRECTORY_SEPARATOR.'source'.($extension !== '' ? '.'.$extension : ''));
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

    private function extractReadableText(string $path, string $extension): ?string
    {
        try {
            return match ($extension) {
                'txt', 'csv', 'md', 'log' => $this->readTextPreview($path),
                'docx' => $this->extractTextFromOfficeZip($path, ['word/document.xml', 'word/header1.xml', 'word/footer1.xml']),
                'pptx' => $this->extractTextFromOfficeZip($path, $this->presentationParts($path)),
                default => null,
            };
        } catch (Throwable) {
            return null;
        }
    }

    private function readTextPreview(string $path): ?string
    {
        $handle = @fopen($path, 'rb');
        if (! $handle) {
            return null;
        }

        try {
            $content = stream_get_contents($handle, 30000);
        } finally {
            fclose($handle);
        }

        $content = trim((string) $content);

        return $content !== '' ? $content : null;
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
            if (@filesize($path) > 10 * 1024 * 1024) {
                return [];
            }

            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
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

    private function dueLabel(Assignment $assignment): string
    {
        if (! $assignment->due_at) {
            return 'Không giới hạn';
        }

        if ($assignment->due_at->isPast()) {
            return 'Đã quá hạn ' . $assignment->due_at->format('d/m/Y H:i');
        }

        if ($assignment->due_at->isToday()) {
            return 'Hạn hôm nay ' . $assignment->due_at->format('H:i');
        }

        if ($assignment->due_at->isTomorrow()) {
            return 'Hạn ngày mai ' . $assignment->due_at->format('H:i');
        }

        return 'Hạn ' . $assignment->due_at->format('d/m/Y H:i');
    }

    private function dueTone(Assignment $assignment): string
    {
        if (! $assignment->due_at) {
            return 'muted';
        }

        if ($assignment->due_at->isPast()) {
            return 'danger';
        }

        if ($assignment->due_at->diffInHours(now()) <= 48) {
            return 'warning';
        }

        return 'muted';
    }
}
