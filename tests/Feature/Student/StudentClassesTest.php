<?php

namespace Tests\Feature\Student;

use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentClassesTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_classes_page_displays_real_enrolled_classes_and_metrics(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Teacher One']);
        $student = User::factory()->create(['role' => 'student']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Lớp Web căn bản',
            'code' => 'WEB101',
            'subject' => 'Tin học',
            'grade_level' => '10',
            'status' => 'active',
            'color' => '#16a34a',
        ]);
        $class->students()->attach($student->id, ['joined_at' => now()]);

        $course = Course::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'name' => 'HTML CSS',
            'status' => 'published',
        ]);
        Quiz::create([
            'teacher_id' => $teacher->id,
            'course_id' => $course->id,
            'title' => 'Quiz HTML',
            'status' => 'published',
        ]);
        Assignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'title' => 'Bài tập CSS',
            'type' => 'text',
            'due_at' => now()->addDay(),
        ]);

        $this->actingAs($student)
            ->get(route('student.classes'))
            ->assertOk()
            ->assertViewIs('pages.student.classes')
            ->assertSee('Lớp Web căn bản')
            ->assertSee('Teacher One')
            ->assertSee('2 việc cần hoàn thành')
            ->assertViewHas('summary', function (array $summary) {
                return $summary['total'] === 1
                    && $summary['active'] === 1
                    && $summary['courses'] === 1
                    && $summary['pending_items'] === 2;
            });
    }

    public function test_student_classes_page_renders_icon_keywords_as_icons_not_text(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Dữ liệu 10C',
            'code' => 'DATA10',
            'subject' => 'Tin học',
            'status' => 'active',
            'icon' => 'database',
        ]);
        $class->students()->attach($student->id, ['joined_at' => now()]);

        $this->actingAs($student)
            ->get(route('student.classes'))
            ->assertOk()
            ->assertSee('student-class-icon', false)
            ->assertDontSeeText('database');

        $this->actingAs($student)
            ->get(route('student.classes.show', $class))
            ->assertOk()
            ->assertSee('class-detail-icon', false)
            ->assertDontSeeText('database');
    }

    public function test_student_classes_page_filters_by_status_subject_and_search(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Nguyễn Văn A']);
        $student = User::factory()->create(['role' => 'student']);
        $activeClass = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Toán 10A',
            'code' => 'MATH10',
            'subject' => 'Toán',
            'status' => 'active',
        ]);
        $archivedClass = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Văn 10A',
            'code' => 'LIT10',
            'subject' => 'Ngữ văn',
            'status' => 'archived',
        ]);
        $activeClass->students()->attach($student->id, ['joined_at' => now()]);
        $archivedClass->students()->attach($student->id, ['joined_at' => now()]);

        $this->actingAs($student)
            ->get(route('student.classes', [
                'status' => 'archived',
                'subject' => 'Ngữ văn',
                'q' => 'Văn',
            ]))
            ->assertOk()
            ->assertViewHas('filters', function (array $filters) {
                return $filters['status'] === 'archived'
                    && $filters['subject'] === 'Ngữ văn'
                    && $filters['q'] === 'Văn';
            })
            ->assertViewHas('classes', function ($classes) use ($archivedClass) {
                return $classes->total() === 1
                    && $classes->first()->id === $archivedClass->id;
            })
            ->assertSee('Văn 10A')
            ->assertDontSee('Toán 10A');
    }

    public function test_student_can_leave_class_from_classes_page(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Rời lớp test',
            'code' => 'LEAVE1',
            'status' => 'active',
        ]);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'name' => 'Course in class',
            'status' => 'published',
        ]);
        $class->students()->attach($student->id, ['joined_at' => now()]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $this->actingAs($student)
            ->delete(route('student.classes.leave', $class))
            ->assertRedirect(route('student.classes'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('class_user', [
            'class_id' => $class->id,
            'user_id' => $student->id,
        ]);
        $this->assertDatabaseMissing('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
    }
}
