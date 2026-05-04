<?php

namespace Tests\Feature\Teacher;

use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use App\Mail\GradePublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeacherGradingSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_grade_own_submitted_quiz_attempt(): void
    {
        Mail::fake();

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Quiz can cham',
            'status' => 'published',
            'quiz_type' => 'exam',
            'total_points' => 10,
        ]);
        $student->quizAttempts()->attach($quiz->id, [
            'score' => 4,
            'total_points' => 10,
            'answers' => json_encode([]),
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now()->subMinutes(5),
            'is_graded' => false,
        ]);

        $this->actingAs($teacher)
            ->post(route('teacher.grading.store'), [
                'gradable_type' => 'quiz',
                'gradable_id' => $quiz->id,
                'student_id' => $student->id,
                'score' => 8,
                'feedback' => 'Tot',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('grades', [
            'student_id' => $student->id,
            'gradable_type' => Quiz::class,
            'gradable_id' => $quiz->id,
            'score' => 8,
            'grader_id' => $teacher->id,
        ]);
        $this->assertDatabaseHas('quiz_user', [
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 8,
            'is_graded' => true,
        ]);
    }

    public function test_teacher_cannot_grade_another_teachers_quiz_attempt(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $quiz = Quiz::create([
            'teacher_id' => $otherTeacher->id,
            'title' => 'Quiz cua nguoi khac',
            'status' => 'published',
            'quiz_type' => 'exam',
            'total_points' => 10,
        ]);
        $student->quizAttempts()->attach($quiz->id, [
            'score' => 5,
            'total_points' => 10,
            'answers' => json_encode([]),
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now()->subMinutes(5),
            'is_graded' => false,
        ]);

        $this->actingAs($teacher)
            ->post(route('teacher.grading.store'), [
                'gradable_type' => 'quiz',
                'gradable_id' => $quiz->id,
                'student_id' => $student->id,
                'score' => 8,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('grades', 0);
        $this->assertDatabaseHas('quiz_user', [
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 5,
            'is_graded' => false,
        ]);
    }

    public function test_teacher_cannot_grade_another_teachers_assignment_submission(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $class = ClassModel::create([
            'teacher_id' => $otherTeacher->id,
            'name' => 'Lop bai tap',
            'code' => 'ASSIGN',
        ]);
        $course = Course::create([
            'teacher_id' => $otherTeacher->id,
            'class_id' => $class->id,
            'name' => 'Khoa bai tap',
            'status' => 'draft',
        ]);
        $assignment = Assignment::create([
            'teacher_id' => $otherTeacher->id,
            'class_id' => $class->id,
            'course_id' => $course->id,
            'title' => 'Bai nop cua nguoi khac',
            'type' => 'text',
            'total_points' => 100,
        ]);
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content' => 'Bai lam',
            'submitted_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->post(route('teacher.grading.store'), [
                'gradable_type' => 'assignment',
                'gradable_id' => $submission->id,
                'student_id' => $student->id,
                'score' => 90,
            ])
            ->assertNotFound();

        $this->assertSame(0, Grade::count());
    }

    public function test_teacher_assignment_grade_queues_email_notification(): void
    {
        Mail::fake();

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'email' => 'student@example.test']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Lop assignment',
            'code' => 'ASM001',
        ]);
        $assignment = Assignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'title' => 'Bai tap can cham',
            'type' => 'text',
            'total_points' => 100,
        ]);
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content' => 'Bai lam',
            'submitted_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->post(route('teacher.grading.store'), [
                'gradable_type' => 'assignment',
                'gradable_id' => $submission->id,
                'student_id' => $student->id,
                'score' => 90,
            ])
            ->assertRedirect();

        Mail::assertQueued(GradePublished::class, function (GradePublished $mail) use ($student) {
            return $mail->hasTo($student->email)
                && $mail->itemType === 'assignment'
                && (int) $mail->score === 90;
        });
    }
}
