<?php

namespace Tests\Feature\Student;

use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentAssignmentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_submit_assignment_with_attachment(): void
    {
        Storage::fake('local');

        [$student, $assignment] = $this->assignedStudentAndAssignment();
        $file = UploadedFile::fake()->create('answer.pdf', 12, 'application/pdf');

        $this->actingAs($student)
            ->post(route('student.assignment.submit', $assignment), [
                'content' => 'Bai nop co file dinh kem',
                'attachment' => $file,
            ])
            ->assertRedirect();

        $submission = Submission::firstOrFail();

        $this->assertSame($assignment->id, $submission->assignment_id);
        $this->assertSame($student->id, $submission->student_id);
        $this->assertNotNull($submission->attachment);
        $this->assertSame('answer.pdf', basename($submission->attachment));
        Storage::disk('local')->assertExists($submission->attachment);
    }

    public function test_student_can_submit_assignment_with_xlsx_attachment(): void
    {
        Storage::fake('local');

        [$student, $assignment] = $this->assignedStudentAndAssignment();
        $file = UploadedFile::fake()->create(
            'bang-tinh.xlsx',
            12,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $this->actingAs($student)
            ->post(route('student.assignment.submit', $assignment), [
                'content' => 'Bai nop bang tinh',
                'attachment' => $file,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $submission = Submission::firstOrFail();

        $this->assertNotNull($submission->attachment);
        $this->assertSame('bang-tinh.xlsx', basename($submission->attachment));
        Storage::disk('local')->assertExists($submission->attachment);
    }

    public function test_student_can_replace_existing_submission_attachment(): void
    {
        Storage::fake('local');

        [$student, $assignment] = $this->assignedStudentAndAssignment();
        $oldPath = UploadedFile::fake()
            ->create('old.pdf', 8, 'application/pdf')
            ->store('submissions');

        Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content' => 'Old content',
            'attachment' => $oldPath,
            'submitted_at' => now()->subHour(),
        ]);

        $newFile = UploadedFile::fake()->image('new-answer.png');

        $this->actingAs($student)
            ->post(route('student.assignment.submit', $assignment), [
                'content' => 'Updated content',
                'attachment' => $newFile,
            ])
            ->assertRedirect();

        $submission = Submission::firstOrFail();

        $this->assertSame('Updated content', $submission->content);
        $this->assertNotSame($oldPath, $submission->attachment);
        $this->assertSame('new-answer.png', basename($submission->attachment));
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($submission->attachment);
    }

    public function test_assignment_detail_uses_compact_attachment_download_and_native_file_input(): void
    {
        Storage::fake('local');

        [$student, $assignment] = $this->assignedStudentAndAssignment();
        Storage::disk('local')->put('assignments/instructions.pdf', '%PDF-1.4 test');
        $assignment->update(['attachment' => 'assignments/instructions.pdf']);

        $this->actingAs($student)
            ->get(route('student.assignment-detail', $assignment))
            ->assertOk()
            ->assertSee('name="attachment"', false)
            ->assertSee('id="file-input"', false)
            ->assertSee('.xlsx', false)
            ->assertSee(route('student.assignment.attachment.preview', $assignment), false)
            ->assertSee('Tải xuống')
            ->assertDontSee('<iframe', false)
            ->assertDontSee('attachment-preview__body', false);
    }

    public function test_student_can_preview_assignment_pdf_attachment(): void
    {
        Storage::fake('local');

        [$student, $assignment] = $this->assignedStudentAndAssignment();
        Storage::disk('local')->put('assignments/instructions.pdf', '%PDF-1.4 test');
        $assignment->update(['attachment' => 'assignments/instructions.pdf']);

        $this->actingAs($student)
            ->get(route('student.assignment.attachment.preview', $assignment))
            ->assertOk()
            ->assertViewIs('pages.student.assignment-attachment-preview')
            ->assertSee('Bản xem trước')
            ->assertSee('<iframe', false)
            ->assertSee(route('student.assignment.attachment.inline', $assignment), false)
            ->assertSee(route('student.assignment.attachment.download', $assignment), false);
    }

    public function test_student_can_preview_submission_attachment_from_detail_page(): void
    {
        Storage::fake('local');

        [$student, $assignment] = $this->assignedStudentAndAssignment();
        Storage::disk('local')->put('submissions/answer.pdf', '%PDF-1.4 answer');
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content' => 'Answer with attachment',
            'attachment' => 'submissions/answer.pdf',
            'submitted_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.assignment-detail', $assignment))
            ->assertOk()
            ->assertSee(route('student.submissions.attachment.preview', $submission), false);

        $this->actingAs($student)
            ->get(route('student.submissions.attachment.preview', $submission))
            ->assertOk()
            ->assertViewIs('pages.student.assignment-attachment-preview')
            ->assertSee('<iframe', false)
            ->assertSee(route('student.submissions.attachment.inline', $submission), false)
            ->assertSee(route('student.submissions.attachment.download', $submission), false);
    }

    private function assignedStudentAndAssignment(): array
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'name' => 'Lop nop bai',
            'code' => 'SUB101',
            'status' => 'active',
        ]);
        $class->students()->attach($student->id, ['joined_at' => now()]);

        $assignment = Assignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'title' => 'Bai tap nop file',
            'description' => 'Nop file bai lam',
            'type' => 'file',
            'due_at' => now()->addDay(),
            'total_points' => 100,
        ]);

        return [$student, $assignment];
    }
}
