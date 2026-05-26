<?php

namespace Tests\Feature\Teacher;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeacherQuizManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_quiz_with_questions_for_own_class_and_course(): void
    {
        Mail::fake();

        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Lop 10A1',
            'code' => 'ABC123',
            'subject' => 'Tin hoc',
        ]);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'name' => 'Nhap mon HTML',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($teacher)
            ->post(route('teacher.quizzes.store'), $this->validQuizPayload([
                'class_id' => $class->id,
                'course_id' => $course->id,
                'assignment_type' => 'class',
                'is_published' => '1',
            ]));

        $quiz = Quiz::firstOrFail();
        $response->assertRedirect(route('teacher.quiz-detail', $quiz));

        $this->assertSame($teacher->id, $quiz->teacher_id);
        $this->assertSame($class->id, $quiz->class_id);
        $this->assertSame('published', $quiz->status);
        $this->assertDatabaseCount('questions', 1);
        $this->assertDatabaseHas('questions', [
            'quiz_id' => $quiz->id,
            'teacher_id' => $teacher->id,
            'content' => 'HTML la gi?',
        ]);
    }

    public function test_teacher_cannot_create_quiz_for_another_teachers_class_or_course(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $otherClass = ClassModel::create([
            'teacher_id' => $otherTeacher->id,
            'name' => 'Lop rieng',
            'code' => 'OTHER1',
        ]);
        $otherCourse = Course::create([
            'teacher_id' => $otherTeacher->id,
            'class_id' => $otherClass->id,
            'name' => 'Khoa rieng',
            'status' => 'draft',
        ]);

        $this->actingAs($teacher)
            ->from(route('teacher.quiz-create'))
            ->post(route('teacher.quizzes.store'), $this->validQuizPayload([
                'class_id' => $otherClass->id,
                'course_id' => $otherCourse->id,
            ]))
            ->assertRedirect(route('teacher.quiz-create'))
            ->assertSessionHasErrors(['class_id', 'course_id']);

        $this->assertDatabaseCount('quizzes', 0);
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_teacher_cannot_update_quiz_to_use_another_teachers_class_or_course(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Quiz goc',
            'status' => 'draft',
            'quiz_type' => 'exam',
        ]);
        Question::create([
            'quiz_id' => $quiz->id,
            'teacher_id' => $teacher->id,
            'content' => 'Cau hoi goc',
            'type' => 'short_answer',
            'options' => [],
            'correct_answer' => 'A',
            'points' => 1,
        ]);
        $otherClass = ClassModel::create([
            'teacher_id' => $otherTeacher->id,
            'name' => 'Lop khac',
            'code' => 'OTHER2',
        ]);
        $otherCourse = Course::create([
            'teacher_id' => $otherTeacher->id,
            'class_id' => $otherClass->id,
            'name' => 'Khoa khac',
            'status' => 'draft',
        ]);

        $this->actingAs($teacher)
            ->from(route('teacher.quizzes.edit', $quiz))
            ->put(route('teacher.quizzes.update', $quiz), $this->validQuizPayload([
                'class_id' => $otherClass->id,
                'course_id' => $otherCourse->id,
            ]))
            ->assertRedirect(route('teacher.quizzes.edit', $quiz))
            ->assertSessionHasErrors(['class_id', 'course_id']);

        $quiz->refresh();
        $this->assertNull($quiz->class_id);
        $this->assertNull($quiz->course_id);
    }

    public function test_teacher_can_assign_specific_students_within_selected_class(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Scoped Class',
            'code' => 'SCOPE1',
        ]);
        $class->students()->attach($student->id, ['joined_at' => now()]);

        $response = $this->actingAs($teacher)
            ->post(route('teacher.quizzes.store'), $this->validQuizPayload([
                'assignment_type' => 'specific',
                'class_id' => $class->id,
                'assigned_students' => [$student->id],
            ]));

        $quiz = Quiz::firstOrFail();
        $response->assertRedirect(route('teacher.quiz-detail', $quiz));
        $this->assertSame($class->id, $quiz->class_id);
        $this->assertSame([$student->id], $quiz->assigned_students);
    }

    public function test_teacher_cannot_assign_specific_students_outside_selected_class_on_update(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $insideStudent = User::factory()->create(['role' => 'student']);
        $outsideStudent = User::factory()->create(['role' => 'student']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Scoped Update Class',
            'code' => 'SCOPE2',
        ]);
        $class->students()->attach($insideStudent->id, ['joined_at' => now()]);

        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'assigned_students' => [$insideStudent->id],
            'title' => 'Scoped quiz',
            'status' => 'draft',
            'quiz_type' => 'exam',
        ]);
        Question::create([
            'quiz_id' => $quiz->id,
            'teacher_id' => $teacher->id,
            'content' => 'Cau hoi goc',
            'type' => 'short_answer',
            'options' => [],
            'correct_answer' => 'A',
            'points' => 1,
        ]);

        $this->actingAs($teacher)
            ->from(route('teacher.quizzes.edit', $quiz))
            ->put(route('teacher.quizzes.update', $quiz), $this->validQuizPayload([
                'assignment_type' => 'specific',
                'class_id' => $class->id,
                'assigned_students' => [$outsideStudent->id],
            ]))
            ->assertRedirect(route('teacher.quizzes.edit', $quiz))
            ->assertSessionHasErrors('assigned_students');

        $quiz->refresh();
        $this->assertSame([$insideStudent->id], $quiz->assigned_students);
    }

    public function test_teacher_can_publish_unpublish_and_delete_only_own_quiz(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Own quiz',
            'status' => 'draft',
            'quiz_type' => 'exam',
            'public_to_all_students' => true,
        ]);
        $otherQuiz = Quiz::create([
            'teacher_id' => $otherTeacher->id,
            'title' => 'Other quiz',
            'status' => 'draft',
            'quiz_type' => 'exam',
        ]);

        $this->actingAs($teacher)
            ->post(route('teacher.quizzes.publish', $quiz))
            ->assertRedirect();
        $this->assertSame('published', $quiz->fresh()->status);

        $this->actingAs($teacher)
            ->post(route('teacher.quizzes.unpublish', $quiz))
            ->assertRedirect();
        $this->assertSame('draft', $quiz->fresh()->status);

        $this->actingAs($teacher)
            ->delete(route('teacher.quizzes.destroy', $otherQuiz))
            ->assertForbidden();
        $this->assertNotSoftDeleted($otherQuiz);

        $this->actingAs($teacher)
            ->delete(route('teacher.quizzes.destroy', $quiz))
            ->assertRedirect(route('teacher.quizzes'));
        $this->assertSoftDeleted($quiz);
    }

    public function test_teacher_cannot_publish_quiz_without_assignment_scope(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'No scope quiz',
            'status' => 'draft',
            'quiz_type' => 'exam',
        ]);

        $this->actingAs($teacher)
            ->post(route('teacher.quizzes.publish', $quiz))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('draft', $quiz->fresh()->status);
    }

    public function test_non_vip_teacher_cannot_call_ai_question_generation_endpoint(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)
            ->postJson(route('teacher.quizzes.generate-ai-questions'), [
                'topic' => 'HTML co ban',
                'type' => 'multiple_choice',
                'count' => 5,
                'difficulty' => 'easy',
            ])
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_teacher_can_configure_multiple_quiz_attempts(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $response = $this->actingAs($teacher)
            ->post(route('teacher.quizzes.store'), $this->validQuizPayload([
                'max_attempts' => 2,
            ]));

        $quiz = Quiz::firstOrFail();
        $response->assertRedirect(route('teacher.quiz-detail', $quiz));
        $this->assertSame(2, (int) $quiz->max_attempts);
    }

    private function validQuizPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'title' => 'Kiem tra HTML',
            'description' => 'Bai kiem tra co ban',
            'assignment_type' => 'everyone',
            'time_limit' => 30,
            'max_attempts' => 1,
            'passing_score' => 50,
            'quiz_type' => 'exam',
            'questions' => [
                [
                    'content' => 'HTML la gi?',
                    'type' => 'multiple_choice',
                    'options' => ['Ngon ngu danh dau', 'Co so du lieu', 'He dieu hanh'],
                    'correct_answer' => '0',
                    'points' => 1,
                ],
            ],
        ], $overrides);
    }
}
