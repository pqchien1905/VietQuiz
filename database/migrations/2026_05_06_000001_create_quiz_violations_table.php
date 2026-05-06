<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('quiz_attempt_id')->nullable();
            $table->string('event_type', 50);
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['quiz_id', 'user_id']);
            $table->index(['quiz_id', 'event_type']);
            $table->index(['occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_violations');
    }
};
