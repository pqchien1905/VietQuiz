<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notifications')
            ->whereIn('type', [
                'assignment',
                'assignment_assigned',
                'assignment_due',
                'class_announcement',
                'class_joined',
                'course',
                'course_assigned',
                'grade',
                'grade_published',
                'quiz',
                'quiz_assigned',
                'quiz_result',
            ])
            ->update(['audience_role' => 'student']);

        DB::table('notifications')
            ->whereIn('type', [
                'assignment_submitted',
                'grading',
                'submission',
            ])
            ->update(['audience_role' => 'teacher']);
    }

    public function down(): void
    {
        // Data normalization only; no schema change to roll back.
    }
};
