<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('email', 'teacher@demo.com')->first();
        $students = User::where('role', 'student')->take(5)->get();

        $teacherNotifs = [
            ['title' => 'Bài thi mới được nộp', 'body' => 'Lê Minh Tuấn đã nộp bài "Kiểm tra HTML/CSS cơ bản"', 'type' => 'quiz', 'is_read' => false],
            ['title' => 'Học sinh mới tham gia lớp', 'body' => 'Phạm Thị Hương đã tham gia lớp 10A - Tin học', 'type' => 'class', 'is_read' => false],
            ['title' => 'Bài tập đến hạn', 'body' => 'Bài "Xây dựng Component React" sẽ đến hạn trong 24 giờ', 'type' => 'assignment', 'is_read' => true],
            ['title' => 'Cập nhật hệ thống', 'body' => 'VietQuiz v2.5 đã cập nhật tính năng phân tích mới', 'type' => 'system', 'is_read' => true],
            ['title' => '5 bài cần chấm điểm', 'body' => 'Có 5 bài tự luận đang chờ chấm từ bài thi JavaScript', 'type' => 'grading', 'is_read' => false],
        ];

        foreach ($teacherNotifs as $n) {
            Notification::create(array_merge($n, [
                'user_id' => $teacher->id,
                'created_at' => now()->subHours(rand(1, 72)),
            ]));
        }

        $studentNotifs = [
            ['title' => 'Bài thi mới', 'body' => 'GV. Nguyễn Văn An đã tạo bài thi "JavaScript ES6+" cho khóa Phát triển Web', 'type' => 'quiz', 'is_read' => false],
            ['title' => 'Điểm bài thi', 'body' => 'Bạn đạt 85/100 điểm trong bài "Kiểm tra HTML/CSS cơ bản"', 'type' => 'grade', 'is_read' => false],
            ['title' => 'Bài tập mới', 'body' => 'Bài tập "Thiết kế ERD" đã được giao, hạn nộp: 5 ngày', 'type' => 'assignment', 'is_read' => true],
            ['title' => 'Nhắc nhở deadline', 'body' => 'Bài "Cài đặt BST" sẽ hết hạn trong 2 ngày', 'type' => 'reminder', 'is_read' => false],
            ['title' => 'Chào mừng!', 'body' => 'Chào mừng bạn đến với VietQuiz! Khám phá các tính năng.', 'type' => 'system', 'is_read' => true],
        ];

        foreach ($students as $student) {
            foreach ($studentNotifs as $n) {
                if (rand(1, 10) <= 7) {
                    Notification::create(array_merge($n, [
                        'user_id' => $student->id,
                        'created_at' => now()->subHours(rand(1, 120)),
                    ]));
                }
            }
        }
    }
}
