<?php

namespace Tests\Feature\Admin;

use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Notification;
use App\Models\Promotion;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_is_available_at_admin_root(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Đăng nhập quản trị')
            ->assertSee('admin')
            ->assertSee('password');
    }

    public function test_admin_can_login_and_view_users_page(): void
    {
        User::factory()->create([
            'name' => 'Nguyen Van A',
            'email' => 'student@example.test',
            'role' => 'student',
        ]);

        $this->post(route('admin.login'), [
            'username' => 'admin',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard', absolute: false));

        $this->get(route('admin.users'))
            ->assertOk()
            ->assertSee('Nguyen Van A')
            ->assertSee('student@example.test');
    }

    public function test_invalid_admin_login_is_rejected(): void
    {
        $this->from(route('admin.dashboard'))->post(route('admin.login'), [
            'username' => 'admin',
            'password' => 'wrong-password',
        ])
            ->assertRedirect(route('admin.dashboard', absolute: false))
            ->assertSessionHasErrors('username');
    }

    public function test_admin_pages_redirect_to_admin_login_when_session_is_missing(): void
    {
        $this->get(route('admin.users'))
            ->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_normal_authenticated_user_must_still_login_to_admin_area(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Đăng nhập quản trị')
            ->assertDontSee('Quản trị VietQuiz');
    }

    public function test_normal_login_clears_existing_admin_session(): void
    {
        $student = User::factory()->create([
            'email' => 'student@example.test',
            'role' => 'student',
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('login'), [
                'email' => $student->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('student.dashboard', absolute: false));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Đăng nhập quản trị')
            ->assertDontSee('Quản trị VietQuiz');
    }

    public function test_admin_management_pages_render_for_admin_session(): void
    {
        foreach ([
            'admin.dashboard',
            'admin.users',
            'admin.classes',
            'admin.courses',
            'admin.quizzes',
            'admin.questions',
            'admin.assignments',
            'admin.submissions',
            'admin.grades',
            'admin.notifications',
            'admin.tickets',
            'admin.vip',
            'admin.promotions',
            'admin.trash',
            'admin.system',
        ] as $route) {
            $this->withSession(['vietquiz_admin_authenticated' => true])
                ->get(route($route))
                ->assertOk();
        }
    }

    public function test_admin_ticket_response_updates_ticket_and_notifies_user(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'category' => 'technical',
            'subject' => 'Không vào được quiz',
            'description' => 'Trang làm bài không tải.',
            'status' => 'open',
            'priority' => 'normal',
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->patch(route('admin.tickets.respond', $ticket->id), [
                'status' => 'resolved',
                'admin_response' => 'Đã kiểm tra và xử lý.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'resolved',
            'admin_response' => 'Đã kiểm tra và xử lý.',
        ]);

        $this->assertSame(1, Notification::where('user_id', $user->id)->count());
    }

    public function test_admin_detail_and_extended_pages_render_with_system_data(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Teacher Admin']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Student Admin']);

        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Lop Admin',
            'code' => 'ADMIN-CLASS',
            'status' => 'active',
        ]);
        $class->students()->attach($student->id, ['joined_at' => now()]);

        $course = Course::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'name' => 'Khoa Admin',
            'status' => 'published',
        ]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'course_id' => $course->id,
            'title' => 'Quiz Admin',
            'duration_minutes' => 30,
            'total_points' => 10,
            'passing_score' => 5,
            'status' => 'published',
        ]);
        $quiz->students()->attach($student->id, [
            'score' => 8,
            'total_points' => 10,
            'answers' => json_encode([]),
            'started_at' => now(),
            'submitted_at' => now(),
            'is_graded' => true,
        ]);

        $question = Question::create([
            'teacher_id' => $teacher->id,
            'quiz_id' => $quiz->id,
            'content' => 'Cau hoi admin',
            'type' => 'multiple_choice',
            'options' => ['A', 'B'],
            'correct_answer' => 'A',
            'points' => 1,
        ]);

        $assignment = Assignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'course_id' => $course->id,
            'title' => 'Bai tap Admin',
            'type' => 'text',
            'total_points' => 10,
        ]);

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content' => 'Bai nop admin',
            'submitted_at' => now(),
        ]);

        Grade::create([
            'student_id' => $student->id,
            'gradable_type' => Submission::class,
            'gradable_id' => $submission->id,
            'score' => 9,
            'grader_id' => $teacher->id,
            'graded_at' => now(),
        ]);

        Notification::create([
            'user_id' => $student->id,
            'type' => 'admin_test',
            'title' => 'Thong bao Admin',
            'body' => 'Noi dung',
            'is_read' => false,
        ]);

        foreach ([
            route('admin.users.show', $student->id),
            route('admin.classes.show', $class->id),
            route('admin.courses.show', $course->id),
            route('admin.quizzes.show', $quiz->id),
            route('admin.questions'),
            route('admin.assignments.show', $assignment->id),
            route('admin.submissions'),
            route('admin.grades'),
            route('admin.notifications'),
            route('admin.promotions'),
            route('admin.trash'),
        ] as $url) {
            $this->withSession(['vietquiz_admin_authenticated' => true])
                ->get($url)
                ->assertOk();
        }

        $this->assertDatabaseHas('questions', ['id' => $question->id, 'content' => 'Cau hoi admin']);
    }

    public function test_admin_can_create_question_and_broadcast_notification(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.questions.store'), [
                'teacher_id' => $teacher->id,
                'content' => 'Cau hoi moi tu admin',
                'type' => 'short_answer',
                'correct_answer' => 'Dap an',
                'points' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('questions', [
            'teacher_id' => $teacher->id,
            'content' => 'Cau hoi moi tu admin',
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.notifications.store'), [
                'target' => 'student',
                'title' => 'Thong bao chung',
                'body' => 'Gui hoc sinh',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'type' => 'admin_broadcast',
            'title' => 'Thong bao chung',
        ]);
    }

    public function test_admin_can_create_and_update_user_accounts(): void
    {
        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.users.store'), [
                'name' => 'Created Admin User',
                'email' => 'created-user@example.test',
                'role' => 'teacher',
                'phone' => '0900000000',
                'subject' => 'Math',
                'password' => 'password-secret',
                'can_switch_role' => '1',
            ])
            ->assertRedirect(route('admin.users', absolute: false));

        $user = User::where('email', 'created-user@example.test')->firstOrFail();

        $this->assertSame('teacher', $user->role);
        $this->assertTrue((bool) $user->can_switch_role);
        $this->assertNotSame('password-secret', $user->password);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->patch(route('admin.users.update', $user->id), [
                'name' => 'Updated Admin User',
                'email' => 'updated-user@example.test',
                'role' => 'student',
                'phone' => '0911111111',
                'subject' => 'Literature',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Admin User',
            'email' => 'updated-user@example.test',
            'role' => 'student',
            'phone' => '0911111111',
            'subject' => 'Literature',
            'can_switch_role' => false,
        ]);
    }

    public function test_admin_can_create_and_update_classes(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $newTeacher = User::factory()->create(['role' => 'teacher']);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.classes.store'), [
                'teacher_id' => $teacher->id,
                'name' => 'Admin Created Class',
                'code' => 'ADM101',
                'description' => 'Class created from admin panel',
                'subject' => 'Tin học',
                'grade_level' => '10',
                'color' => '#3b82f6',
                'icon' => '10A',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.classes', absolute: false));

        $class = ClassModel::where('code', 'ADM101')->firstOrFail();

        $this->assertSame($teacher->id, $class->teacher_id);
        $this->assertSame('Tin học', $class->subject);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->patch(route('admin.classes.update', $class->id), [
                'teacher_id' => $newTeacher->id,
                'name' => 'Admin Updated Class',
                'code' => 'ADM102',
                'description' => 'Updated from admin panel',
                'subject' => 'Lập trình',
                'grade_level' => '11',
                'color' => '#22c55e',
                'icon' => '11B',
                'status' => 'archived',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('classes', [
            'id' => $class->id,
            'teacher_id' => $newTeacher->id,
            'name' => 'Admin Updated Class',
            'code' => 'ADM102',
            'subject' => 'Lập trình',
            'grade_level' => '11',
            'status' => 'archived',
        ]);
    }

    public function test_admin_can_filter_delete_restore_and_manage_class_students(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Filter Teacher']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Filter Student']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Filterable Admin Class',
            'code' => 'FILTER-CLASS',
            'subject' => 'Physics',
            'grade_level' => '12',
            'status' => 'active',
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.classes', [
                'q' => 'Filterable',
                'teacher_id' => $teacher->id,
                'subject' => 'Physics',
                'status' => 'active',
                'sort' => 'students',
            ]))
            ->assertOk()
            ->assertSee('Filterable Admin Class')
            ->assertSee('FILTER-CLASS');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.classes.students.add', $class->id), [
                'student_id' => $student->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('class_user', [
            'class_id' => $class->id,
            'user_id' => $student->id,
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->delete(route('admin.classes.students.remove', [$class->id, $student->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('class_user', [
            'class_id' => $class->id,
            'user_id' => $student->id,
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->delete(route('admin.classes.delete', $class->id))
            ->assertRedirect();

        $this->assertSoftDeleted('classes', ['id' => $class->id]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.classes', ['state' => 'deleted']))
            ->assertOk()
            ->assertSee('Filterable Admin Class');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.classes.restore', $class->id))
            ->assertRedirect();

        $this->assertDatabaseHas('classes', [
            'id' => $class->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_create_update_filter_restore_and_manage_course_students(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Course Teacher']);
        $newTeacher = User::factory()->create(['role' => 'teacher', 'name' => 'New Course Teacher']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Course Student']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Course Linked Class',
            'code' => 'COURSE-CLASS',
            'subject' => 'Programming',
            'status' => 'active',
        ]);
        $class->students()->attach($student->id, ['joined_at' => now()]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.courses.store'), [
                'teacher_id' => $teacher->id,
                'class_id' => $class->id,
                'name' => 'Admin Created Course',
                'description' => 'Course created from admin',
                'color' => '#3b82f6',
                'icon' => 'JS',
                'status' => 'draft',
            ])
            ->assertRedirect(route('admin.courses', absolute: false));

        $course = Course::where('name', 'Admin Created Course')->firstOrFail();

        $this->assertSame($teacher->id, $course->teacher_id);
        $this->assertSame($class->id, $course->class_id);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->patch(route('admin.courses.update', $course->id), [
                'teacher_id' => $newTeacher->id,
                'class_id' => $class->id,
                'name' => 'Admin Updated Course',
                'description' => 'Updated course from admin',
                'cover_image' => 'covers/course.png',
                'color' => '#22c55e',
                'icon' => 'PHP',
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'teacher_id' => $newTeacher->id,
            'name' => 'Admin Updated Course',
            'status' => 'published',
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.courses', [
                'q' => 'Updated',
                'teacher_id' => $newTeacher->id,
                'class_id' => $class->id,
                'status' => 'published',
                'scope' => 'empty_content',
                'sort' => 'students',
            ]))
            ->assertOk()
            ->assertSee('Admin Updated Course')
            ->assertSee('Chưa có nội dung');

        $unlinkedCourse = Course::create([
            'teacher_id' => $newTeacher->id,
            'name' => 'Unlinked Operations Course',
            'description' => 'Needs class connection',
            'status' => 'draft',
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.courses', ['scope' => 'unlinked']))
            ->assertOk()
            ->assertSee('Unlinked Operations Course')
            ->assertSee('Chưa gắn lớp');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.courses.show', $course->id))
            ->assertOk()
            ->assertSee('Vận hành')
            ->assertSee('Ghi danh học sinh');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.courses.students.add', $course->id), [
                'student_id' => $student->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->delete(route('admin.courses.students.remove', [$course->id, $student->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.courses.students.sync', $course->id))
            ->assertRedirect();

        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->delete(route('admin.courses.delete', $course->id))
            ->assertRedirect();

        $this->assertSoftDeleted('courses', ['id' => $course->id]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.courses', ['state' => 'deleted']))
            ->assertOk()
            ->assertSee('Admin Updated Course');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.courses.restore', $course->id))
            ->assertRedirect();

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_manage_promotions(): void
    {
        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.promotions.store'), [
                'code' => 'WELCOME20',
                'name' => 'Welcome discount',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'usage_limit' => 100,
                'used_count' => 0,
                'status' => 'active',
            ])
            ->assertRedirect();

        $promotion = Promotion::where('code', 'WELCOME20')->firstOrFail();

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->patch(route('admin.promotions.update', $promotion->id), [
                'code' => 'WELCOME25',
                'name' => 'Welcome discount updated',
                'discount_type' => 'percentage',
                'discount_value' => 25,
                'usage_limit' => 100,
                'used_count' => 1,
                'status' => 'inactive',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'code' => 'WELCOME25',
            'status' => 'inactive',
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->delete(route('admin.promotions.delete', $promotion->id))
            ->assertRedirect();

        $this->assertSoftDeleted('promotions', ['id' => $promotion->id]);
    }
}
