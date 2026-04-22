<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $grades = Grade::where('student_id', $user->id)
            ->orderByDesc('graded_at')
            ->get()
            ->map(function ($g) {
                return [
                    'id'        => $g->id,
                    'score'     => $g->score,
                    'feedback'  => $g->feedback,
                    'graded_at' => $g->graded_at?->toIso8601String(),
                    'item'      => $g->gradable ? [
                        'type' => class_basename($g->gradable_type),
                        'name' => $g->gradable->title ?? 'N/A',
                    ] : null,
                ];
            });

        $avg = $grades->avg('score');

        return response()->json([
            'grades' => $grades,
            'average' => $avg ? round($avg, 1) : null,
        ]);
    }
}
