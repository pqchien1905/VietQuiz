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
}
