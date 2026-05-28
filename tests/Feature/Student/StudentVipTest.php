<?php

namespace Tests\Feature\Student;

use App\Models\User;
use App\Models\Promotion;
use App\Models\VipPayment;
use App\Models\VipSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentVipTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_vip_page_only_shows_ad_free_learning_plan(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('student.vip'))
            ->assertOk()
            ->assertSee('Bỏ quảng cáo khi học')
            ->assertSee('19,000đ')
            ->assertDontSee('Pro năm')
            ->assertDontSee('Pro trọn đời')
            ->assertDontSee('Doanh nghiệp');
    }

    public function test_student_cannot_submit_teacher_vip_plan(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->post(route('student.vip.subscribe'), [
                'plan' => 'yearly',
            ])
            ->assertSessionHasErrors('plan');
    }

    public function test_active_vip_dropdown_links_to_plan_details(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        VipSubscription::create([
            'user_id' => $student->id,
            'plan' => 'monthly',
            'status' => 'active',
            'started_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Quản lý / gia hạn gói')
            ->assertSee(route('student.vip') . '#vip-plan', false);
    }

    public function test_student_vip_cancel_does_not_cancel_teacher_subscription_on_dual_account(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'can_switch_role' => true,
        ]);

        $studentSubscription = VipSubscription::create([
            'user_id' => $user->id,
            'audience' => 'student',
            'plan' => 'monthly',
            'status' => 'active',
            'started_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);

        $teacherSubscription = VipSubscription::create([
            'user_id' => $user->id,
            'audience' => 'teacher',
            'plan' => 'yearly',
            'status' => 'active',
            'started_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
        ]);

        $this->actingAs($user)
            ->post(route('student.vip.cancel'))
            ->assertRedirect(route('student.vip'));

        $this->assertSame('cancelled', $studentSubscription->fresh()->status);
        $this->assertSame('active', $teacherSubscription->fresh()->status);
    }

    public function test_student_vip_promotion_code_can_activate_free_checkout(): void
    {
        config([
            'services.vnpay.tmn_code' => 'TESTMERCHANT',
            'services.vnpay.hash_secret' => 'test-vnpay-secret',
            'services.vnpay.payment_url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
        ]);

        $student = User::factory()->create(['role' => 'student']);
        $promotion = Promotion::create([
            'code' => 'FREEVIP',
            'name' => 'Free student VIP',
            'vip_plan' => 'monthly',
            'discount_type' => 'fixed',
            'discount_value' => 19000,
            'status' => 'active',
        ]);

        $this->actingAs($student)
            ->post(route('student.vip.subscribe'), [
                'plan' => 'monthly',
                'promotion_code' => 'freevip',
            ])
            ->assertRedirect(route('student.vip'));

        $payment = VipPayment::where('user_id', $student->id)->firstOrFail();

        $this->assertSame('paid', $payment->status);
        $this->assertSame('student', $payment->audience);
        $this->assertSame('FREEVIP', $payment->promotion_code);
        $this->assertSame(19000, $payment->original_amount);
        $this->assertSame(19000, $payment->discount_amount);
        $this->assertSame(0, $payment->amount);
        $this->assertSame(1, $promotion->fresh()->used_count);

        $this->assertDatabaseHas('vip_subscriptions', [
            'user_id' => $student->id,
            'audience' => 'student',
            'plan' => 'monthly',
            'status' => 'active',
        ]);
    }

    public function test_student_cannot_use_teacher_vip_promotion_code(): void
    {
        config([
            'services.vnpay.tmn_code' => 'TESTMERCHANT',
            'services.vnpay.hash_secret' => 'test-vnpay-secret',
            'services.vnpay.payment_url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
        ]);

        $student = User::factory()->create(['role' => 'student']);
        Promotion::create([
            'code' => 'TEACHERONLY',
            'name' => 'Teacher only VIP',
            'audience' => 'teacher',
            'vip_plan' => 'monthly',
            'discount_type' => 'percentage',
            'discount_value' => 100,
            'status' => 'active',
        ]);

        $this->actingAs($student)
            ->post(route('student.vip.subscribe'), [
                'plan' => 'monthly',
                'promotion_code' => 'TEACHERONLY',
            ])
            ->assertSessionHasErrors('promotion_code');

        $this->assertDatabaseMissing('vip_payments', [
            'user_id' => $student->id,
            'promotion_code' => 'TEACHERONLY',
        ]);
    }
}
