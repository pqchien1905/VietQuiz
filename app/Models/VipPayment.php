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
        'vip_subscription_id',
        'txn_ref',
        'plan',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(VipSubscription::class, 'vip_subscription_id');
    }
}
