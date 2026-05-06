<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VipPayment;
use App\Models\VipSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VnpayIpnTest extends TestCase
{
    use RefreshDatabase;

    public function test_vnpay_ipn_success_is_idempotent(): void
    {
        config([
            'services.vnpay.hash_secret' => 'test-vnpay-secret',
        ]);

        $user = User::factory()->create(['role' => 'teacher']);
        $payment = VipPayment::create([
            'user_id' => $user->id,
            'txn_ref' => 'VQVIP-TEST-001',
            'plan' => 'monthly',
            'amount' => 199000,
            'status' => 'pending',
        ]);

        $payload = $this->signedPayload([
            'vnp_Amount' => (string) ($payment->amount * 100),
            'vnp_BankCode' => 'NCB',
            'vnp_ResponseCode' => '00',
            'vnp_TmnCode' => 'TESTMERCHANT',
            'vnp_TransactionNo' => '14123456',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => $payment->txn_ref,
        ]);

        $this->getJson(route('vip.vnpay.ipn', $payload))
            ->assertOk()
            ->assertJson(['RspCode' => '00', 'Message' => 'Confirm Success']);

        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->vip_subscription_id);
        $this->assertSame(1, VipSubscription::where('user_id', $user->id)->count());

        $firstSubscriptionId = $payment->vip_subscription_id;

        $this->getJson(route('vip.vnpay.ipn', $payload))
            ->assertOk()
            ->assertJson(['RspCode' => '02', 'Message' => 'Order already confirmed']);

        $payment->refresh();
        $this->assertSame($firstSubscriptionId, $payment->vip_subscription_id);
        $this->assertSame(1, VipSubscription::where('user_id', $user->id)->count());
    }

    /**
     * @param  array<string, string>  $payload
     * @return array<string, string>
     */
    private function signedPayload(array $payload): array
    {
        ksort($payload);
        $query = http_build_query($payload);
        $payload['vnp_SecureHash'] = hash_hmac('sha512', $query, config('services.vnpay.hash_secret'));

        return $payload;
    }
}
