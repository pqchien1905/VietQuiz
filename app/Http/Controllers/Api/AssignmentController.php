<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $assignments = Assignment::whereHas('class.students', fn($q) => $q->where('users.id', $user->id))
            ->orWhereHas('course.students', fn($q) => $q->where('users.id', $user->id))
            ->with('teacher', 'class', 'course')
            ->latest()
            ->get()
            ->map(function ($a) use ($user) {
                $a->submission = $a->submissions()->where('student_id', $user->id)->first();
                $a->grade = $a->grades()->where('student_id', $user->id)->first();
                return $a;
            });

        return response()->json(['assignments' => $assignments]);
    }

    public function show(Request $request, Assignment $assignment)
    {
        $user = $request->user();

        abort_unless(
            $assignment->class?->students()->where('users.id', $user->id)->exists()
            || $assignment->course?->students()->where('users.id', $user->id)->exists(),
            403
        );

        $assignment->load('teacher');

        return response()->json([
            'assignment' => $assignment,
            'submission' => $assignment->submissions()->where('student_id', $user->id)->first(),
            'grade'      => $assignment->grades()->where('student_id', $user->id)->first(),
        ]);
    }

    public function submit(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'content'    => 'nullable|string|max:10000',
            'attachment'  => 'nullable|file|max:10240',
        ]);

        $user = $request->user();

        $existing = $assignment->submissions()->where('student_id', $user->id)->first();

        if ($existing) {
            $existing->update(['content' => $validated['content'] ?? $existing->content]);
            return response()->json(['message' => 'Đã cập nhật bài nộp.', 'submission' => $existing->fresh()]);
        }

        $data = [
            'assignment_id'  => $assignment->id,
            'student_id'     => $user->id,
            'content'        => $validated['content'] ?? null,
            'submitted_at'   => now(),
        ];

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('submissions');
        }

        $submission = \App\Models\Submission::create($data);

        return response()->json([
            'message'    => 'Nộp bài thành công!',
            'submission' => $submission,
        ], 201);
    }
}
