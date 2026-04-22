<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $teacher1 = User::where('email', 'teacher@demo.com')->first();
        $teacher2 = User::where('email', 'teacher2@demo.com')->first();
        $classes = ClassModel::all();

        $courses = [
            ['teacher_id' => $teacher1->id, 'class_id' => $classes[0]->id, 'name' => 'Phát triển Web', 'description' => 'Học HTML, CSS, JavaScript và PHP cơ bản đến nâng cao', 'color' => '#3b82f6', 'icon' => '💻', 'status' => 'published'],
            ['teacher_id' => $teacher1->id, 'class_id' => $classes[0]->id, 'name' => 'Cấu trúc Dữ liệu', 'description' => 'Array, LinkedList, Stack, Queue, Tree, Graph', 'color' => '#f97316', 'icon' => '🌳', 'status' => 'published'],
            ['teacher_id' => $teacher1->id, 'class_id' => $classes[1]->id, 'name' => 'Thiết kế CSDL', 'description' => 'ERD, Normalization, SQL nâng cao, Indexing', 'color' => '#22c55e', 'icon' => '🗄️', 'status' => 'published'],
            ['teacher_id' => $teacher1->id, 'class_id' => $classes[1]->id, 'name' => 'Mạng Máy tính', 'description' => 'OSI, TCP/IP, Routing, Subnetting', 'color' => '#a855f7', 'icon' => '🌐', 'status' => 'published'],
            ['teacher_id' => $teacher1->id, 'class_id' => $classes[3]->id ?? null, 'name' => 'Lập trình Java', 'description' => 'OOP, Collections, Streams, Spring Boot', 'color' => '#ef4444', 'icon' => '☕', 'status' => 'published'],
            ['teacher_id' => $teacher2->id, 'class_id' => $classes[2]->id, 'name' => 'Kỹ thuật Phần mềm', 'description' => 'SDLC, Agile, UML, Testing, CI/CD', 'color' => '#06b6d4', 'icon' => '⚙️', 'status' => 'draft'],
        ];

        $students = User::where('role', 'student')->get();

        foreach ($courses as $data) {
            $course = Course::create($data);

            // Enroll 5-10 students
            $enrolled = $students->random(rand(5, 10));
            foreach ($enrolled as $student) {
                $course->students()->attach($student->id, [
                    'enrolled_at' => now()->subDays(rand(1, 45)),
                ]);
            }
        }
    }
}
