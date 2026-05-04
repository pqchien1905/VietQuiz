<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vip_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vip_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('txn_ref')->unique();
            $table->enum('plan', ['monthly', 'yearly', 'lifetime']);
            $table->unsignedInteger('amount');
            $table->string('bank_code')->nullable();
            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled'])->default('pending');
            $table->string('vnp_transaction_no')->nullable();
            $table->string('vnp_bank_code')->nullable();
            $table->string('vnp_response_code')->nullable();
            $table->string('vnp_transaction_status')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('vnp_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_payments');
    }
};
