<?php

namespace Tests\Feature\Student;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizTakeTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_take_page_uses_full_screen_exam_layout_without_dashboard_chrome(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Full screen quiz',
            'status' => 'published',
            'quiz_type' => 'exam',
            'anti_cheat_enabled' => false,
            'max_attempts' => 1,
        ]);

        Question::create([
            'quiz_id' => $quiz->id,
            'teacher_id' => $teacher->id,
            'content' => 'First question',
            'type' => 'short_answer',
            'options' => [],
            'correct_answer' => 'answer',
            'points' => 1,
        ]);

        $this->actingAs($student)
            ->get(route('student.quiz-take', $quiz))
            ->assertOk()
            ->assertSee('quiz-shell')
            ->assertSee('showExitModal')
            ->assertDontSee('main-sidebar')
            ->assertDontSee('main-header');
    }

    public function test_student_can_submit_index_answer_when_correct_answer_is_option_text(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'HTML basics',
            'status' => 'published',
            'quiz_type' => 'exam',
            'anti_cheat_enabled' => false,
            'max_attempts' => 1,
        ]);

        Question::create([
            'quiz_id' => $quiz->id,
            'teacher_id' => $teacher->id,
            'content' => 'Which tag creates a link?',
            'type' => 'multiple_choice',
            'options' => ['<link>', '<a>', '<href>', '<url>'],
            'correct_answer' => '<a>',
            'points' => 2,
        ]);

        $student->quizAttempts()->attach($quiz->id, ['started_at' => now()]);

        $this->actingAs($student)
            ->postJson(route('student.quiz-take.submit', $quiz), [
                'answers' => [
                    (string) $quiz->questions()->first()->id => 1,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('score', 2)
            ->assertJsonPath('total', 2)
            ->assertJsonPath('percent', 100);
    }

    public function test_submit_creates_attempt_if_active_attempt_is_missing(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Recover missing attempt',
            'status' => 'published',
            'quiz_type' => 'exam',
            'anti_cheat_enabled' => false,
            'max_attempts' => 1,
        ]);

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'teacher_id' => $teacher->id,
            'content' => '2 + 2 = ?',
            'type' => 'multiple_choice',
            'options' => ['3', '4', '5', '6'],
            'correct_answer' => '1',
            'points' => 1,
        ]);

        $this->actingAs($student)
            ->postJson(route('student.quiz-take.submit', $quiz), [
                'answers' => [
                    (string) $question->id => 1,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('score', 1)
            ->assertJsonPath('percent', 100);

        $this->assertDatabaseHas('quiz_user', [
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 1,
        ]);
    }

    public function test_submit_is_idempotent_after_quiz_was_already_submitted(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Already submitted quiz',
            'status' => 'published',
            'quiz_type' => 'exam',
            'anti_cheat_enabled' => false,
            'max_attempts' => 1,
        ]);

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'teacher_id' => $teacher->id,
            'content' => 'Submitted question',
            'type' => 'short_answer',
            'options' => [],
            'correct_answer' => 'ok',
            'points' => 1,
        ]);

        $student->quizAttempts()->attach($quiz->id, [
            'answers' => json_encode([(string) $question->id => 'ok']),
            'score' => 1,
            'total_points' => 1,
            'started_at' => now()->subHour(),
            'submitted_at' => now()->subMinutes(10),
            'is_graded' => true,
        ]);

        $this->actingAs($student)
            ->postJson(route('student.quiz-take.submit', $quiz), [
                'answers' => [
                    (string) $question->id => 'ok',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('already_submitted', true)
            ->assertJsonPath('redirect_url', route('student.quiz-result', $quiz));
    }

    public function test_student_cannot_submit_unpublished_quiz_directly(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Draft quiz',
            'status' => 'draft',
            'quiz_type' => 'exam',
            'anti_cheat_enabled' => false,
            'max_attempts' => 1,
        ]);
        $question = Question::create([
            'quiz_id' => $quiz->id,
            'teacher_id' => $teacher->id,
            'content' => 'Draft question',
            'type' => 'short_answer',
            'options' => [],
            'correct_answer' => 'ok',
            'points' => 1,
        ]);

        $this->actingAs($student)
            ->postJson(route('student.quiz-take.submit', $quiz), [
                'answers' => [(string) $question->id => 'ok'],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('quiz_user', [
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_student_cannot_submit_scheduled_or_expired_quiz_directly(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $futureQuiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Future quiz',
            'status' => 'published',
            'quiz_type' => 'exam',
            'start_at' => now()->addHour(),
            'max_attempts' => 1,
        ]);
        $expiredQuiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Expired quiz',
            'status' => 'published',
            'quiz_type' => 'exam',
            'end_at' => now()->subHour(),
            'max_attempts' => 1,
        ]);

        foreach ([$futureQuiz, $expiredQuiz] as $quiz) {
            $question = Question::create([
                'quiz_id' => $quiz->id,
                'teacher_id' => $teacher->id,
                'content' => 'Timed question',
                'type' => 'short_answer',
                'options' => [],
                'correct_answer' => 'ok',
                'points' => 1,
            ]);

            $this->actingAs($student)
                ->postJson(route('student.quiz-take.submit', $quiz), [
                    'answers' => [(string) $question->id => 'ok'],
                ])
                ->assertForbidden();

            $this->assertDatabaseMissing('quiz_user', [
                'quiz_id' => $quiz->id,
                'user_id' => $student->id,
            ]);
        }
    }

    public function test_student_can_submit_true_false_when_correct_answer_is_vietnamese_text(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'CSS basics',
            'status' => 'published',
            'quiz_type' => 'exam',
            'anti_cheat_enabled' => false,
            'max_attempts' => 1,
        ]);

        Question::create([
            'quiz_id' => $quiz->id,
            'teacher_id' => $teacher->id,
            'content' => 'CSS changes presentation.',
            'type' => 'true_false',
            'options' => ['Đúng', 'Sai'],
            'correct_answer' => 'Đúng',
            'points' => 1,
        ]);

        $student->quizAttempts()->attach($quiz->id, ['started_at' => now()]);

        $this->actingAs($student)
            ->postJson(route('student.quiz-take.submit', $quiz), [
                'answers' => [
                    (string) $quiz->questions()->first()->id => 'true',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('score', 1)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('percent', 100);
    }
}
