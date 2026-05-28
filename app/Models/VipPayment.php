<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VipPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'audience',
        'promotion_id',
        'promotion_code',
        'vip_subscription_id',
        'txn_ref',
        'plan',
        'original_amount',
        'discount_amount',
        'amount',
        'bank_code',
        'status',
        'vnp_transaction_no',
        'vnp_bank_code',
        'vnp_response_code',
        'vnp_transaction_status',
        'paid_at',
        'vnp_payload',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'vnp_payload' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (VipPayment $payment) {
            if (! $payment->audience && $payment->user_id) {
                $payment->audience = User::whereKey($payment->user_id)->value('role') === 'student'
                    ? 'student'
                    : 'teacher';
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(VipSubscription::class, 'vip_subscription_id');
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
