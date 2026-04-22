<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\ClassModel;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $courses = $user->createdCourses()
            ->withCount(['students', 'quizzes', 'assignments'])
            ->latest()
            ->get();

        return view('pages.teacher.courses', compact('courses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'class_id'    => 'nullable|exists:classes,id',
            'subject'     => 'nullable|string|max:100',
            'color'      => 'nullable|string|max:20',
            'status'     => 'nullable|in:draft,active,completed',
        ]);

        $validated['teacher_id'] = $request->user()->id;
        $validated['color'] = $validated['color'] ?? '#3b82f6';
        $validated['status'] = $validated['status'] ?? 'draft';

        Course::create($validated);

        return back()->with('success', 'Tạo khóa học thành công!');
    }

    public function update(Request $request, Course $course)
    {
        abort_unless($course->teacher_id === $request->user()->id, 403);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'class_id'    => 'nullable|exists:classes,id',
            'subject'     => 'nullable|string|max:100',
            'color'      => 'nullable|string|max:20',
            'status'     => 'nullable|in:draft,active,completed',
        ]);

        $course->update($validated);

        return back()->with('success', 'Cập nhật khóa học thành công!');
    }

    public function destroy(Request $request, Course $course)
    {
        abort_unless($course->teacher_id === $request->user()->id, 403);
        $course->delete();

        return redirect()->route('teacher.courses')->with('success', 'Đã xóa khóa học!');
    }
}
