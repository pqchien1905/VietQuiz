<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vip_subscriptions', function (Blueprint $table) {
            $table->string('audience', 20)->nullable()->index();
            $table->index(['user_id', 'audience']);
        });

        Schema::table('vip_payments', function (Blueprint $table) {
            $table->string('audience', 20)->nullable()->index();
            $table->index(['user_id', 'audience']);
        });

        DB::table('vip_payments')
            ->whereNull('audience')
            ->orderBy('id')
            ->chunkById(100, function ($payments) {
                foreach ($payments as $payment) {
                    DB::table('vip_payments')
                        ->where('id', $payment->id)
                        ->update(['audience' => $this->audienceForPayment($payment)]);
                }
            });

        DB::table('vip_subscriptions')
            ->whereNull('audience')
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) {
                foreach ($subscriptions as $subscription) {
                    DB::table('vip_subscriptions')
                        ->where('id', $subscription->id)
                        ->update(['audience' => $this->audienceForSubscription($subscription)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('vip_payments', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'audience']);
            $table->dropIndex(['audience']);
            $table->dropColumn('audience');
        });

        Schema::table('vip_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'audience']);
            $table->dropIndex(['audience']);
            $table->dropColumn('audience');
        });
    }

    private function audienceForUser(int $userId): string
    {
        return DB::table('users')->where('id', $userId)->value('role') === 'student'
            ? 'student'
            : 'teacher';
    }

    private function audienceForPayment(object $payment): string
    {
        $audience = DB::table('vip_plans')
            ->where('plan', $payment->plan)
            ->where('amount', $payment->amount)
            ->value('audience');

        return in_array($audience, ['teacher', 'student'], true)
            ? $audience
            : $this->audienceForUser((int) $payment->user_id);
    }

    private function audienceForSubscription(object $subscription): string
    {
        $audience = DB::table('vip_payments')
            ->where('vip_subscription_id', $subscription->id)
            ->whereNotNull('audience')
            ->where('status', 'paid')
            ->latest('paid_at')
            ->value('audience');

        return in_array($audience, ['teacher', 'student'], true)
            ? $audience
            : $this->audienceForUser((int) $subscription->user_id);
    }
};
