<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\VipPayment;
use App\Models\VipPlan;
use App\Models\VipSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VipController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $audience = $this->audienceForUser($user);
        $subscription = $user->vipSubscriptionForAudience($audience)->first();
        $latestPayment = $user->vipPaymentsForAudience($audience)->latest()->first();
        $viewPath = $audience === 'teacher' ? 'pages.teacher.vip' : 'pages.student.vip';

        return view($viewPath, [
            'user' => $user,
            'subscription' => $subscription,
            'latestPayment' => $latestPayment,
            'plans' => $this->plansForAudience($audience),
            'ipnUrl' => route('vip.vnpay.ipn'),
        ]);
    }

    public function subscribe(Request $request)
    {
        $audience = $this->audienceForUser($request->user());
        $plans = $this->plansForAudience($audience);
        $planKeys = implode(',', array_keys($plans));

        $validated = $request->validate([
            'plan' => 'required|in:' . $planKeys,
            'bank_code' => 'nullable|in:VNPAYQR,VNBANK,INTCARD,NCB',
            'promotion_code' => ['nullable', 'string', 'max:50'],
        ]);

        $config = config('services.vnpay');
        if (blank($config['tmn_code']) || blank($config['hash_secret']) || blank($config['payment_url'])) {
            return back()->with('error', 'Chưa cấu hình VNPay. Vui lòng kiểm tra VNPAY_TMN_CODE, VNPAY_HASH_SECRET và VNPAY_PAYMENT_URL.');
        }

        $plan = $plans[$validated['plan']];
        $promotion = $this->resolvePromotion($validated['promotion_code'] ?? null, $validated['plan'], $audience);
        if (($validated['promotion_code'] ?? null) && ! $promotion) {
            return back()
                ->withInput()
                ->withErrors(['promotion_code' => 'Mã khuyến mãi không hợp lệ, đã hết hạn hoặc không áp dụng cho gói này.']);
        }

        $originalAmount = $plan['amount'];
        $discountAmount = $promotion ? $this->discountAmount($promotion, $originalAmount) : 0;
        $amount = max(0, $originalAmount - $discountAmount);
        $payment = VipPayment::create([
            'user_id' => $request->user()->id,
            'audience' => $audience,
            'promotion_id' => $promotion?->id,
            'promotion_code' => $promotion?->code,
            'txn_ref' => $this->makeTxnRef($request->user()->id),
            'plan' => $validated['plan'],
            'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount,
            'amount' => $amount,
            'bank_code' => $validated['bank_code'] ?? null,
            'status' => 'pending',
        ]);

        if ($amount === 0) {
            $this->markPaymentPaid($payment, [
                'vnp_ResponseCode' => '00',
                'vnp_TransactionStatus' => '00',
                'promotion_free_checkout' => true,
            ]);

            return redirect()
                ->route($audience === 'teacher' ? 'teacher.vip' : 'student.vip')
                ->with('success', 'Mã khuyến mãi đã giảm 100%. Gói VIP đã được kích hoạt.');
        }

        return redirect()->away($this->buildPaymentUrl($request, $payment, $plan['label']));
    }

    public function vnpayReturn(Request $request)
    {
        $payment = VipPayment::with('user')->where('txn_ref', $request->query('vnp_TxnRef'))->first();
        $returnUser = $request->user() ?: $payment?->user;
        $routeName = ($payment?->audience ?? $this->audienceForUser($returnUser)) === 'teacher' ? 'teacher.vip' : 'student.vip';

        if (!$this->hasValidSignature($request->query())) {
            return redirect()->route($routeName)->with('error', 'VNPay trả về checksum không hợp lệ. Giao dịch chưa được kích hoạt.');
        }

        if (!$payment) {
            return redirect()->route($routeName)->with('error', 'Không tìm thấy giao dịch VIP từ VNPay.');
        }

        if ($this->isSuccessfulVnpayPayment($request->query())) {
            $this->markPaymentPaid($payment, $request->query());

            return redirect()->route($routeName)->with('success', 'Thanh toán VNPay thành công. Tài khoản VietQuiz Pro đã được kích hoạt.');
        }

        $this->markPaymentFailed($payment, $request->query());

        return redirect()->route($routeName)->with('error', 'Thanh toán VNPay chưa thành công hoặc đã bị hủy. Vui lòng thử lại.');
    }

    public function vnpayIpn(Request $request)
    {
        try {
            $payload = $request->query();

            if (!$this->hasValidSignature($payload)) {
                return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
            }

            $payment = VipPayment::where('txn_ref', $payload['vnp_TxnRef'] ?? null)->first();
            if (!$payment) {
                return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
            }

            if ((int) ($payload['vnp_Amount'] ?? 0) !== $payment->amount * 100) {
                return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
            }

            if ($payment->status === 'paid') {
                return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']);
            }

            if ($this->isSuccessfulVnpayPayment($payload)) {
                $this->markPaymentPaid($payment, $payload);
            } else {
                $this->markPaymentFailed($payment, $payload);
            }

            return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['RspCode' => '99', 'Message' => 'Unknown error']);
        }
    }

    public function cancel(Request $request)
    {
        $audience = $this->audienceForUser($request->user());

        VipSubscription::where('user_id', $request->user()->id)
            ->where('audience', $audience)
            ->update(['status' => 'cancelled']);

        $routeName = $audience === 'teacher' ? 'teacher.vip' : 'student.vip';

        return redirect()
            ->route($routeName)
            ->with('success', 'Đã hủy đăng ký. Bạn vẫn sử dụng được đến hết chu kỳ đã thanh toán.');
    }

    private function buildPaymentUrl(Request $request, VipPayment $payment, string $planLabel): string
    {
        $createDate = now('Asia/Ho_Chi_Minh');
        $inputData = [
            'vnp_Version' => config('services.vnpay.version', '2.1.0'),
            'vnp_TmnCode' => config('services.vnpay.tmn_code'),
            'vnp_Amount' => $payment->amount * 100,
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => $createDate->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => $request->ip(),
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => "{$planLabel} VietQuiz {$payment->txn_ref}",
            'vnp_OrderType' => 'other',
            'vnp_ReturnUrl' => route('vip.vnpay.return'),
            'vnp_TxnRef' => $payment->txn_ref,
            'vnp_ExpireDate' => $createDate->copy()->addMinutes(15)->format('YmdHis'),
        ];

        if ($payment->bank_code) {
            $inputData['vnp_BankCode'] = $payment->bank_code;
        }

        ksort($inputData);
        $query = http_build_query($inputData);
        $secureHash = hash_hmac('sha512', $query, config('services.vnpay.hash_secret'));

        return config('services.vnpay.payment_url') . '?' . $query . '&vnp_SecureHash=' . $secureHash;
    }

    private function hasValidSignature(array $payload): bool
    {
        $secureHash = $payload['vnp_SecureHash'] ?? null;
        if (!$secureHash) {
            return false;
        }

        $hashData = Arr::except($payload, ['vnp_SecureHash', 'vnp_SecureHashType']);
        ksort($hashData);
        $query = http_build_query($hashData);
        $expectedHash = hash_hmac('sha512', $query, config('services.vnpay.hash_secret'));

        return hash_equals($expectedHash, $secureHash);
    }

    private function isSuccessfulVnpayPayment(array $payload): bool
    {
        return ($payload['vnp_ResponseCode'] ?? null) === '00'
            && ($payload['vnp_TransactionStatus'] ?? null) === '00';
    }

    private function markPaymentPaid(VipPayment $payment, array $payload): void
    {
        DB::transaction(function () use ($payment, $payload) {
            $payment->refresh();

            if ($payment->status === 'paid') {
                return;
            }

            $expiresAt = match ($payment->plan) {
                'monthly' => now()->addMonth(),
                'yearly' => now()->addYear(),
                'lifetime' => null,
            };

            $audience = $payment->audience ?? $this->audienceForUser($payment->user);
            $subscription = VipSubscription::updateOrCreate(
                [
                    'user_id' => $payment->user_id,
                    'audience' => $audience,
                ],
                [
                    'plan' => $payment->plan,
                    'status' => 'active',
                    'started_at' => now(),
                    'expires_at' => $expiresAt,
                ]
            );

            $payment->update([
                'audience' => $audience,
                'vip_subscription_id' => $subscription->id,
                'status' => 'paid',
                'vnp_transaction_no' => $payload['vnp_TransactionNo'] ?? null,
                'vnp_bank_code' => $payload['vnp_BankCode'] ?? null,
                'vnp_response_code' => $payload['vnp_ResponseCode'] ?? null,
                'vnp_transaction_status' => $payload['vnp_TransactionStatus'] ?? null,
                'paid_at' => now(),
                'vnp_payload' => $payload,
            ]);

            if ($payment->promotion_id) {
                Promotion::whereKey($payment->promotion_id)->increment('used_count');
            }
        });
    }

    private function markPaymentFailed(VipPayment $payment, array $payload): void
    {
        if ($payment->status !== 'pending') {
            return;
        }

        $payment->update([
            'status' => (($payload['vnp_ResponseCode'] ?? null) === '24') ? 'cancelled' : 'failed',
            'vnp_transaction_no' => $payload['vnp_TransactionNo'] ?? null,
            'vnp_bank_code' => $payload['vnp_BankCode'] ?? null,
            'vnp_response_code' => $payload['vnp_ResponseCode'] ?? null,
            'vnp_transaction_status' => $payload['vnp_TransactionStatus'] ?? null,
            'vnp_payload' => $payload,
        ]);
    }

    private function makeTxnRef(int $userId): string
    {
        return 'VQVIP' . $userId . now('Asia/Ho_Chi_Minh')->format('YmdHis') . Str::upper(Str::random(6));
    }

    private function plansForAudience(string $audience): array
    {
        $plans = VipPlan::where('audience', $audience)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn ($plan) => [
                $plan->plan => [
                    'label' => $plan->label,
                    'amount' => (int) $plan->amount,
                ],
            ])
            ->all();

        if ($plans !== []) {
            return $plans;
        }

        return collect(VipPlan::defaults())
            ->where('audience', $audience)
            ->where('status', 'active')
            ->mapWithKeys(fn ($plan) => [
                $plan['plan'] => [
                    'label' => $plan['label'],
                    'amount' => $plan['amount'],
                ],
            ])
            ->all();
    }

    private function audienceForUser($user): string
    {
        return $user?->isStudent() ? 'student' : 'teacher';
    }

    private function resolvePromotion(?string $code, string $plan, string $audience): ?Promotion
    {
        $code = Str::upper(trim((string) $code));
        if ($code === '') {
            return null;
        }

        $promotion = Promotion::query()
            ->where('code', $code)
            ->where(function ($query) use ($plan) {
                $query->where('vip_plan', 'all')
                    ->orWhere('vip_plan', $plan);
            })
            ->where(function ($query) use ($audience) {
                $query->where('audience', 'all')
                    ->orWhere('audience', $audience);
            })
            ->first();

        return $promotion?->isActive() ? $promotion : null;
    }

    private function discountAmount(Promotion $promotion, int $amount): int
    {
        $discount = $promotion->discount_type === 'percentage'
            ? (int) floor($amount * ((float) $promotion->discount_value / 100))
            : (int) $promotion->discount_value;

        return min($amount, max(0, $discount));
    }
}
