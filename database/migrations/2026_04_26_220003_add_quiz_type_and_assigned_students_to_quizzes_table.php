<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->enum('quiz_type', ['exam', 'practice'])->default('exam')->after('is_shuffle');
            $table->json('assigned_students')->nullable()->after('quiz_type');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['quiz_type', 'assigned_students']);
        });
    }
};
