<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add class_id to courses
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('class_id')->nullable()->after('teacher_id')->constrained('classes')->nullOnDelete();
            $table->string('color', 20)->nullable()->after('cover_image');
            $table->string('icon', 10)->nullable()->after('color');
        });

        // Add class_id to quizzes
        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreignId('class_id')->nullable()->after('course_id')->constrained('classes')->nullOnDelete();
        });

        // Add color/icon to classes
        Schema::table('classes', function (Blueprint $table) {
            $table->string('color', 20)->nullable()->after('description');
            $table->string('icon', 10)->nullable()->after('color');
            $table->string('subject')->nullable()->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_id');
            $table->dropColumn(['color', 'icon']);
        });
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_id');
        });
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn(['color', 'icon', 'subject']);
        });
    }
};
