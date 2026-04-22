<?php

namespace Database\Seeders;

use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('email', 'teacher@demo.com')->first();
        $submissions = Submission::all();

        foreach ($submissions as $submission) {
            $assignment = $submission->assignment;
            if (!$assignment) continue;

            // Grade ~60% of submissions
            if (rand(1, 10) <= 6) {
                $maxPoints = $assignment->total_points;
                $score = rand(intval($maxPoints * 0.5), $maxPoints);

                $feedbacks = [
                    'Bài làm tốt, tiếp tục phát huy!',
                    'Cần cải thiện phần phân tích.',
                    'Trình bày rõ ràng, logic tốt.',
                    'Thiếu một số yêu cầu, xem lại đề bài.',
                    'Xuất sắc! Đạt điểm cao nhất lớp.',
                    'Truy vấn tốt, cần tối ưu index.',
                    'Thiết kế đẹp, responsive tốt!',
                    'Phân tích đúng, cần thêm ví dụ.',
                ];

                Grade::create([
                    'student_id' => $submission->student_id,
                    'gradable_type' => Submission::class,
                    'gradable_id' => $submission->id,
                    'score' => $score,
                    'feedback' => $feedbacks[array_rand($feedbacks)],
                    'grader_id' => $teacher->id,
                    'graded_at' => now()->subDays(rand(0, 5)),
                ]);
            }
        }
    }
}
