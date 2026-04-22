<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        $teacher1 = User::where('email', 'teacher@demo.com')->first();
        $teacher2 = User::where('email', 'teacher2@demo.com')->first();
        $courses = Course::all();

        $quizzes = [
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[0]->id, 'title' => 'Kiểm tra HTML/CSS cơ bản', 'description' => 'Đánh giá kiến thức nền tảng về HTML5 và CSS3', 'duration_minutes' => 30, 'total_points' => 100, 'passing_score' => 60, 'status' => 'published', 'start_at' => now()->subDays(5), 'end_at' => now()->addDays(2)],
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[0]->id, 'title' => 'Bài thi JavaScript ES6+', 'description' => 'Kiểm tra kiến thức JavaScript hiện đại', 'duration_minutes' => 45, 'total_points' => 100, 'passing_score' => 50, 'status' => 'published', 'start_at' => now(), 'end_at' => now()->addDays(7)],
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[1]->id, 'title' => 'Kiểm tra Linked List', 'description' => 'Singly/Doubly/Circular Linked List', 'duration_minutes' => 25, 'total_points' => 50, 'passing_score' => 25, 'status' => 'published', 'start_at' => now()->subDays(10), 'end_at' => now()->subDays(3)],
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[1]->id, 'title' => 'Thi giữa kỳ CTDL', 'description' => 'Tổng hợp Array, Stack, Queue, Tree', 'duration_minutes' => 60, 'total_points' => 200, 'passing_score' => 100, 'status' => 'draft'],
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[2]->id, 'title' => 'Kiểm tra SQL cơ bản', 'description' => 'SELECT, JOIN, GROUP BY, HAVING', 'duration_minutes' => 30, 'total_points' => 80, 'passing_score' => 40, 'status' => 'published', 'start_at' => now()->subDays(3), 'end_at' => now()->addDays(4)],
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[2]->id, 'title' => 'Thiết kế ERD', 'description' => 'Vẽ sơ đồ ERD cho hệ thống quản lý', 'duration_minutes' => 40, 'total_points' => 100, 'passing_score' => 50, 'status' => 'published', 'start_at' => now()->addDays(1), 'end_at' => now()->addDays(10)],
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[3]->id, 'title' => 'Bài thi OSI Model', 'description' => '7 tầng mô hình OSI', 'duration_minutes' => 20, 'total_points' => 50, 'passing_score' => 25, 'status' => 'published', 'start_at' => now()->subDays(7), 'end_at' => now()->subDays(1)],
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[3]->id, 'title' => 'Kiểm tra TCP/IP', 'description' => 'Giao thức TCP, UDP, IP Addressing', 'duration_minutes' => 35, 'total_points' => 80, 'passing_score' => 40, 'status' => 'draft'],
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[4]->id, 'title' => 'Java OOP', 'description' => 'Encapsulation, Inheritance, Polymorphism, Abstraction', 'duration_minutes' => 40, 'total_points' => 100, 'passing_score' => 50, 'status' => 'published', 'start_at' => now()->subDays(15), 'end_at' => now()->subDays(8)],
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[4]->id, 'title' => 'Java Collections', 'description' => 'List, Set, Map, Queue', 'duration_minutes' => 30, 'total_points' => 60, 'passing_score' => 30, 'status' => 'published', 'start_at' => now()->subDays(2), 'end_at' => now()->addDays(5)],
            ['teacher_id' => $teacher2->id, 'course_id' => $courses[5]->id, 'title' => 'Thi cuối kỳ KTPM', 'description' => 'Bài thi tổng hợp Kỹ thuật Phần mềm', 'duration_minutes' => 90, 'total_points' => 200, 'passing_score' => 100, 'status' => 'draft'],
            ['teacher_id' => $teacher1->id, 'course_id' => $courses[0]->id, 'title' => 'Bài thi PHP Laravel', 'description' => 'Routing, Controller, Eloquent, Blade', 'duration_minutes' => 50, 'total_points' => 120, 'passing_score' => 60, 'status' => 'published', 'start_at' => now()->addDays(3), 'end_at' => now()->addDays(14)],
        ];

        foreach ($quizzes as $quizData) {
            $quiz = Quiz::create($quizData);
            $this->seedQuestions($quiz, $quiz->teacher_id);
        }

        // Seed quiz attempts for published quizzes
        $this->seedAttempts();
    }

    private function seedQuestions(Quiz $quiz, int $teacherId): void
    {
        $questionBanks = [
            'Kiểm tra HTML/CSS cơ bản' => [
                ['content' => 'HTML viết tắt của gì?', 'type' => 'multiple_choice', 'options' => ['HyperText Markup Language', 'High Tech Modern Language', 'HyperTransfer Markup Language', 'Home Tool Markup Language'], 'correct_answer' => 'HyperText Markup Language', 'points' => 10],
                ['content' => 'Thẻ nào dùng để tạo liên kết trong HTML?', 'type' => 'multiple_choice', 'options' => ['<link>', '<a>', '<href>', '<url>'], 'correct_answer' => '<a>', 'points' => 10],
                ['content' => 'CSS viết tắt của Cascading Style Sheets', 'type' => 'true_false', 'options' => ['Đúng', 'Sai'], 'correct_answer' => 'Đúng', 'points' => 5],
                ['content' => 'Thuộc tính CSS nào dùng để thay đổi màu chữ?', 'type' => 'multiple_choice', 'options' => ['font-color', 'text-color', 'color', 'foreground'], 'correct_answer' => 'color', 'points' => 10],
                ['content' => 'Giải thích sự khác nhau giữa id và class trong CSS', 'type' => 'short_answer', 'options' => null, 'correct_answer' => 'id là duy nhất, class có thể dùng nhiều lần', 'points' => 15],
                ['content' => 'Thẻ HTML5 nào dùng cho nội dung chính?', 'type' => 'multiple_choice', 'options' => ['<main>', '<content>', '<body>', '<section>'], 'correct_answer' => '<main>', 'points' => 10],
                ['content' => 'Box model gồm những thành phần nào?', 'type' => 'short_answer', 'options' => null, 'correct_answer' => 'content, padding, border, margin', 'points' => 15],
                ['content' => 'Flexbox là một phương pháp layout 2 chiều', 'type' => 'true_false', 'options' => ['Đúng', 'Sai'], 'correct_answer' => 'Sai', 'points' => 5],
                ['content' => 'Thuộc tính nào dùng để bo góc?', 'type' => 'multiple_choice', 'options' => ['corner-radius', 'border-radius', 'round-corner', 'border-round'], 'correct_answer' => 'border-radius', 'points' => 10],
                ['content' => 'Selector * trong CSS chọn tất cả phần tử', 'type' => 'true_false', 'options' => ['Đúng', 'Sai'], 'correct_answer' => 'Đúng', 'points' => 10],
            ],
        ];

        // Use bank if available, otherwise generate generic questions
        $questions = $questionBanks[$quiz->title] ?? $this->generateGenericQuestions($quiz->title);

        foreach ($questions as $q) {
            Question::create(array_merge($q, [
                'quiz_id' => $quiz->id,
                'teacher_id' => $teacherId,
                'subject' => $quiz->course?->name,
            ]));
        }
    }

    private function generateGenericQuestions(string $title): array
    {
        $questions = [];
        $count = rand(8, 15);
        for ($i = 1; $i <= $count; $i++) {
            $type = ['multiple_choice', 'true_false', 'multiple_choice', 'short_answer'][rand(0, 3)];
            $q = ['content' => "Câu hỏi $i — $title", 'type' => $type, 'points' => $type === 'short_answer' ? 15 : 10];

            if ($type === 'multiple_choice') {
                $q['options'] = ["Đáp án A", "Đáp án B", "Đáp án C", "Đáp án D"];
                $q['correct_answer'] = "Đáp án A";
            } elseif ($type === 'true_false') {
                $q['options'] = ['Đúng', 'Sai'];
                $q['correct_answer'] = rand(0, 1) ? 'Đúng' : 'Sai';
            } else {
                $q['options'] = null;
                $q['correct_answer'] = "Đáp án mẫu cho câu $i";
            }
            $questions[] = $q;
        }
        return $questions;
    }

    private function seedAttempts(): void
    {
        $publishedQuizzes = Quiz::where('status', 'published')->whereNotNull('start_at')->where('start_at', '<=', now())->get();
        $students = User::where('role', 'student')->take(15)->get();

        foreach ($publishedQuizzes as $quiz) {
            $attemptStudents = $students->random(rand(3, min(8, $students->count())));
            foreach ($attemptStudents as $student) {
                $score = rand(30, 100);
                $quiz->students()->attach($student->id, [
                    'score' => $score,
                    'total_points' => $quiz->total_points,
                    'answers' => json_encode(['sample' => true]),
                    'started_at' => now()->subHours(rand(1, 72)),
                    'submitted_at' => now()->subHours(rand(0, 48)),
                    'is_graded' => true,
                ]);
            }
        }
    }
}
