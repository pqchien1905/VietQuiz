<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['teacher_id', 'name']);
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreignId('folder_id')
                ->nullable()
                ->after('teacher_id')
                ->constrained('quiz_folders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('folder_id');
        });

        Schema::dropIfExists('quiz_folders');
    }
};
