<?php

namespace Tests\Feature\Teacher;

use App\Models\Notification;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VipSubscription;
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

    public function test_active_teacher_vip_dropdown_can_open_plan_details_and_upgrade(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        VipSubscription::create([
            'user_id' => $teacher->id,
            'plan' => 'yearly',
            'status' => 'active',
            'started_at' => now()->subMonth(),
            'expires_at' => now()->addYear(),
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('Quản lý / gia hạn gói')
            ->assertSee(route('teacher.vip') . '#vip-plans', false);
    }
}
