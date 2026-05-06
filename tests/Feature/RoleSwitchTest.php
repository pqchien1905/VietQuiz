<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_switches_to_teacher_on_same_account(): void
    {
        $user = User::factory()->create([
            'email' => 'pqchien1905@gmail.com',
            'role' => 'student',
            'can_switch_role' => true,
        ]);

        $this->actingAs($user)
            ->get(\Illuminate\Support\Facades\URL::signedRoute('switch.to.teacher'))
            ->assertRedirect(route('teacher.dashboard'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'pqchien1905@gmail.com',
            'role' => 'teacher',
            'can_switch_role' => true,
            'last_active_role' => 'teacher',
        ]);

        $this->assertSame(1, User::count());
    }

    public function test_register_as_teacher_enables_teacher_screen_without_creating_linked_email(): void
    {
        $user = User::factory()->create([
            'email' => 'pqchien1905@gmail.com',
            'role' => 'student',
            'can_switch_role' => false,
        ]);

        $this->actingAs($user)
            ->get(route('register.as.teacher'))
            ->assertRedirect(route('teacher.dashboard'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'pqchien1905@gmail.com',
            'role' => 'teacher',
            'can_switch_role' => true,
        ]);
        $this->assertSame(1, User::count());
    }

    public function test_teacher_without_dual_role_is_sent_to_student_registration_before_switching(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'can_switch_role' => false,
        ]);

        $this->actingAs($teacher)
            ->get(\Illuminate\Support\Facades\URL::signedRoute('switch.to.student'))
            ->assertRedirect(route('register.as.student', [
                'intended' => route('student.dashboard'),
            ]));

        $this->assertDatabaseHas('users', [
            'id' => $teacher->id,
            'role' => 'teacher',
            'can_switch_role' => false,
        ]);
    }

    public function test_signed_role_switch_rejects_invalid_role(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'can_switch_role' => true,
        ]);

        $this->actingAs($user)
            ->get(\Illuminate\Support\Facades\URL::signedRoute('switch.role', ['role' => 'admin']))
            ->assertNotFound();
    }
}
