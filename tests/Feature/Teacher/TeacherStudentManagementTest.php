<?php

namespace Tests\Feature\Teacher;

use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class TeacherStudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_invite_existing_student_to_own_class_by_email(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'email' => 'student@example.test']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Lop moi',
            'code' => 'INV001',
        ]);

        $this->actingAs($teacher)
            ->post(route('teacher.students.invite-email'), [
                'class_id' => $class->id,
                'emails_raw' => $student->email,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('class_user', [
            'class_id' => $class->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_teacher_cannot_invite_student_to_another_teachers_class(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'email' => 'student@example.test']);
        $otherClass = ClassModel::create([
            'teacher_id' => $otherTeacher->id,
            'name' => 'Lop khac',
            'code' => 'INV002',
        ]);

        $this->actingAs($teacher)
            ->from(route('teacher.students'))
            ->post(route('teacher.students.invite-email'), [
                'class_id' => $otherClass->id,
                'emails_raw' => $student->email,
            ])
            ->assertRedirect(route('teacher.students'))
            ->assertSessionHasErrors('class_id');

        $this->assertDatabaseMissing('class_user', [
            'class_id' => $otherClass->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_teacher_cannot_remove_from_another_teachers_class_or_remove_non_student_user(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $otherClass = ClassModel::create([
            'teacher_id' => $otherTeacher->id,
            'name' => 'Lop cua nguoi khac',
            'code' => 'REM001',
        ]);
        $otherClass->students()->attach($student->id, ['joined_at' => now()]);

        $this->actingAs($teacher)
            ->from(route('teacher.students'))
            ->post(route('teacher.students.remove'), [
                'class_id' => $otherClass->id,
                'student_id' => $student->id,
            ])
            ->assertRedirect(route('teacher.students'))
            ->assertSessionHasErrors('class_id');

        $ownClass = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Lop cua toi',
            'code' => 'REM002',
        ]);

        $this->actingAs($teacher)
            ->from(route('teacher.students'))
            ->post(route('teacher.students.remove'), [
                'class_id' => $ownClass->id,
                'student_id' => $otherTeacher->id,
            ])
            ->assertRedirect(route('teacher.students'))
            ->assertSessionHasErrors('student_id');

        $this->assertDatabaseHas('class_user', [
            'class_id' => $otherClass->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_teacher_can_import_students_from_xlsx_template(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Nguyen Van A', 'email' => 'student.xlsx@example.test']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Lop import',
            'code' => 'IMP001',
        ]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['name', 'email'],
            [$student->name, $student->email],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'students_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $this->actingAs($teacher)
            ->post(route('teacher.classes.import', $class), [
                'students_file' => new UploadedFile(
                    $path,
                    'students.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    null,
                    true
                ),
            ])
            ->assertRedirect(route('teacher.class-detail', $class));

        $this->assertDatabaseHas('class_user', [
            'class_id' => $class->id,
            'user_id' => $student->id,
        ]);
    }
}
