<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('audience', 20)->default('all')->after('description')->index();
        });

        DB::table('promotions')
            ->whereNull('audience')
            ->update(['audience' => 'all']);
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropIndex(['audience']);
            $table->dropColumn('audience');
        });
    }
};
