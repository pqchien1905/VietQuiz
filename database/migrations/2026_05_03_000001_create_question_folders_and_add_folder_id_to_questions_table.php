<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['teacher_id', 'name']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('folder_id')
                ->nullable()
                ->after('teacher_id')
                ->constrained('question_folders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('folder_id');
        });

        Schema::dropIfExists('question_folders');
    }
};
