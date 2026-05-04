<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->integer('max_attempts')->nullable()->default(1)->after('passing_score');
            $table->boolean('is_shuffle')->default(false)->after('show_result');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['max_attempts', 'is_shuffle']);
        });
    }
};
