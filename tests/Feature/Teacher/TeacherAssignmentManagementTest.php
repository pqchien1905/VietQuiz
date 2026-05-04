<?php

namespace Tests\Feature\Teacher;

use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
