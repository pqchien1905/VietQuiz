<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('public_to_all_students')->default(false)->after('assigned_students');
        });

        DB::table('quizzes')
            ->whereNull('class_id')
            ->whereNull('course_id')
            ->where(function ($query) {
                $query->whereNull('assigned_students')
                    ->orWhereJsonLength('assigned_students', 0);
            })
            ->update(['public_to_all_students' => true]);
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('public_to_all_students');
        });
    }
};
