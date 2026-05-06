<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\ClassModel;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizListTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_quizzes_page_groups_assigned_quizzes_by_learning_status(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'name' => 'Frontend fundamentals',
            'status' => 'published',
        ]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $openQuiz = $this->createQuiz($teacher, $course, 'Open quiz', [
            'end_at' => now()->addDay(),
        ]);
        $scheduledQuiz = $this->createQuiz($teacher, $course, 'Scheduled quiz', [
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
        ]);
        $completedQuiz = $this->createQuiz($teacher, $course, 'Completed quiz', [
            'end_at' => now()->addDay(),
        ]);
        $missedQuiz = $this->createQuiz($teacher, $course, 'Missed quiz', [
            'end_at' => now()->subDay(),
        ]);

        $student->quizAttempts()->attach($completedQuiz->id, [
            'score' => 8,
            'total_points' => 10,
            'started_at' => now()->subHour(),
            'submitted_at' => now()->subMinutes(10),
            'is_graded' => true,
        ]);

        $response = $this->actingAs($student)->get(route('student.quizzes'));

        $response
            ->assertOk()
            ->assertViewIs('pages.student.quizzes')
            ->assertViewHas('activeTab', 'available')
            ->assertViewHas('summary', function (array $summary) {
                return $summary['total'] === 4
                    && $summary['available'] === 1
                    && $summary['scheduled'] === 1
                    && $summary['completed'] === 1
                    && $summary['missed'] === 1;
            })
            ->assertViewHas('available', function ($quizzes) use ($openQuiz, $scheduledQuiz) {
                return $quizzes->pluck('id')->all() === [$openQuiz->id];
            })
            ->assertViewHas('scheduled', function ($quizzes) use ($scheduledQuiz) {
                return $quizzes->pluck('id')->all() === [$scheduledQuiz->id];
            })
            ->assertViewHas('completed', function ($quizzes) use ($completedQuiz) {
                return $quizzes->pluck('id')->all() === [$completedQuiz->id];
            })
            ->assertViewHas('missed', function ($quizzes) use ($missedQuiz) {
                return $quizzes->pluck('id')->all() === [$missedQuiz->id];
            });
    }

    public function test_student_quizzes_page_preserves_status_and_filters(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'name' => 'JavaScript',
            'status' => 'published',
        ]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $exam = $this->createQuiz($teacher, $course, 'DOM exam', ['quiz_type' => 'exam']);
        $practice = $this->createQuiz($teacher, $course, 'DOM practice', ['quiz_type' => 'practice']);

        $student->quizAttempts()->attach($exam->id, [
            'score' => 7,
            'total_points' => 10,
            'started_at' => now()->subHour(),
            'submitted_at' => now()->subMinutes(5),
            'is_graded' => true,
        ]);

        $response = $this->actingAs($student)->get(route('student.quizzes', [
            'status' => 'completed',
            'type' => 'exam',
            'course_id' => $course->id,
            'q' => 'DOM',
        ]));

        $response
            ->assertOk()
            ->assertViewHas('activeTab', 'completed')
            ->assertViewHas('filters', function (array $filters) use ($course) {
                return $filters['type'] === 'exam'
                    && $filters['course_id'] === $course->id
                    && $filters['q'] === 'DOM';
            })
            ->assertViewHas('available', function ($quizzes) use ($practice) {
                return $quizzes->pluck('id')->all() === [];
            })
            ->assertViewHas('completed', function ($quizzes) use ($exam) {
                return $quizzes->pluck('id')->all() === [$exam->id];
            });
    }

    public function test_student_only_sees_quizzes_assigned_to_them(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);

        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Assigned class',
            'code' => 'ASSIGN1',
        ]);
        $class->students()->attach($student->id, ['joined_at' => now()]);

        $course = Course::create([
            'teacher_id' => $teacher->id,
            'name' => 'Assigned course',
            'status' => 'published',
        ]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $classQuiz = $this->createQuiz($teacher, null, 'Class assigned quiz', ['class_id' => $class->id]);
        $courseQuiz = $this->createQuiz($teacher, $course, 'Course assigned quiz');
        $specificQuiz = $this->createQuiz($teacher, null, 'Specific assigned quiz', ['assigned_students' => [$student->id]]);
        $publicQuiz = $this->createQuiz($teacher, null, 'Public quiz', ['public_to_all_students' => true]);

        $this->createQuiz($teacher, null, 'Other student specific quiz', ['assigned_students' => [$otherStudent->id]]);
        $this->createQuiz($teacher, null, 'Unassigned quiz');

        $response = $this->actingAs($student)->get(route('student.quizzes'));

        $response->assertOk()
            ->assertViewHas('summary', fn (array $summary) => $summary['total'] === 4)
            ->assertViewHas('available', function ($quizzes) use ($classQuiz, $courseQuiz, $specificQuiz, $publicQuiz) {
                return $quizzes->pluck('id')->sort()->values()->all() === collect([
                    $classQuiz->id,
                    $courseQuiz->id,
                    $specificQuiz->id,
                    $publicQuiz->id,
                ])->sort()->values()->all();
            });
    }

    private function createQuiz(User $teacher, ?Course $course, string $title, array $overrides = []): Quiz
    {
        $quiz = Quiz::create(array_merge([
            'teacher_id' => $teacher->id,
            'course_id' => $course?->id,
            'title' => $title,
            'status' => 'published',
            'quiz_type' => 'exam',
            'anti_cheat_enabled' => false,
            'time_limit' => 30,
            'passing_score' => 50,
            'max_attempts' => 1,
        ], $overrides));

        Question::create([
            'quiz_id' => $quiz->id,
            'teacher_id' => $teacher->id,
            'content' => $title . ' question',
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => '0',
            'points' => 10,
        ]);

        return $quiz;
    }
}
