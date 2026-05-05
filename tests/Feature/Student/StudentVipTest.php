<?php

namespace Tests\Feature\Student;

use App\Models\User;
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
}
