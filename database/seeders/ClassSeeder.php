<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        $teacher1 = User::where('email', 'teacher@demo.com')->first();
        $teacher2 = User::where('email', 'teacher2@demo.com')->first();

        $classes = [
            ['teacher_id' => $teacher1->id, 'name' => 'Lớp 10A - Tin học', 'code' => 'TH10A-2026', 'description' => 'Lớp chuyên Tin học khối 10', 'color' => '#3b82f6', 'icon' => '💻', 'subject' => 'Tin học', 'status' => 'active'],
            ['teacher_id' => $teacher1->id, 'name' => 'Lớp 11B - CNTT', 'code' => 'CN11B-2026', 'description' => 'Lớp Công nghệ Thông tin khối 11', 'color' => '#8b5cf6', 'icon' => '🖥️', 'subject' => 'CNTT', 'status' => 'active'],
            ['teacher_id' => $teacher2->id, 'name' => 'Lớp 9C - Toán', 'code' => 'TN09C-2026', 'description' => 'Lớp Toán nâng cao khối 9', 'color' => '#f97316', 'icon' => '📐', 'subject' => 'Toán', 'status' => 'active'],
            ['teacher_id' => $teacher1->id, 'name' => 'Lớp 10B - Web Dev', 'code' => 'WD10B-2026', 'description' => 'Lớp Phát triển Web khối 10', 'color' => '#22c55e', 'icon' => '🌐', 'subject' => 'Lập trình', 'status' => 'archived'],
        ];

        $students = User::where('role', 'student')->get();

        foreach ($classes as $index => $data) {
            $class = ClassModel::create($data);

            // Assign 7-10 students per class
            $count = rand(7, 10);
            $offset = $index * 7;
            $classStudents = $students->slice($offset, $count);
            foreach ($classStudents as $student) {
                $class->students()->attach($student->id, [
                    'joined_at' => now()->subDays(rand(1, 60)),
                ]);
            }
        }
    }
}
