<?php

namespace Tests\Feature\Teacher;

use App\Models\Notification;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherPageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_main_pages_render_successfully(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        Notification::create([
            'user_id' => $teacher->id,
            'type' => 'system',
            'title' => 'Thong bao he thong',
            'body' => 'Noi dung thong bao',
        ]);

        Ticket::create([
            'user_id' => $teacher->id,
            'category' => 'technical',
            'subject' => 'Can ho tro',
            'description' => 'Mo ta yeu cau ho tro',
            'status' => 'open',
            'priority' => 'normal',
        ]);

        $routes = [
            'teacher.dashboard',
            'teacher.classes',
            'teacher.students',
            'teacher.quizzes',
            'teacher.quiz-create',
            'teacher.questions',
            'teacher.assignments',
            'teacher.courses',
            'teacher.grading',
            'teacher.analytics',
            'teacher.notifications',
            'teacher.profile',
            'teacher.settings',
            'teacher.help',
            'teacher.vip',
            'teacher.trash',
        ];

        foreach ($routes as $routeName) {
            $this->actingAs($teacher)
                ->get(route($routeName))
                ->assertOk();
        }
    }
}
