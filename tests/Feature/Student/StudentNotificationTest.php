<?php

namespace Tests\Feature\Student;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_notifications_page_uses_real_notifications(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        Notification::create([
            'user_id' => $student->id,
            'type' => 'quiz_assigned',
            'title' => 'Có bài kiểm tra mới',
            'body' => 'Bạn có một bài kiểm tra cần hoàn thành.',
            'is_read' => false,
        ]);

        $this->actingAs($student)
            ->get(route('student.notifications'))
            ->assertOk()
            ->assertSee('Có bài kiểm tra mới')
            ->assertSee('Bạn có một bài kiểm tra cần hoàn thành.');
    }

    public function test_student_can_mark_notification_read_and_move_it_to_trash(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $notification = Notification::create([
            'user_id' => $student->id,
            'type' => 'system',
            'title' => 'Thông báo hệ thống',
            'body' => 'Nội dung hệ thống.',
            'is_read' => false,
        ]);

        $this->actingAs($student)
            ->post(route('student.notifications.read', $notification))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);

        $this->actingAs($student)
            ->delete(route('student.notifications.destroy', $notification))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('notifications', [
            'id' => $notification->id,
        ]);

        $this->actingAs($student)
            ->get(route('student.trash'))
            ->assertOk()
            ->assertSee('Thông báo hệ thống');
    }

    public function test_notifications_are_scoped_by_current_role(): void
    {
        $user = User::factory()->create([
            'role' => 'teacher',
            'can_switch_role' => true,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'audience_role' => 'teacher',
            'type' => 'system',
            'title' => 'Teacher scoped notification',
            'body' => 'Only for teacher dashboard.',
            'is_read' => false,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'audience_role' => 'student',
            'type' => 'quiz_assigned',
            'title' => 'Student scoped notification',
            'body' => 'Only for student dashboard.',
            'is_read' => false,
        ]);

        $this->actingAs($user)
            ->get(route('teacher.notifications'))
            ->assertOk()
            ->assertSee('Teacher scoped notification')
            ->assertDontSee('Student scoped notification');

        $user->update(['role' => 'student']);

        $this->actingAs($user->fresh())
            ->get(route('student.notifications'))
            ->assertOk()
            ->assertSee('Student scoped notification')
            ->assertDontSee('Teacher scoped notification');
    }
}
