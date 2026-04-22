<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GradingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $quizIds = $user->quizzes()->pluck('id');
        $assignmentIds = $user->assignments()->pluck('id');

        $pendingGrades = [];

        // Quiz submissions
        $quizSubmissions = DB::table('quiz_user')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_user.quiz_id')
            ->join('users', 'users.id', '=', 'quiz_user.user_id')
            ->whereIn('quiz_id', $quizIds)
            ->whereNotNull('submitted_at')
            ->select([
                'quiz_user.user_id as student_id',
                'users.name as student_name',
                'quizzes.title as item_title',
                'quizzes.total_points as max_score',
                'quiz_user.score as score',
                'quiz_user.quiz_id as quiz_id',
                'quiz_user.submitted_at',
                'quiz_user.is_graded',
                DB::raw("'quiz' as type"),
            ])
            ->get();

        foreach ($quizSubmissions as $sub) {
            $pendingGrades[] = (object)[
                'id'           => 'quiz_' . $sub->student_id . '_' . $sub->quiz_id,
                'student_id'   => $sub->student_id,
                'student_name' => $sub->student_name,
                'item_title'   => $sub->item_title,
                'max_score'    => $sub->max_score,
                'score'        => $sub->score,
                'submitted_at' => $sub->submitted_at,
                'is_graded'    => $sub->is_graded,
                'type'         => 'quiz',
                'gradable_id'  => $sub->quiz_id,
            ];
        }

        // Assignment submissions
        $assignmentSubmissions = DB::table('submissions')
            ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
            ->join('users', 'users.id', '=', 'submissions.student_id')
            ->whereIn('assignment_id', $assignmentIds)
            ->select([
                'submissions.id',
                'submissions.student_id',
                'users.name as student_name',
                'assignments.title as item_title',
                'assignments.total_points as max_score',
                'submissions.submitted_at',
            ])
            ->get();

        foreach ($assignmentSubmissions as $sub) {
            $pendingGrades[] = (object)[
                'id'           => 'assign_' . $sub->id,
                'student_id'   => $sub->student_id,
                'student_name' => $sub->student_name,
                'item_title'   => $sub->item_title,
                'max_score'    => $sub->max_score,
                'score'        => null,
                'submitted_at' => $sub->submitted_at,
                'is_graded'    => false,
                'type'         => 'assignment',
                'gradable_id'  => $sub->id, // submission id for assignment
            ];
        }

        $pendingGrades = collect($pendingGrades)
            ->sortByDesc('submitted_at')
            ->values()
            ->all();

        return view('pages.teacher.grading', compact('pendingGrades'));
    }

    public function storeGrade(Request $request)
    {
        $validated = $request->validate([
            'gradable_type' => 'required|in:quiz,assignment',
            'gradable_id'   => 'required',
            'student_id'   => 'required|exists:users,id',
            'score'        => 'required|numeric|min:0',
            'feedback'     => 'nullable|string|max:2000',
        ]);

        // Map form type to polymorphic class
        $gradableClass = match ($validated['gradable_type']) {
            'quiz' => \App\Models\Quiz::class,
            'assignment' => \App\Models\Submission::class,
        };

        // Validate gradable exists
        if ($validated['gradable_type'] === 'quiz') {
            abort_unless(\App\Models\Quiz::where('id', $validated['gradable_id'])->exists(), 422);
        } else {
            abort_unless(\App\Models\Submission::where('id', $validated['gradable_id'])->exists(), 422);
        }

        Grade::updateOrCreate(
            [
                'student_id'    => $validated['student_id'],
                'gradable_type' => $gradableClass,
                'gradable_id'   => $validated['gradable_id'],
            ],
            [
                'score'     => $validated['score'],
                'feedback'  => $validated['feedback'] ?? null,
                'grader_id' => $request->user()->id,
                'graded_at' => now(),
            ]
        );

        if ($validated['gradable_type'] === 'quiz') {
            DB::table('quiz_user')
                ->where('quiz_id', $validated['gradable_id'])
                ->where('user_id', $validated['student_id'])
                ->update(['is_graded' => true, 'score' => $validated['score']]);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Đã lưu điểm thành công!']);
        }

        return back()->with('success', 'Đã lưu điểm thành công!');
    }

    public function exportGrades(Request $request)
    {
        $user = $request->user();

        $quizIds = $user->quizzes()->pluck('id');

        $grades = DB::table('quiz_user')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_user.quiz_id')
            ->join('users', 'users.id', '=', 'quiz_user.user_id')
            ->whereIn('quiz_id', $quizIds)
            ->whereNotNull('submitted_at')
            ->select([
                'users.name as student_name',
                'users.email',
                'quizzes.title as quiz_title',
                'quiz_user.score',
                'quizzes.total_points',
                'quiz_user.is_graded',
                'quiz_user.submitted_at',
            ])
            ->get();

        $csvData = "Học sinh,Email,Bài kiểm tra,Điểm,Tổng điểm,Xếp loại,Đã chấm,Ngày nộp\n";

        foreach ($grades as $g) {
            $pct = $g->total_points > 0 ? round(($g->score / $g->total_points) * 100) : 0;
            $gradedText = $g->is_graded ? 'Co' : 'Chua';
            if ($pct >= 90) {
                $gradeLetter = 'A';
            } elseif ($pct >= 80) {
                $gradeLetter = 'B';
            } elseif ($pct >= 70) {
                $gradeLetter = 'C';
            } elseif ($pct >= 60) {
                $gradeLetter = 'D';
            } else {
                $gradeLetter = 'F';
            }
            $csvData .= $g->student_name . ',' . $g->email . ',' . $g->quiz_title . ',' . $g->score . ',' . $g->total_points . ',' . $gradeLetter . ',' . $gradedText . ',' . $g->submitted_at . "\n";
        }

        return response($csvData, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition'=> 'attachment; filename="bang_diem_' . now()->format('Ymd') . '.csv"',
        ]);
    }
}
