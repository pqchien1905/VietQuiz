<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vip_plans', function (Blueprint $table) {
            $table->id();
            $table->enum('audience', ['teacher', 'student']);
            $table->enum('plan', ['monthly', 'yearly', 'lifetime']);
            $table->string('label');
            $table->unsignedInteger('amount');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['audience', 'plan']);
            $table->index(['audience', 'status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_plans');
    }
};
