<?php

namespace Tests\Feature\Student;

use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JoinClassLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_join_from_invite_link_requires_teacher_approval(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Lớp Toán 10A',
            'code' => 'ABC123',
            'status' => 'active',
        ]);

        $response = $this->actingAs($student)->get('/student/join/abc123');

        $response
            ->assertRedirect(route('student.join-class'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('class_user', [
            'class_id' => $class->id,
            'user_id' => $student->id,
            'enrollment_status' => 'pending',
        ]);

        $this->assertFalse($student->fresh()->classes()->where('classes.id', $class->id)->exists());
    }

    public function test_teacher_can_approve_student_join_request(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Lop Toan 10A',
            'code' => 'ABC123',
            'status' => 'active',
        ]);

        $student->classEnrollments()->attach($class->id, [
            'joined_at' => now(),
            'enrollment_status' => 'pending',
            'enrollment_source' => 'link',
            'requested_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->post(route('teacher.classes.join-requests.approve', [$class, $student->id]))
            ->assertRedirect(route('teacher.class-detail', $class));

        $this->assertDatabaseHas('class_user', [
            'class_id' => $class->id,
            'user_id' => $student->id,
            'enrollment_status' => 'approved',
        ]);

        $this->assertTrue($student->fresh()->classes()->where('classes.id', $class->id)->exists());
    }

    public function test_non_student_sees_join_link_explanation_instead_of_dashboard_redirect(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Lớp Toán 10A',
            'code' => 'ABC123',
            'status' => 'active',
        ]);

        $response = $this->actingAs($teacher)->get('/student/join/abc123');

        $response
            ->assertOk()
            ->assertViewIs('pages.student.join-link')
            ->assertSee('Chuyển sang màn học sinh');
    }

    public function test_guest_is_redirected_to_login_before_joining_from_invite_link(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Lớp Toán 10A',
            'code' => 'ABC123',
            'status' => 'active',
        ]);

        $this->get('/student/join/abc123')
            ->assertRedirect(route('login'));
    }
}
