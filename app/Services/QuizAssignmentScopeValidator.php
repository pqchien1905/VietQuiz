<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\User;

class QuizAssignmentScopeValidator
{
    /**
     * @param  array<int, int|string>  $studentIds
     * @return array<int, int>
     */
    public function invalidAssignedStudentIds(array $studentIds, ?int $classId = null, ?int $courseId = null): array
    {
        $studentIds = collect($studentIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($studentIds->isEmpty()) {
            return [];
        }

        $validStudentIds = User::whereIn('id', $studentIds)
            ->where('role', 'student')
            ->pluck('id');

        if ($classId !== null || $courseId !== null) {
            $scopedStudentIds = collect();

            if ($classId !== null) {
                $class = ClassModel::find($classId);
                if ($class) {
                    $scopedStudentIds = $scopedStudentIds->merge($class->students()->pluck('users.id'));
                }
            }

            if ($courseId !== null) {
                $course = Course::find($courseId);
                if ($course) {
                    $scopedStudentIds = $scopedStudentIds->merge($course->students()->pluck('users.id'));
                }
            }

            $validStudentIds = $validStudentIds->intersect($scopedStudentIds->unique()->values());
        }

        return $studentIds->diff($validStudentIds)->values()->all();
    }
}
