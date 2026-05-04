<?php

namespace Tests\Feature\Student;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCoursesTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_courses_page_renders_icon_keywords_as_icons_not_text(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Web 11B',
            'code' => 'WEB11B',
            'status' => 'active',
        ]);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'name' => 'HTML CSS nền tảng',
            'status' => 'published',
            'icon' => 'layout',
        ]);
        $class->students()->attach($student->id, ['joined_at' => now()]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $this->actingAs($student)
            ->get(route('student.courses'))
            ->assertOk()
            ->assertViewIs('pages.student.courses')
            ->assertSee('student-course-icon', false)
            ->assertDontSeeText('layout');
    }
}
