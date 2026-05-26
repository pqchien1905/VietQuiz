<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_user', function (Blueprint $table) {
            $table->string('enrollment_status', 20)->default('approved')->after('joined_at')->index();
            $table->string('enrollment_source', 20)->default('teacher_invite')->after('enrollment_status');
            $table->timestamp('requested_at')->nullable()->after('enrollment_source');
            $table->timestamp('approved_at')->nullable()->after('requested_at');
        });

        DB::table('class_user')->update([
            'enrollment_status' => 'approved',
            'enrollment_source' => 'teacher_invite',
            'approved_at' => DB::raw('joined_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('class_user', function (Blueprint $table) {
            $table->dropIndex(['enrollment_status']);
            $table->dropColumn(['enrollment_status', 'enrollment_source', 'requested_at', 'approved_at']);
        });
    }
};
