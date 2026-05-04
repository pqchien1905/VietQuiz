<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_user', function (Blueprint $table) {
            $table->index(['user_id', 'submitted_at']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read']);
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->index(['student_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex(['student_id', 'submitted_at']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_read']);
        });

        Schema::table('quiz_user', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'submitted_at']);
        });
    }
};
