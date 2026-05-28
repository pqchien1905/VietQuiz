<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_user', function (Blueprint $table) {
            $table->integer('best_score')->nullable()->after('total_points');
            $table->integer('best_total_points')->nullable()->after('best_score');
        });

        DB::table('quiz_user')
            ->whereNotNull('score')
            ->update([
                'best_score' => DB::raw('score'),
                'best_total_points' => DB::raw('total_points'),
            ]);
    }

    public function down(): void
    {
        Schema::table('quiz_user', function (Blueprint $table) {
            $table->dropColumn(['best_score', 'best_total_points']);
        });
    }
};
