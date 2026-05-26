<?php

namespace Tests\Feature\Teacher;

use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use App\Models\VipSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeacherAssignmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_assignment_for_own_class_and_course(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Lop 11A',
            'code' => 'OWN111',
        ]);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'name' => 'Lap trinh co ban',
            'status' => 'draft',
        ]);

        $this->actingAs($teacher)
            ->post(route('teacher.assignments.store'), $this->validAssignmentPayload([
                'class_id' => $class->id,
                'course_id' => $course->id,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('assignments', [
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'course_id' => $course->id,
            'title' => 'Bai tap lap trinh',
        ]);
    }

    public function test_teacher_assignment_attachment_preserves_original_filename(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Lop upload',
            'code' => 'UPL111',
        ]);
        $file = UploadedFile::fake()->create('huong-dan-bai-tap.xlsx', 12, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($teacher)
            ->post(route('teacher.assignments.store'), $this->validAssignmentPayload([
                'class_id' => $class->id,
                'type' => 'file',
                'attachment' => $file,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $assignment = Assignment::firstOrFail();

        $this->assertNotNull($assignment->attachment);
        $this->assertSame('huong-dan-bai-tap.xlsx', basename($assignment->attachment));
        Storage::disk('local')->assertExists($assignment->attachment);
    }

    public function test_teacher_cannot_create_assignment_for_another_teachers_class_or_course(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $otherClass = ClassModel::create([
            'teacher_id' => $otherTeacher->id,
            'name' => 'Lop cua giao vien khac',
            'code' => 'OTH111',
        ]);
        $otherCourse = Course::create([
            'teacher_id' => $otherTeacher->id,
            'class_id' => $otherClass->id,
            'name' => 'Khoa cua giao vien khac',
            'status' => 'draft',
        ]);

        $this->actingAs($teacher)
            ->from(route('teacher.assignments'))
            ->post(route('teacher.assignments.store'), $this->validAssignmentPayload([
                'class_id' => $otherClass->id,
                'course_id' => $otherCourse->id,
            ]))
            ->assertRedirect(route('teacher.assignments'))
            ->assertSessionHasErrors(['class_id', 'course_id']);

        $this->assertDatabaseCount('assignments', 0);
    }

    public function test_teacher_cannot_update_assignment_to_another_teachers_class_or_course(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $ownClass = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Lop goc',
            'code' => 'OWN222',
        ]);
        $assignment = Assignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $ownClass->id,
            'title' => 'Bai tap goc',
            'description' => 'Noi dung',
            'type' => 'text',
            'total_points' => 100,
        ]);
        $otherClass = ClassModel::create([
            'teacher_id' => $otherTeacher->id,
            'name' => 'Lop khac',
            'code' => 'OTH222',
        ]);
        $otherCourse = Course::create([
            'teacher_id' => $otherTeacher->id,
            'class_id' => $otherClass->id,
            'name' => 'Khoa khac',
            'status' => 'draft',
        ]);

        $this->actingAs($teacher)
            ->from(route('teacher.assignments'))
            ->put(route('teacher.assignments.update', $assignment), $this->validAssignmentPayload([
                'class_id' => $otherClass->id,
                'course_id' => $otherCourse->id,
            ]))
            ->assertRedirect(route('teacher.assignments'))
            ->assertSessionHasErrors(['class_id', 'course_id']);

        $assignment->refresh();
        $this->assertSame($ownClass->id, $assignment->class_id);
        $this->assertNull($assignment->course_id);
    }

    public function test_vip_teacher_can_generate_ai_grade_suggestion_for_assignment_submission(): void
    {
        config([
            'services.ai_questions.url' => 'https://ai.example.test/v1/chat/completions',
            'services.ai_questions.adapter' => 'chat_completions',
            'services.ai_questions.model' => 'test-model',
            'services.ai_questions.key' => 'test-key',
        ]);

        Http::fake([
            'https://ai.example.test/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'score' => 86,
                                'feedback' => 'Bai lam dung trong tam, can bo sung them vi du kiem thu.',
                                'summary' => 'Dat phan lon yeu cau de bai.',
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        [$teacher, $assignment, $submission] = $this->assignmentSubmissionFixture();
        VipSubscription::create([
            'user_id' => $teacher->id,
            'plan' => 'monthly',
            'status' => 'active',
            'started_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($teacher)
            ->postJson(route('teacher.assignments.grading-submission.ai-grade', [$assignment, $submission]), [
                'rubric' => 'Cham dung yeu cau va cach trinh bay.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('score', 86)
            ->assertJsonPath('feedback', 'Bai lam dung trong tam, can bo sung them vi du kiem thu.');

        Http::assertSent(fn ($request) => $request->url() === 'https://ai.example.test/v1/chat/completions'
            && str_contains((string) data_get($request->data(), 'messages.1.content'), 'Cham dung yeu cau'));

        $this->assertDatabaseCount('grades', 0);
    }

    public function test_free_teacher_cannot_generate_ai_grade_suggestion(): void
    {
        Http::fake();

        [$teacher, $assignment, $submission] = $this->assignmentSubmissionFixture();

        $this->actingAs($teacher)
            ->postJson(route('teacher.assignments.grading-submission.ai-grade', [$assignment, $submission]))
            ->assertForbidden()
            ->assertJsonPath('success', false);

        Http::assertNothingSent();
    }

    private function validAssignmentPayload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'Bai tap lap trinh',
            'description' => 'Viet chuong trinh dau tien',
            'due_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'total_points' => 100,
            'type' => 'text',
        ], $overrides);
    }

    /**
     * @return array{0:User,1:Assignment,2:Submission}
     */
    private function assignmentSubmissionFixture(): array
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Lop AI',
            'code' => 'AI111',
        ]);
        $assignment = Assignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'title' => 'Bai tap AI',
            'description' => 'Giai thich cach xay dung website hoc tap.',
            'type' => 'text',
            'total_points' => 100,
        ]);
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content' => 'Em trinh bay kien truc website gom dang nhap, khoa hoc va nop bai.',
            'submitted_at' => now(),
        ]);

        return [$teacher, $assignment, $submission];
    }
}
