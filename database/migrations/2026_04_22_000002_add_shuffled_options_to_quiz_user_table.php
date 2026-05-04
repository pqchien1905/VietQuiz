<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_user', function (Blueprint $table) {
            $table->text('shuffled_options')->nullable()->after('is_graded');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_user', function (Blueprint $table) {
            $table->dropColumn('shuffled_options');
        });
    }
};
