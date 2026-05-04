<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Notification;
use App\Support\CollectionPaginator;
use Illuminate\Http\Request;
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
        $validated = $request->validate($this->rules($request));
        $this->authorizeClassAndCourse($request, $validated);

        $validated['teacher_id'] = $request->user()->id;
        $validated['type'] = $this->normalizeType($validated['type'] ?? 'file');
        $validated['total_points'] = $validated['total_points'] ?? 100;

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('assignments');
        }

        $assignment = Assignment::create($validated);
        $this->notifyStudents($request, $assignment);

        return back()->with('success', 'Tạo bài tập thành công!');
    }

    public function update(Request $request, Assignment $assignment)
    {
        abort_unless($assignment->teacher_id === $request->user()->id, 403);

        $validated = $request->validate($this->rules($request) + [
            'remove_attachment' => 'nullable|boolean',
        ]);
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
            $validated['attachment'] = $request->file('attachment')->store('assignments');
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

    private function rules(Request $request): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'class_id' => ['required', 'integer', Rule::exists('classes', 'id')->where('teacher_id', $request->user()->id)],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')->where('teacher_id', $request->user()->id)],
            'due_at' => 'nullable|date',
            'total_points' => 'nullable|integer|min:1|max:10000',
            'type' => ['nullable', Rule::in(['file', 'text', 'online', 'essay', 'code', 'project', 'practice'])],
            'attachment' => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,png,jpg,jpeg,webp,txt',
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
        return Storage::path($assignment->attachment);
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
        $binary = $this->libreOfficeBinary();
        if ($binary === null) {
            return null;
        }

        $tempDir = storage_path('app/preview-tmp/'.Str::uuid()->toString());
        $outputDir = $tempDir.DIRECTORY_SEPARATOR.'out';

        if (! is_dir($outputDir) && ! mkdir($outputDir, 0777, true) && ! is_dir($outputDir)) {
            return null;
        }

        try {
            $process = new Process([
                $binary,
                '--headless',
                '-env:UserInstallation=file:///'.str_replace('\\', '/', storage_path('app/libreoffice-profile')),
                '--norestore',
                '--nofirststartwizard',
                '--convert-to',
                'pdf',
                '--outdir',
                $outputDir,
                $sourcePath,
            ]);
            $process->setTimeout(60);
            $process->run();

            $convertedPath = $outputDir.DIRECTORY_SEPARATOR.pathinfo($sourcePath, PATHINFO_FILENAME).'.pdf';
            if (! is_file($convertedPath)) {
                $matches = glob($outputDir.DIRECTORY_SEPARATOR.'*.pdf') ?: [];
                $convertedPath = $matches[0] ?? null;
            }

            if (! $convertedPath || ! is_file($convertedPath)) {
                return null;
            }

            Storage::makeDirectory(dirname($targetRelativePath));
            if (! copy($convertedPath, Storage::path($targetRelativePath))) {
                return null;
            }

            return Storage::path($targetRelativePath);
        } catch (Throwable) {
            return null;
        } finally {
            $this->deleteDirectory($tempDir);
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
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
        ]));

        foreach ($candidates as $candidate) {
            if (str_contains($candidate, DIRECTORY_SEPARATOR) && ! is_file($candidate)) {
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
}
