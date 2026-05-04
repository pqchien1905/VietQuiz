<?php

namespace Tests\Feature\Student;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentProfileViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_profile_page_uses_authenticated_student_data(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Nguyễn Minh An',
            'email' => 'minhan@example.com',
            'phone' => '0901234567',
        ]);

        $this->actingAs($student)
            ->get(route('student.profile'))
            ->assertOk()
            ->assertSee('Nguyễn Minh An')
            ->assertSee('minhan@example.com')
            ->assertSee('0901234567')
            ->assertSee('Chỉnh sửa hồ sơ')
            ->assertDontSee('Giáo viên Demo')
            ->assertDontSee('teacher@demo.com');
    }
}
