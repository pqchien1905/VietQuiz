<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $assignments = Assignment::whereHas('class', fn($q) =>
            $q->whereHas('students', fn($s) => $s->where('users.id', $user->id))
        )
            ->orWhereHas('course', fn($q) =>
                $q->whereHas('students', fn($s) => $s->where('users.id', $user->id))
            )
            ->with('teacher', 'class', 'course')
            ->latest()
            ->get()
            ->map(function ($assignment) use ($user) {
                $submission = $assignment->submissions()
                    ->where('student_id', $user->id)
                    ->first();
                $grade = $assignment->grades()
                    ->where('student_id', $user->id)
                    ->first();

                $assignment->submission = $submission;
                $assignment->grade = $grade;

                if ($submission && $grade) {
                    $assignment->status = 'graded';
                } elseif ($submission) {
                    $assignment->status = 'submitted';
                } else {
                    $assignment->status = 'pending';
                }

                return $assignment;
            });

        return view('pages.student.assignments', compact('assignments'));
    }

    public function show(Request $request, Assignment $assignment)
    {
        $user = $request->user();

        abort_unless(
            $assignment->class?->students()->where('users.id', $user->id)->exists()
            || $assignment->course?->students()->where('users.id', $user->id)->exists(),
            403
        );

        $submission = $assignment->submissions()
            ->where('student_id', $user->id)
            ->first();

        $grade = $assignment->grades()
            ->where('student_id', $user->id)
            ->first();

        return view('pages.student.assignment-detail', compact(
            'assignment', 'submission', 'grade'
        ));
    }

    public function submit(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'content'    => 'nullable|string|max:10000',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $user = $request->user();

        $existing = $assignment->submissions()
            ->where('student_id', $user->id)
            ->first();

        if ($existing) {
            if ($validated['content']) {
                $existing->update(['content' => $validated['content']]);
            }
            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store('submissions');
                $existing->update(['attachment' => $path]);
            }
            return back()->with('success', 'Đã cập nhật bài nộp!');
        }

        $data = [
            'assignment_id'  => $assignment->id,
            'student_id'     => $user->id,
            'content'       => $validated['content'] ?? null,
            'submitted_at'   => now(),
        ];

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('submissions');
        }

        \App\Models\Submission::create($data);

        return back()->with('success', 'Nộp bài thành công!');
    }
}
