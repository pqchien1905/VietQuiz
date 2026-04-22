<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $teacher1 = User::where('email', 'teacher@demo.com')->first();
        $courses = Course::where('teacher_id', $teacher1->id)->get();
        $students = User::where('role', 'student')->take(15)->get();

        $assignments = [
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[0]->id, 'class_id' => $courses[0]->class_id, 'title' => 'Xây dựng Thư viện Component React', 'description' => 'Tạo 5 components tái sử dụng: Button, Card, Modal, Input, Table', 'type' => 'file', 'due_at' => now()->addDays(1), 'total_points' => 100],
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[2]->id, 'class_id' => $courses[2]->class_id, 'title' => 'Thiết kế Sơ đồ ER hệ thống', 'description' => 'Vẽ ERD cho hệ thống quản lý thư viện', 'type' => 'file', 'due_at' => now()->addDays(5), 'total_points' => 80],
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[1]->id, 'class_id' => $courses[1]->class_id, 'title' => 'Cài đặt Cây Tìm kiếm Nhị phân', 'description' => 'Implement BST với insert, delete, search, traversal', 'type' => 'file', 'due_at' => now()->addDays(8), 'total_points' => 100],
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[1]->id, 'class_id' => $courses[1]->class_id, 'title' => 'Báo cáo so sánh thuật toán sắp xếp', 'description' => 'So sánh Bubble, Quick, Merge, Heap Sort', 'type' => 'text', 'due_at' => now()->subDays(1), 'total_points' => 50],
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[2]->id, 'class_id' => $courses[2]->class_id, 'title' => 'Truy vấn SQL Nâng cao', 'description' => 'Viết 10 truy vấn SQL phức tạp', 'type' => 'text', 'due_at' => now()->subDays(5), 'total_points' => 60],
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[0]->id, 'class_id' => $courses[0]->class_id, 'title' => 'Trang web Portfolio cá nhân', 'description' => 'Thiết kế và code portfolio responsive', 'type' => 'file', 'due_at' => now()->subDays(10), 'total_points' => 150],
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[3]->id, 'class_id' => $courses[3]->class_id, 'title' => 'Phân tích giao thức TCP/IP', 'description' => 'Dùng Wireshark capture và phân tích', 'type' => 'file', 'due_at' => now()->subDays(15), 'total_points' => 40],
        ];

        foreach ($assignments as $data) {
            $assignment = Assignment::create($data);

            // Create submissions for past-due assignments
            if ($assignment->due_at && $assignment->due_at->isPast()) {
                $submitters = $students->random(rand(3, 8));
                foreach ($submitters as $student) {
                    Submission::create([
                        'assignment_id' => $assignment->id,
                        'student_id' => $student->id,
                        'content' => 'Bài nộp của ' . $student->name,
                        'submitted_at' => $assignment->due_at->subHours(rand(1, 48)),
                    ]);
                }
            }
        }
    }
}
