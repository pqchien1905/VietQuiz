<?php

namespace Tests\Feature\Student;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTrashTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_restore_deleted_notification_from_trash(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $notification = Notification::create([
            'user_id' => $student->id,
            'type' => 'system',
            'title' => 'Thông báo cần khôi phục',
            'body' => 'Nội dung thông báo',
            'is_read' => false,
        ]);

        $notification->delete();

        $this->actingAs($student)
            ->get(route('student.trash'))
            ->assertOk()
            ->assertSee('Thông báo cần khôi phục');

        $this->actingAs($student)
            ->post(route('student.trash.restore', ['notification', $notification->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'deleted_at' => null,
        ]);
    }

    public function test_student_cannot_force_delete_another_users_deleted_notification(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $notification = Notification::create([
            'user_id' => $otherStudent->id,
            'type' => 'system',
            'title' => 'Thông báo của người khác',
            'body' => 'Không được phép xóa',
            'is_read' => false,
        ]);

        $notification->delete();

        $this->actingAs($student)
            ->delete(route('student.trash.force-delete', ['notification', $notification->id]))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('notifications', [
            'id' => $notification->id,
        ]);
    }
}
