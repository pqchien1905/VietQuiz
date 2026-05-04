<?php

namespace Tests\Feature\Student;

use App\Models\User;
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
}
