<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Assignment;
use App\Models\Question;
use Illuminate\Http\Request;

class TrashController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type', 'all');

        $query = null;

        $trashedQuizzes = Quiz::onlyTrashed()
            ->where('teacher_id', $request->user()->id)
            ->get()
            ->map(fn($q) => (object)[
                'id'         => $q->id,
                'name'       => $q->title,
                'type'       => 'quiz',
                'deleted_at'  => $q->deleted_at,
                'days_left'  => $q->deleted_at->diffInDays(now()),
            ]);

        $trashedAssignments = Assignment::onlyTrashed()
            ->where('teacher_id', $request->user()->id)
            ->get()
            ->map(fn($a) => (object)[
                'id'         => $a->id,
                'name'       => $a->title,
                'type'       => 'assignment',
                'deleted_at' => $a->deleted_at,
                'days_left'  => $a->deleted_at->diffInDays(now()),
            ]);

        $trashedQuestions = Question::onlyTrashed()
            ->where('teacher_id', $request->user()->id)
            ->get()
            ->map(fn($q) => (object)[
                'id'         => $q->id,
                'name'       => \Str::limit($q->content, 50),
                'type'       => 'question',
                'deleted_at' => $q->deleted_at,
                'days_left'  => $q->deleted_at->diffInDays(now()),
            ]);

        $allTrashed = $trashedQuizzes
            ->concat($trashedAssignments)
            ->concat($trashedQuestions)
            ->sortByDesc('deleted_at')
            ->values();

        return view('pages.teacher.trash', compact('allTrashed', 'type'));
    }

    public function restore(Request $request, string $type, int $id)
    {
        abort_unless($request->user()->isTeacher(), 403);

        match ($type) {
            'quiz'       => Quiz::onlyTrashed()->where('teacher_id', $request->user()->id)->where('id', $id)->restore(),
            'assignment' => Assignment::onlyTrashed()->where('teacher_id', $request->user()->id)->where('id', $id)->restore(),
            'question'   => Question::onlyTrashed()->where('teacher_id', $request->user()->id)->where('id', $id)->restore(),
            default      => null,
        };

        return back()->with('success', 'Đã khôi phục mục thành công!');
    }

    public function forceDelete(Request $request, string $type, int $id)
    {
        abort_unless($request->user()->isTeacher(), 403);

        match ($type) {
            'quiz'       => Quiz::onlyTrashed()->where('teacher_id', $request->user()->id)->where('id', $id)->forceDelete(),
            'assignment' => Assignment::onlyTrashed()->where('teacher_id', $request->user()->id)->where('id', $id)->forceDelete(),
            'question'   => Question::onlyTrashed()->where('teacher_id', $request->user()->id)->where('id', $id)->forceDelete(),
            default      => null,
        };

        return back()->with('success', 'Đã xóa vĩnh viễn!');
    }

    public function restoreAll(Request $request)
    {
        Quiz::onlyTrashed()->where('teacher_id', $request->user()->id)->restore();
        Assignment::onlyTrashed()->where('teacher_id', $request->user()->id)->restore();
        Question::onlyTrashed()->where('teacher_id', $request->user()->id)->restore();

        return back()->with('success', 'Đã khôi phục tất cả!');
    }

    public function forceDeleteAll(Request $request)
    {
        Quiz::onlyTrashed()->where('teacher_id', $request->user()->id)->forceDelete();
        Assignment::onlyTrashed()->where('teacher_id', $request->user()->id)->forceDelete();
        Question::onlyTrashed()->where('teacher_id', $request->user()->id)->forceDelete();

        return back()->with('success', 'Đã xóa vĩnh viễn tất cả!');
    }
}
