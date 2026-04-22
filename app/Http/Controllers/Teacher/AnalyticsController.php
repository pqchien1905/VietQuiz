<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $period = $request->get('period', 'month');

        $startDate = match ($period) {
            'week'  => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year'  => now()->startOfYear(),
            default  => now()->startOfMonth(),
        };

        // Basic stats
        $classCount = $user->createdClasses()->count();
        $quizCount = $user->quizzes()->count();
        $studentCount = $user->createdClasses()->withCount('students')->get()->sum('students_count');
        $avgScore = Grade::where('grader_id', $user->id)->avg('score');
        $totalGraded = Grade::where('grader_id', $user->id)->count();

        // Scores by class
        $classIds = $user->createdClasses()->pluck('id');
        $scoreByClass = DB::table('grades')
            ->join('users', 'grades.student_id', '=', 'users.id')
            ->join('class_user', 'class_user.user_id', '=', 'users.id')
            ->whereIn('class_user.class_id', $classIds)
            ->where('grades.graded_at', '>=', $startDate)
            ->groupBy('class_user.class_id')
            ->select([
                'class_user.class_id',
                DB::raw('AVG(grades.score) as avg_score'),
                DB::raw('COUNT(*) as count'),
            ])
            ->get()
            ->map(fn($r) => [
                'class_id'  => $r->class_id,
                'class_name'=> ClassModel::find($r->class_id)?->name ?? 'N/A',
                'avg_score' => round($r->avg_score ?? 0, 1),
                'count'     => $r->count,
            ]);

        // Score distribution
        $totalGrades = Grade::where('grader_id', $user->id)->count();
        $excellent = Grade::where('grader_id', $user->id)->where('score', '>=', 8)->count();
        $good      = Grade::where('grader_id', $user->id)->whereBetween('score', [6, 7.9])->count();
        $average   = Grade::where('grader_id', $user->id)->whereBetween('score', [5, 5.9])->count();
        $weak      = Grade::where('grader_id', $user->id)->where('score', '<', 5)->count();

        $distribution = [
            ['label' => 'Giỏi (≥8)', 'pct' => $totalGrades > 0 ? round($excellent / $totalGrades * 100) : 0, 'color' => '#22c55e'],
            ['label' => 'Khá (6-7.9)', 'pct' => $totalGrades > 0 ? round($good / $totalGrades * 100) : 0, 'color' => '#3b82f6'],
            ['label' => 'TB (5-5.9)', 'pct' => $totalGrades > 0 ? round($average / $totalGrades * 100) : 0, 'color' => '#f97316'],
            ['label' => 'Yếu (<5)', 'pct' => $totalGrades > 0 ? round($weak / $totalGrades * 100) : 0, 'color' => '#ef4444'],
        ];

        // Top students
        $topStudents = User::where('role', 'student')
            ->whereHas('classes', fn($q) => $q->whereIn('class_id', $classIds))
            ->withAvg('grades as avg_score', 'score')
            ->orderByDesc('avg_score')
            ->take(5)
            ->get(['id', 'name'])
            ->map(fn($s) => [
                'id'    => $s->id,
                'name'  => $s->name,
                'avg'   => round($s->avg_score ?? 0, 1),
                'color' => ['#f97316', '#3b82f6', '#22c55e', '#a855f7', '#06b6d4'][0],
            ]);

        // Weekly trend (6 weeks)
        $weeklyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd   = now()->subWeeks($i)->endOfWeek();
            $avg = Grade::where('grader_id', $user->id)
                ->whereBetween('graded_at', [$weekStart, $weekEnd])
                ->avg('score');
            $weeklyTrend[] = [
                'label' => 'T' . ($i + 1),
                'val'   => round($avg ?? 0, 1),
                'color' => '#3b82f6',
            ];
        }

        return view('pages.teacher.analytics', compact(
            'period', 'classCount', 'quizCount', 'studentCount',
            'avgScore', 'totalGraded', 'scoreByClass',
            'distribution', 'topStudents', 'weeklyTrend'
        ));
    }

    public function export(Request $request)
    {
        $user = $request->user();

        $grades = Grade::where('grader_id', $user->id)
            ->with(['student:id,name,email', 'gradable'])
            ->get();

        $csv = "Học sinh,Email,Bài,Ngày chấm,Điểm,Nhận xét\n";

        foreach ($grades as $g) {
            $itemName = $g->gradable?->title ?? 'N/A';
            $csv .= "\"{$g->student->name}\",\"{$g->student->email}\",\"{$itemName}\",{$g->graded_at?->format('d/m/Y')},{$g->score},\"{$g->feedback}\"\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition'=> 'attachment; filename="phan_tich_' . now()->format('Ymd') . '.csv"',
        ]);
    }
}
