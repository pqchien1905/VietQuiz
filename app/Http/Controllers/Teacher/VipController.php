<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\VipSubscription;
use Illuminate\Http\Request;

class VipController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $subscription = $user->vipSubscription;

        return view('pages.teacher.vip', compact('user', 'subscription'));
    }

    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'plan'   => 'required|in:monthly,yearly,lifetime',
            'payment_method' => 'nullable|string',
        ]);

        // In production, integrate with payment gateway (VNPay, MoMo, etc.)
        // For now, simulate a successful subscription
        $expiresAt = match ($validated['plan']) {
            'monthly' => now()->addMonth(),
            'yearly'  => now()->addYear(),
            'lifetime' => null,
            default    => now()->addMonth(),
        };

        VipSubscription::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'plan'       => $validated['plan'],
                'status'     => 'active',
                'started_at'  => now(),
                'expires_at'  => $expiresAt,
            ]
        );

        return redirect()
            ->route('teacher.vip')
            ->with('success', 'Nâng cấp VietQuiz Pro thành công!');
    }

    public function cancel(Request $request)
    {
        VipSubscription::where('user_id', $request->user()->id)
            ->update(['status' => 'cancelled']);

        return redirect()
            ->route('teacher.vip')
            ->with('success', 'Đã hủy đăng ký. Bạn vẫn sử dụng được đến hết chu kỳ.');
    }
}
