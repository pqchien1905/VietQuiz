<?php

namespace Tests\Feature\Student;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizViolation;
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
            'public_to_all_students' => true,
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
            ->assertSee('AUTOSAVE_KEY')
            ->assertSee('autosave-status')
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
            'public_to_all_students' => true,
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
            'public_to_all_students' => true,
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
            'public_to_all_students' => true,
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

    public function test_student_cannot_start_quiz_after_max_attempts_are_used(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'One attempt only',
            'status' => 'published',
            'quiz_type' => 'exam',
            'anti_cheat_enabled' => false,
            'max_attempts' => 1,
            'public_to_all_students' => true,
        ]);

        Question::create([
            'quiz_id' => $quiz->id,
            'teacher_id' => $teacher->id,
            'content' => 'Already done',
            'type' => 'short_answer',
            'options' => [],
            'correct_answer' => 'ok',
            'points' => 1,
        ]);

        $student->quizAttempts()->attach($quiz->id, [
            'answers' => json_encode([]),
            'score' => 0,
            'total_points' => 1,
            'started_at' => now()->subHour(),
            'submitted_at' => now()->subMinutes(30),
            'is_graded' => true,
        ]);

        $this->actingAs($student)
            ->get(route('student.quiz-take', $quiz))
            ->assertRedirect(route('student.quiz-result', $quiz));

        $this->assertSame(1, $student->quizAttempts()->where('quiz_id', $quiz->id)->count());
    }

    public function test_student_best_score_is_kept_across_multiple_attempts(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Best score quiz',
            'status' => 'published',
            'quiz_type' => 'practice',
            'anti_cheat_enabled' => false,
            'max_attempts' => 3,
            'public_to_all_students' => true,
        ]);

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'teacher_id' => $teacher->id,
            'content' => 'Best answer',
            'type' => 'short_answer',
            'options' => [],
            'correct_answer' => 'ok',
            'points' => 2,
        ]);

        $student->quizAttempts()->attach($quiz->id, ['started_at' => now()]);

        $this->actingAs($student)
            ->postJson(route('student.quiz-take.submit', $quiz), [
                'answers' => [(string) $question->id => 'ok'],
            ])
            ->assertOk()
            ->assertJsonPath('score', 2)
            ->assertJsonPath('percent', 100);

        $student->quizAttempts()->updateExistingPivot($quiz->id, [
            'started_at' => now(),
            'submitted_at' => null,
            'is_graded' => false,
        ]);

        $this->actingAs($student)
            ->postJson(route('student.quiz-take.submit', $quiz), [
                'answers' => [(string) $question->id => 'wrong'],
            ])
            ->assertOk()
            ->assertJsonPath('score', 2)
            ->assertJsonPath('percent', 100);

        $this->assertDatabaseHas('quiz_user', [
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 2,
            'total_points' => 2,
            'attempt_count' => 2,
        ]);
    }

    public function test_student_cannot_submit_after_attempt_time_limit_expires(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Timed backend quiz',
            'status' => 'published',
            'quiz_type' => 'exam',
            'time_limit' => 10,
            'anti_cheat_enabled' => false,
            'max_attempts' => 1,
            'public_to_all_students' => true,
        ]);

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'teacher_id' => $teacher->id,
            'content' => 'Late answer should not count',
            'type' => 'short_answer',
            'options' => [],
            'correct_answer' => 'ok',
            'points' => 2,
        ]);

        $student->quizAttempts()->attach($quiz->id, ['started_at' => now()->subMinutes(11)]);

        $this->actingAs($student)
            ->postJson(route('student.quiz-take.submit', $quiz), [
                'answers' => [(string) $question->id => 'ok'],
            ])
            ->assertForbidden()
            ->assertJsonPath('time_expired', true)
            ->assertJsonPath('redirect_url', route('student.quiz-result', $quiz));

        $this->assertDatabaseHas('quiz_user', [
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 0,
            'total_points' => 2,
            'is_graded' => true,
        ]);
    }

    public function test_student_score_uses_original_answer_when_options_are_shuffled(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Shuffled answers',
            'status' => 'published',
            'quiz_type' => 'exam',
            'shuffle_answers' => true,
            'anti_cheat_enabled' => false,
            'max_attempts' => 1,
            'public_to_all_students' => true,
        ]);

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'teacher_id' => $teacher->id,
            'content' => 'Correct original answer is B',
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => '1',
            'points' => 3,
        ]);

        $student->quizAttempts()->attach($quiz->id, [
            'started_at' => now(),
            'shuffled_options' => json_encode([
                $question->id => [
                    'original' => ['A', 'B', 'C', 'D'],
                    'shuffled' => ['D', 'B', 'A', 'C'],
                ],
            ]),
        ]);

        $this->actingAs($student)
            ->postJson(route('student.quiz-take.submit', $quiz), [
                'answers' => [
                    (string) $question->id => 1,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('score', 3)
            ->assertJsonPath('total', 3)
            ->assertJsonPath('percent', 100);
    }

    public function test_anti_cheat_violation_logs_and_auto_closes_attempt_at_limit(): void
    {
        config(['vietquiz.anti_cheat.max_violations' => 2]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Guarded exam',
            'status' => 'published',
            'quiz_type' => 'exam',
            'anti_cheat_enabled' => true,
            'max_attempts' => 1,
            'public_to_all_students' => true,
        ]);

        Question::create([
            'quiz_id' => $quiz->id,
            'teacher_id' => $teacher->id,
            'content' => 'Guarded question',
            'type' => 'short_answer',
            'options' => [],
            'correct_answer' => 'ok',
            'points' => 2,
        ]);

        $student->quizAttempts()->attach($quiz->id, ['started_at' => now()]);

        foreach (['tab_hidden', 'focus_lost'] as $index => $eventType) {
            $response = $this->actingAs($student)
                ->postJson(route('student.quiz-take.violations', $quiz), [
                    'event_type' => $eventType,
                    'metadata' => ['sequence' => $index + 1],
                ])
                ->assertOk()
                ->assertJsonPath('logged', true)
                ->assertJsonPath('violation_count', $index + 1)
                ->assertJsonPath('max_violations', 2);

            if ($index < 1) {
                $response->assertJsonPath('should_auto_submit', false);
            } else {
                $response->assertJsonPath('should_auto_submit', true)
                    ->assertJsonPath('redirect_url', route('student.quiz-result', $quiz));
            }
        }

        $this->assertSame(2, QuizViolation::where('quiz_id', $quiz->id)->where('user_id', $student->id)->count());
        $this->assertDatabaseHas('quiz_user', [
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 0,
            'total_points' => 2,
            'is_graded' => true,
        ]);
    }

    public function test_screenshot_attempt_is_ignored_by_anti_cheat_counter(): void
    {
        config(['vietquiz.anti_cheat.max_violations' => 2]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Screenshot guarded exam',
            'status' => 'published',
            'quiz_type' => 'exam',
            'anti_cheat_enabled' => true,
            'max_attempts' => 1,
            'public_to_all_students' => true,
        ]);

        Question::create([
            'quiz_id' => $quiz->id,
            'teacher_id' => $teacher->id,
            'content' => 'Guarded question',
            'type' => 'short_answer',
            'options' => [],
            'correct_answer' => 'ok',
            'points' => 2,
        ]);

        $student->quizAttempts()->attach($quiz->id, ['started_at' => now()]);

        $this->actingAs($student)
            ->postJson(route('student.quiz-take.violations', $quiz), [
                'event_type' => 'screenshot_attempt',
                'metadata' => ['key' => 'PrintScreen'],
            ])
            ->assertOk()
            ->assertJsonPath('logged', false)
            ->assertJsonPath('ignored', true)
            ->assertJsonPath('violation_count', 0)
            ->assertJsonPath('should_auto_submit', false);

        $this->assertSame(0, QuizViolation::where('quiz_id', $quiz->id)->where('user_id', $student->id)->count());
        $this->assertDatabaseHas('quiz_user', [
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'submitted_at' => null,
        ]);
    }

    public function test_copy_and_cut_are_ignored_by_anti_cheat_counter(): void
    {
        config(['vietquiz.anti_cheat.max_violations' => 2]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Clipboard guarded exam',
            'status' => 'published',
            'quiz_type' => 'exam',
            'anti_cheat_enabled' => true,
            'max_attempts' => 1,
            'public_to_all_students' => true,
        ]);

        Question::create([
            'quiz_id' => $quiz->id,
            'teacher_id' => $teacher->id,
            'content' => 'Guarded question',
            'type' => 'short_answer',
            'options' => [],
            'correct_answer' => 'ok',
            'points' => 2,
        ]);

        $student->quizAttempts()->attach($quiz->id, ['started_at' => now()]);

        foreach (['copy', 'cut'] as $eventType) {
            $this->actingAs($student)
                ->postJson(route('student.quiz-take.violations', $quiz), [
                    'event_type' => $eventType,
                    'metadata' => ['target' => 'BODY'],
                ])
                ->assertOk()
                ->assertJsonPath('logged', false)
                ->assertJsonPath('ignored', true)
                ->assertJsonPath('violation_count', 0)
                ->assertJsonPath('should_auto_submit', false);
        }

        $this->actingAs($student)
            ->postJson(route('student.quiz-take.violations', $quiz), [
                'event_type' => 'paste',
                'metadata' => ['target' => 'BODY'],
            ])
            ->assertOk()
            ->assertJsonPath('logged', true)
            ->assertJsonPath('violation_count', 1)
            ->assertJsonPath('should_auto_submit', false);

        $this->assertSame(1, QuizViolation::where('quiz_id', $quiz->id)->where('user_id', $student->id)->count());
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
            'public_to_all_students' => true,
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
            'public_to_all_students' => true,
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
