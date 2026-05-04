<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Submission;
use App\Support\CollectionPaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssignmentController extends Controller
{
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
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
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
            });

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

        $submission = $assignment->submissions()
            ->where('student_id', $user->id)
            ->first();

        $grade = $submission?->grades()
            ->where('student_id', $user->id)
            ->latest('graded_at')
            ->first();

        return view('pages.student.assignment-detail', compact(
            'assignment', 'submission', 'grade'
        ));
    }

    public function submit(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'content' => 'nullable|string|max:10000',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,zip,png,jpg,jpeg,gif|max:10240',
        ]);

        $user = $request->user();
        abort_unless($this->isAssignedToUser($assignment, $user), 403);

        $existing = $assignment->submissions()
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
                $updates['attachment'] = $request->file('attachment')->store('submissions');
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
            $data['attachment'] = $request->file('attachment')->store('submissions');
        }

        Submission::create($data);

        return back()->with('success', 'Nộp bài thành công!');
    }

    private function isAssignedToUser(Assignment $assignment, $user): bool
    {
        return ($assignment->class_id && $user->classes()->where('classes.id', $assignment->class_id)->exists())
            || ($assignment->course_id && $user->courses()->where('courses.id', $assignment->course_id)->exists());
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
