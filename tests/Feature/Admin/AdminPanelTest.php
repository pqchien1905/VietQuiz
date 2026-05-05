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
use App\Models\VipPayment;
use App\Models\VipPlan;
use App\Models\VipSubscription;
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
            'admin.analytics',
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
        ] as $route) {
            $this->withSession(['vietquiz_admin_authenticated' => true])
                ->get(route($route))
                ->assertOk();
        }

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.analytics.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee('admin-analytics-print-pdf', false);
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
                'status' => 'in_progress',
                'priority' => 'vip',
                'category' => 'quiz',
                'admin_response' => 'Đã kiểm tra và xử lý.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress',
            'priority' => 'vip',
            'category' => 'quiz',
            'admin_response' => 'Đã kiểm tra và xử lý.',
        ]);

        $notification = Notification::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('support_ticket', $notification->type);
        $this->assertSame($ticket->id, $notification->data['ticket_id']);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.tickets', [
                'q' => 'quiz',
                'status' => 'in_progress',
                'category' => 'quiz',
                'priority' => 'vip',
                'role' => 'student',
                'scope' => 'answered',
                'sort' => 'priority',
            ]))
            ->assertOk()
            ->assertSee('Không vào được quiz')
            ->assertSee('Ưu tiên VIP')
            ->assertSee('Hàng đợi hỗ trợ');
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
            ->post(route('admin.questions.folders.store'), [
                'teacher_id' => $teacher->id,
                'name' => 'Admin Question Folder',
            ])
            ->assertRedirect();

        $folder = \App\Models\QuestionFolder::where('name', 'Admin Question Folder')->firstOrFail();

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.questions.store'), [
                'teacher_id' => $teacher->id,
                'folder_id' => $folder->id,
                'content' => 'Cau hoi moi tu admin',
                'type' => 'multiple_choice',
                'options' => ['Dap an', 'Lua chon sai'],
                'correct_answer' => 'Dap an',
                'points' => 2,
                'explanation' => 'Giai thich admin',
            ])
            ->assertRedirect();

        $question = Question::where('content', 'Cau hoi moi tu admin')->firstOrFail();

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'teacher_id' => $teacher->id,
            'folder_id' => $folder->id,
            'content' => 'Cau hoi moi tu admin',
            'type' => 'multiple_choice',
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.questions', [
                'q' => 'admin',
                'teacher_id' => $teacher->id,
                'folder_id' => $folder->id,
                'type' => 'multiple_choice',
                'scope' => 'bank',
                'sort' => 'points',
            ]))
            ->assertOk()
            ->assertSee('Cau hoi moi tu admin')
            ->assertSee('Admin Question Folder');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->patch(route('admin.questions.update', $question->id), [
                'teacher_id' => $teacher->id,
                'folder_id' => $folder->id,
                'content' => 'Cau hoi admin da sua',
                'type' => 'short_answer',
                'correct_answer' => 'Dap an moi',
                'points' => 3,
                'explanation' => 'Giai thich moi',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'content' => 'Cau hoi admin da sua',
            'type' => 'short_answer',
            'points' => 3,
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.notifications.store'), [
                'target' => 'student',
                'type' => 'system',
                'title' => 'Thong bao chung',
                'body' => 'Gui hoc sinh',
                'url' => route('student.dashboard'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'type' => 'system',
            'title' => 'Thong bao chung',
        ]);
        $notification = Notification::where('user_id', $student->id)->where('title', 'Thong bao chung')->firstOrFail();
        $this->assertSame('student', $notification->data['target']);
        $this->assertSame(route('student.dashboard'), $notification->data['url']);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.notifications', [
                'q' => 'Thong bao',
                'user_id' => $student->id,
                'role' => 'student',
                'type' => 'system',
                'state' => 'unread',
                'scope' => 'with_url',
                'sort' => 'recipient',
            ]))
            ->assertOk()
            ->assertSee('Thong bao chung')
            ->assertSee('Gửi thông báo');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->patch(route('admin.notifications.read-state', $notification->id), [
                'is_read' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->delete(route('admin.notifications.delete', $notification->id))
            ->assertRedirect();

        $this->assertSoftDeleted('notifications', ['id' => $notification->id]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.notifications', ['state' => 'deleted']))
            ->assertOk()
            ->assertSee('Thong bao chung');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.notifications.restore', $notification->id))
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'deleted_at' => null,
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->delete(route('admin.questions.delete', $question->id))
            ->assertRedirect();

        $this->assertSoftDeleted('questions', ['id' => $question->id]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.questions.restore', $question->id))
            ->assertRedirect();

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'deleted_at' => null,
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

    public function test_admin_can_create_update_filter_reset_and_restore_quizzes(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Quiz Teacher']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Quiz Student']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Quiz Linked Class',
            'code' => 'QUIZ-CLASS',
            'status' => 'active',
        ]);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'name' => 'Quiz Linked Course',
            'status' => 'published',
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.quizzes.store'), [
                'teacher_id' => $teacher->id,
                'class_id' => $class->id,
                'course_id' => $course->id,
                'title' => 'Admin Created Quiz',
                'description' => 'Quiz created by admin',
                'duration_minutes' => 45,
                'total_points' => 20,
                'passing_score' => 60,
                'max_attempts' => 1,
                'quiz_type' => 'exam',
                'status' => 'draft',
                'start_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'shuffle_questions' => 1,
                'shuffle_answers' => 1,
                'show_result' => 1,
                'anti_cheat_enabled' => 1,
            ])
            ->assertRedirect(route('admin.quizzes', absolute: false));

        $quiz = Quiz::where('title', 'Admin Created Quiz')->firstOrFail();

        $this->assertSame($teacher->id, $quiz->teacher_id);
        $this->assertSame($class->id, $quiz->class_id);
        $this->assertSame($course->id, $quiz->course_id);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->patch(route('admin.quizzes.update', $quiz->id), [
                'teacher_id' => $teacher->id,
                'class_id' => null,
                'course_id' => $course->id,
                'title' => 'Admin Updated Quiz',
                'description' => 'Updated admin quiz',
                'duration_minutes' => 50,
                'total_points' => 30,
                'passing_score' => 70,
                'max_attempts' => 1,
                'quiz_type' => 'practice',
                'status' => 'published',
                'show_result' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'title' => 'Admin Updated Quiz',
            'class_id' => null,
            'status' => 'published',
            'anti_cheat_enabled' => false,
        ]);

        $question = Question::create([
            'teacher_id' => $teacher->id,
            'quiz_id' => $quiz->id,
            'content' => 'Admin quiz question',
            'type' => 'short_answer',
            'correct_answer' => 'Answer',
            'points' => 5,
        ]);

        $quiz->students()->attach($student->id, [
            'score' => 24,
            'total_points' => 30,
            'answers' => json_encode([]),
            'started_at' => now(),
            'submitted_at' => now(),
            'is_graded' => false,
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.quizzes', [
                'q' => 'Updated',
                'teacher_id' => $teacher->id,
                'course_id' => $course->id,
                'quiz_type' => 'practice',
                'status' => 'published',
                'scope' => 'ungraded',
                'sort' => 'attempts',
            ]))
            ->assertOk()
            ->assertSee('Admin Updated Quiz')
            ->assertSee('Chờ chấm');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.quizzes.show', $quiz->id))
            ->assertOk()
            ->assertSee('Vận hành')
            ->assertSee('Admin quiz question')
            ->assertSee('Quiz Student');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->delete(route('admin.quizzes.attempts.reset', [$quiz->id, $student->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('quiz_user', [
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->delete(route('admin.quizzes.delete', $quiz->id))
            ->assertRedirect();

        $this->assertSoftDeleted('quizzes', ['id' => $quiz->id]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.quizzes', ['state' => 'deleted']))
            ->assertOk()
            ->assertSee('Admin Updated Quiz');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.quizzes.restore', $quiz->id))
            ->assertRedirect();

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('questions', ['id' => $question->id]);
    }

    public function test_admin_can_create_update_filter_and_restore_assignments(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Assignment Teacher']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Assignment Student']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Assignment Class',
            'code' => 'ASSIGN-CLASS',
            'status' => 'active',
        ]);
        $class->students()->attach($student->id, ['joined_at' => now()]);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'name' => 'Assignment Course',
            'status' => 'published',
        ]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.assignments.store'), [
                'teacher_id' => $teacher->id,
                'class_id' => $class->id,
                'course_id' => $course->id,
                'title' => 'Admin Created Assignment',
                'description' => 'Assignment from admin',
                'type' => 'text',
                'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'total_points' => 20,
            ])
            ->assertRedirect(route('admin.assignments', absolute: false));

        $assignment = Assignment::where('title', 'Admin Created Assignment')->firstOrFail();

        $this->assertSame($teacher->id, $assignment->teacher_id);
        $this->assertSame($class->id, $assignment->class_id);
        $this->assertSame($course->id, $assignment->course_id);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->patch(route('admin.assignments.update', $assignment->id), [
                'teacher_id' => $teacher->id,
                'class_id' => $class->id,
                'course_id' => $course->id,
                'title' => 'Admin Updated Assignment',
                'description' => 'Updated assignment from admin',
                'attachment' => 'assignments/admin.pdf',
                'type' => 'file',
                'due_at' => now()->subDay()->format('Y-m-d H:i:s'),
                'total_points' => 30,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'title' => 'Admin Updated Assignment',
            'type' => 'file',
            'total_points' => 30,
        ]);

        Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content' => 'Submitted homework',
            'submitted_at' => now(),
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.assignments', [
                'q' => 'Updated',
                'teacher_id' => $teacher->id,
                'class_id' => $class->id,
                'course_id' => $course->id,
                'type' => 'file',
                'scope' => 'grading',
                'sort' => 'submissions',
            ]))
            ->assertOk()
            ->assertSee('Admin Updated Assignment')
            ->assertSee('Chờ chấm');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.assignments.show', $assignment->id))
            ->assertOk()
            ->assertSee('Vận hành')
            ->assertSee('Assignment Student')
            ->assertSee('Submitted homework');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->delete(route('admin.assignments.delete', $assignment->id))
            ->assertRedirect();

        $this->assertSoftDeleted('assignments', ['id' => $assignment->id]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.assignments', ['state' => 'deleted']))
            ->assertOk()
            ->assertSee('Admin Updated Assignment');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.assignments.restore', $assignment->id))
            ->assertRedirect();

        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_filter_grade_and_delete_submissions(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Submission Teacher']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Submission Student']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Submission Class',
            'code' => 'SUB-CLASS',
            'status' => 'active',
        ]);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'name' => 'Submission Course',
            'status' => 'published',
        ]);
        $assignment = Assignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'course_id' => $course->id,
            'title' => 'Admin Submission Assignment',
            'type' => 'text',
            'due_at' => now()->subDay(),
            'total_points' => 25,
        ]);
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content' => 'Late submission content',
            'attachment' => 'submissions/work.pdf',
            'submitted_at' => now(),
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.submissions', [
                'q' => 'Late',
                'student_id' => $student->id,
                'assignment_id' => $assignment->id,
                'teacher_id' => $teacher->id,
                'class_id' => $class->id,
                'course_id' => $course->id,
                'status' => 'ungraded',
                'scope' => 'late',
            ]))
            ->assertOk()
            ->assertSee('Submission Student')
            ->assertSee('Late submission content')
            ->assertSee('Nộp trễ');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.submissions.grade', $submission->id), [
                'score' => 21,
                'feedback' => 'Good admin feedback',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('grades', [
            'student_id' => $student->id,
            'gradable_type' => Submission::class,
            'gradable_id' => $submission->id,
            'score' => 21,
            'feedback' => 'Good admin feedback',
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.submissions', ['status' => 'graded']))
            ->assertOk()
            ->assertSee('Đã chấm')
            ->assertSee('21/25');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->delete(route('admin.submissions.delete', $submission->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('submissions', ['id' => $submission->id]);
        $this->assertDatabaseMissing('grades', [
            'gradable_type' => Submission::class,
            'gradable_id' => $submission->id,
        ]);
    }

    public function test_admin_can_filter_update_and_delete_grades(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Grade Teacher']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Grade Student']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Grade Class',
            'code' => 'GRADE-CLASS',
            'status' => 'active',
        ]);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'name' => 'Grade Course',
            'status' => 'published',
        ]);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'course_id' => $course->id,
            'title' => 'Admin Grade Quiz',
            'duration_minutes' => 30,
            'total_points' => 100,
            'passing_score' => 50,
            'status' => 'published',
        ]);
        $quiz->students()->attach($student->id, [
            'score' => 72,
            'total_points' => 100,
            'answers' => json_encode([]),
            'started_at' => now(),
            'submitted_at' => now(),
            'is_graded' => true,
        ]);

        $grade = Grade::create([
            'student_id' => $student->id,
            'gradable_type' => Quiz::class,
            'gradable_id' => $quiz->id,
            'score' => 72,
            'feedback' => null,
            'grader_id' => $teacher->id,
            'graded_at' => now()->subHour(),
        ]);

        $assignment = Assignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'course_id' => $course->id,
            'title' => 'Admin Grade Assignment',
            'type' => 'text',
            'total_points' => 25,
        ]);
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content' => 'Grade assignment submission',
            'submitted_at' => now(),
        ]);
        Grade::create([
            'student_id' => $student->id,
            'gradable_type' => Submission::class,
            'gradable_id' => $submission->id,
            'score' => 20,
            'feedback' => 'Assignment feedback',
            'grader_id' => $teacher->id,
            'graded_at' => now(),
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.grades', [
                'q' => 'Admin Grade Quiz',
                'student_id' => $student->id,
                'grader_id' => $teacher->id,
                'type' => 'quiz',
                'quality' => 'missing_feedback',
                'band' => 'pass',
                'sort' => 'score_desc',
            ]))
            ->assertOk()
            ->assertSee('Grade Student')
            ->assertSee('Admin Grade Quiz')
            ->assertDontSee('Admin Grade Assignment');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.grades', ['type' => 'assignment']))
            ->assertOk()
            ->assertSee('Admin Grade Assignment')
            ->assertSee('20/25');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->patch(route('admin.grades.update', $grade->id), [
                'score' => 88,
                'feedback' => 'Improved admin feedback',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('grades', [
            'id' => $grade->id,
            'score' => 88,
            'feedback' => 'Improved admin feedback',
        ]);
        $this->assertDatabaseHas('quiz_user', [
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 88,
            'is_graded' => true,
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->delete(route('admin.grades.delete', $grade->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('grades', ['id' => $grade->id]);
        $this->assertDatabaseHas('quiz_user', [
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'is_graded' => false,
        ]);
    }

    public function test_admin_can_manage_vip_subscriptions_and_reconcile_payments(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'VIP Teacher']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'VIP Student']);

        $teacherMonthlyPlan = VipPlan::create([
            'audience' => 'teacher',
            'plan' => 'monthly',
            'label' => 'Teacher Monthly',
            'amount' => 199000,
            'status' => 'active',
            'sort_order' => 10,
        ]);

        $subscription = VipSubscription::create([
            'user_id' => $teacher->id,
            'plan' => 'monthly',
            'status' => 'active',
            'started_at' => now()->subDays(20),
            'expires_at' => now()->addDays(3),
        ]);

        $pendingPayment = VipPayment::create([
            'user_id' => $student->id,
            'txn_ref' => 'VQVIP-STUDENT-001',
            'plan' => 'yearly',
            'amount' => 1668000,
            'bank_code' => 'VNBANK',
            'status' => 'pending',
        ]);

        VipPayment::create([
            'user_id' => $teacher->id,
            'vip_subscription_id' => $subscription->id,
            'txn_ref' => 'VQVIP-TEACHER-PAID',
            'plan' => 'monthly',
            'amount' => 199000,
            'status' => 'paid',
            'paid_at' => now()->subDay(),
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.vip', [
                'sub_q' => 'VIP Teacher',
                'sub_role' => 'teacher',
                'sub_plan' => 'monthly',
                'sub_status' => 'active',
                'sub_scope' => 'expiring',
                'sub_sort' => 'expires',
                'pay_q' => 'VQVIP-STUDENT',
                'pay_role' => 'student',
                'pay_plan' => 'yearly',
                'pay_status' => 'pending',
                'pay_sort' => 'amount',
            ]))
            ->assertOk()
            ->assertSee('VIP Teacher')
            ->assertSee('VQVIP-STUDENT-001')
            ->assertSee('Cấp VIP thủ công')
            ->assertSee('Giá gói VIP')
            ->assertSee('Khuyến mãi cho VIP');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->patch(route('admin.vip.plans.update', $teacherMonthlyPlan->id), [
                'label' => 'Teacher Monthly Updated',
                'amount' => 249000,
                'status' => 'active',
                'sort_order' => 10,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('vip_plans', [
            'id' => $teacherMonthlyPlan->id,
            'label' => 'Teacher Monthly Updated',
            'amount' => 249000,
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.promotions.store'), [
                'code' => 'VIP20',
                'name' => 'VIP discount',
                'vip_plan' => 'yearly',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'usage_limit' => 50,
                'used_count' => 0,
                'status' => 'active',
            ])
            ->assertRedirect();

        $promotion = Promotion::where('code', 'VIP20')->firstOrFail();

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->patch(route('admin.promotions.update', $promotion->id), [
                'code' => 'VIP25',
                'name' => 'VIP discount updated',
                'vip_plan' => 'all',
                'discount_type' => 'fixed',
                'discount_value' => 50000,
                'usage_limit' => 80,
                'used_count' => 1,
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'code' => 'VIP25',
            'vip_plan' => 'all',
            'discount_type' => 'fixed',
            'discount_value' => 50000,
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.vip.subscriptions.store'), [
                'user_id' => $student->id,
                'plan' => 'monthly',
                'status' => 'active',
                'started_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('vip_subscriptions', [
            'user_id' => $student->id,
            'plan' => 'monthly',
            'status' => 'active',
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->patch(route('admin.vip.subscriptions.update', $subscription->id), [
                'plan' => 'lifetime',
                'status' => 'active',
                'started_at' => now()->subDay()->format('Y-m-d H:i:s'),
                'expires_at' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('vip_subscriptions', [
            'id' => $subscription->id,
            'plan' => 'lifetime',
            'status' => 'active',
            'expires_at' => null,
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->patch(route('admin.vip.payments.update', $pendingPayment->id), [
                'status' => 'paid',
            ])
            ->assertRedirect();

        $pendingPayment->refresh();
        $this->assertSame('paid', $pendingPayment->status);
        $this->assertNotNull($pendingPayment->vip_subscription_id);
        $this->assertDatabaseHas('vip_subscriptions', [
            'user_id' => $student->id,
            'plan' => 'yearly',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_manage_promotions(): void
    {
        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.promotions.store'), [
                'code' => 'WELCOME20',
                'name' => 'Welcome discount',
                'vip_plan' => 'monthly',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'usage_limit' => 100,
                'used_count' => 0,
                'starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'status' => 'active',
            ])
            ->assertRedirect();

        $promotion = Promotion::where('code', 'WELCOME20')->firstOrFail();

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->patch(route('admin.promotions.update', $promotion->id), [
                'code' => 'WELCOME25',
                'name' => 'Welcome discount updated',
                'vip_plan' => 'all',
                'discount_type' => 'percentage',
                'discount_value' => 25,
                'usage_limit' => 100,
                'used_count' => 1,
                'starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'code' => 'WELCOME25',
            'vip_plan' => 'all',
            'discount_value' => 25,
            'status' => 'active',
        ]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.promotions', [
                'q' => 'WELCOME25',
                'status' => 'active',
                'discount_type' => 'percentage',
                'vip_plan' => 'all',
                'scope' => 'running',
                'state' => 'active',
                'sort' => 'ending',
            ]))
            ->assertOk()
            ->assertSee('WELCOME25')
            ->assertSee('Welcome discount updated')
            ->assertSee('Tạo khuyến mãi');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->delete(route('admin.promotions.delete', $promotion->id))
            ->assertRedirect();

        $this->assertSoftDeleted('promotions', ['id' => $promotion->id]);

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->get(route('admin.promotions', ['state' => 'deleted']))
            ->assertOk()
            ->assertSee('WELCOME25');

        $this->withSession(['vietquiz_admin_authenticated' => true])
            ->post(route('admin.promotions.restore', $promotion->id))
            ->assertRedirect();

        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'deleted_at' => null,
        ]);
    }
}
