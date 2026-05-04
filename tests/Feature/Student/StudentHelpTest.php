<?php

namespace Tests\Feature\Student;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentHelpTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_help_page_displays_existing_tickets(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        Ticket::create([
            'user_id' => $student->id,
            'category' => 'quiz',
            'subject' => 'Không mở được bài kiểm tra',
            'description' => 'Trang quiz báo lỗi.',
            'status' => 'open',
            'priority' => 'normal',
        ]);

        $this->actingAs($student)
            ->get(route('student.help'))
            ->assertOk()
            ->assertSee('Không mở được bài kiểm tra')
            ->assertSee('Bài kiểm tra')
            ->assertSee('Mới gửi');
    }

    public function test_student_can_submit_support_ticket(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->post(route('student.help.ticket'), [
                'category' => 'grades',
                'subject' => 'Không thấy điểm bài tập',
                'description' => 'Em đã nộp bài nhưng chưa thấy điểm trong bảng điểm.',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tickets', [
            'user_id' => $student->id,
            'category' => 'grades',
            'subject' => 'Không thấy điểm bài tập',
            'status' => 'open',
            'priority' => 'normal',
        ]);
    }
}
