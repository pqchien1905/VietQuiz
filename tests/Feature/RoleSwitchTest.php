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
}
