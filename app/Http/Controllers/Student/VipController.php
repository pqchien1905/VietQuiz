<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\VipSubscription;
use Illuminate\Http\Request;

class VipController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $subscription = $user->vipSubscription;

        return view('pages.student.vip', compact('user', 'subscription'));
    }

    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'plan' => 'required|in:monthly,yearly,lifetime',
        ]);

        $expiresAt = match ($validated['plan']) {
            'monthly'  => now()->addMonth(),
            'yearly'   => now()->addYear(),
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
            ->route('student.vip')
            ->with('success', 'Nâng cấp VietQuiz Pro thành công!');
    }
}
