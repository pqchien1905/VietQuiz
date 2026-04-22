<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $classes = $user->createdClasses()
            ->withCount('students')
            ->with(['assignments', 'courses'])
            ->latest()
            ->get();

        return view('pages.teacher.classes', compact('classes'));
    }

    public function show(Request $request, ClassModel $class)
    {
        $this->authorizeTeacher($request, $class);

        $class->load(['students', 'assignments.quiz']);
        $studentCount = $class->students()->count();

        return view('pages.teacher.class-detail', compact('class', 'studentCount'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'subject'     => 'nullable|string|max:255',
            'grade_level' => 'nullable|string|max:50',
        ]);

        $validated['teacher_id'] = $request->user()->id;
        $validated['code'] = strtoupper(Str::random(6));

        ClassModel::create($validated);

        return redirect()->route('teacher.classes')
            ->with('success', 'Tạo lớp thành công! Mã lớp: ' . $validated['code']);
    }

    public function update(Request $request, ClassModel $class)
    {
        $this->authorizeTeacher($request, $class);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'subject'     => 'nullable|string|max:255',
            'grade_level' => 'nullable|string|max:50',
        ]);

        $class->update($validated);

        return redirect()->route('teacher.class-detail', $class)
            ->with('success', 'Cập nhật lớp thành công!');
    }

    public function destroy(Request $request, ClassModel $class)
    {
        $this->authorizeTeacher($request, $class);

        $class->delete();

        return redirect()->route('teacher.classes')
            ->with('success', 'Đã xóa lớp thành công!');
    }

    public function removeStudent(Request $request, ClassModel $class, $studentId)
    {
        $this->authorizeTeacher($request, $class);
        $class->students()->detach($studentId);

        return redirect()->route('teacher.class-detail', $class)
            ->with('success', 'Đã xóa học sinh khỏi lớp!');
    }

    private function authorizeTeacher(Request $request, ClassModel $class): void
    {
        abort_unless($class->teacher_id === $request->user()->id, 403);
    }
}
