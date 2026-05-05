<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VipPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'audience',
        'plan',
        'label',
        'amount',
        'status',
        'sort_order',
    ];

    public static function defaults(): array
    {
        return [
            ['audience' => 'teacher', 'plan' => 'monthly', 'label' => 'Gói Pro tháng', 'amount' => 199000, 'status' => 'active', 'sort_order' => 10],
            ['audience' => 'teacher', 'plan' => 'yearly', 'label' => 'Gói Pro năm', 'amount' => 1668000, 'status' => 'active', 'sort_order' => 20],
            ['audience' => 'teacher', 'plan' => 'lifetime', 'label' => 'Gói Pro trọn đời', 'amount' => 3999000, 'status' => 'active', 'sort_order' => 30],
            ['audience' => 'student', 'plan' => 'monthly', 'label' => 'Gói bỏ qua quảng cáo khi học', 'amount' => 19000, 'status' => 'active', 'sort_order' => 40],
        ];
    }
}
