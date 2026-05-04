<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_protected_pages(): void
    {
        $this->get(route('student.dashboard'))
            ->assertRedirect(route('login'));

        $this->get(route('teacher.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_teacher_area(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('teacher.dashboard'))
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_teacher_cannot_access_student_area_without_dual_role(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'can_switch_role' => false]);

        $this->actingAs($teacher)
            ->get(route('student.dashboard'))
            ->assertRedirect(route('teacher.dashboard'));
    }

    public function test_login_redirects_each_role_to_its_dashboard(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'email' => 'teacher@example.test']);

        $this->post(route('login'), [
            'email' => $teacher->email,
            'password' => 'password',
        ])->assertRedirect(route('teacher.dashboard', absolute: false));
    }

    public function test_admin_login_is_blocked_when_admin_panel_is_disabled(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@example.test']);

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }
}
