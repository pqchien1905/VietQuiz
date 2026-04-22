<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $assignments = Assignment::where('teacher_id', $user->id)
            ->with('class', 'course')
            ->withCount('submissions')
            ->latest()
            ->get();

        $classes = $user->createdClasses()->get();
        $courses = $user->createdCourses()->get();

        return view('pages.teacher.assignments', compact('assignments', 'classes', 'courses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'class_id'    => 'required|exists:classes,id',
            'course_id'   => 'nullable|exists:courses,id',
            'due_at'      => 'nullable|date',
            'total_points' => 'nullable|integer|min:1|max:10000',
            'type'        => 'nullable|in:essay,code,project,practice',
        ]);

        abort_unless(
            ClassModel::where('id', $validated['class_id'])->where('teacher_id', $request->user()->id)->exists(),
            403
        );

        $validated['teacher_id'] = $request->user()->id;
        $validated['type'] = $validated['type'] ?? 'essay';
        $validated['total_points'] = $validated['total_points'] ?? 100;

        Assignment::create($validated);

        return back()->with('success', 'Tạo bài tập thành công!');
    }

    public function update(Request $request, Assignment $assignment)
    {
        abort_unless($assignment->teacher_id === $request->user()->id, 403);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'class_id'    => 'required|exists:classes,id',
            'course_id'   => 'nullable|exists:courses,id',
            'due_at'      => 'nullable|date',
            'total_points'=> 'nullable|integer|min:1|max:10000',
            'type'        => 'nullable|in:essay,code,project,practice',
        ]);

        $assignment->update($validated);

        return back()->with('success', 'Cập nhật bài tập thành công!');
    }

    public function destroy(Request $request, Assignment $assignment)
    {
        abort_unless($assignment->teacher_id === $request->user()->id, 403);
        $assignment->delete();

        return redirect()->route('teacher.assignments')->with('success', 'Đã xóa bài tập!');
    }
}
