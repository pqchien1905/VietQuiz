<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('audience_role', 20)->nullable()->after('user_id')->index();
        });

        DB::table('notifications')
            ->whereNull('notifications.audience_role')
            ->orderBy('id')
            ->chunkById(200, function ($notifications): void {
                $roles = DB::table('users')
                    ->whereIn('id', $notifications->pluck('user_id')->all())
                    ->pluck('role', 'id');

                foreach ($notifications as $notification) {
                    DB::table('notifications')
                        ->where('id', $notification->id)
                        ->update([
                            'audience_role' => $roles[$notification->user_id] ?? 'student',
                        ]);
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['audience_role']);
            $table->dropColumn('audience_role');
        });
    }
};
