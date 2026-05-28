<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vip_payments', function (Blueprint $table) {
            $table->foreignId('promotion_id')->nullable()->after('audience')->constrained()->nullOnDelete();
            $table->string('promotion_code')->nullable()->after('promotion_id');
            $table->unsignedInteger('original_amount')->nullable()->after('plan');
            $table->unsignedInteger('discount_amount')->default(0)->after('original_amount');
        });
    }

    public function down(): void
    {
        Schema::table('vip_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
            $table->dropColumn(['promotion_code', 'original_amount', 'discount_amount']);
        });
    }
};
