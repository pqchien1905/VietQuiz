<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Question;
use App\Models\QuestionFolder;
use App\Models\Quiz;
use App\Models\QuizFolder;
use App\Models\Submission;
use App\Models\User;
use App\Models\VipSubscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PaginationDemoSeeder extends Seeder
{
    private const PREFIX = 'PG Demo';

    public function run(): void
    {
        DB::transaction(function () {
            $teacher = User::updateOrCreate(
                ['email' => 'teacher@demo.com'],
                [
                    'name' => 'Giáo viên Demo',
                    'password' => Hash::make('password'),
                    'role' => 'teacher',
                    'can_switch_role' => false,
                    'last_active_role' => 'teacher',
                ]
            );

            VipSubscription::updateOrCreate(
                ['user_id' => $teacher->id],
                [
                    'plan' => 'yearly',
                    'status' => 'active',
                    'started_at' => now()->subDays(5),
                    'expires_at' => now()->addYear(),
                ]
            );

            $this->cleanDemoData($teacher);

            $students = $this->seedStudents();
            $classes = $this->seedClasses($teacher, $students);
            $courses = $this->seedCourses($teacher, $classes, $students);
            $quizFolders = $this->seedQuizFolders($teacher);
            $questionFolders = $this->seedQuestionFolders($teacher);
            $quizzes = $this->seedQuizzes($teacher, $classes, $courses, $quizFolders, $students);
            $this->seedQuestionBank($teacher, $questionFolders);
            $this->seedAssignments($teacher, $classes, $courses, $students);
            $this->seedQuizAttempts($quizzes, $students);
        });
    }

    private function cleanDemoData(User $teacher): void
    {
        $quizIds = Quiz::withTrashed()
            ->where('teacher_id', $teacher->id)
            ->where('title', 'like', self::PREFIX . '%')
            ->pluck('id');
        $assignmentIds = Assignment::withTrashed()
            ->where('teacher_id', $teacher->id)
            ->where('title', 'like', self::PREFIX . '%')
            ->pluck('id');
        $courseIds = Course::withTrashed()
            ->where('teacher_id', $teacher->id)
            ->where('name', 'like', self::PREFIX . '%')
            ->pluck('id');
        $classIds = ClassModel::withTrashed()
            ->where('teacher_id', $teacher->id)
            ->where('name', 'like', self::PREFIX . '%')
            ->pluck('id');
        $quizFolderIds = QuizFolder::where('teacher_id', $teacher->id)
            ->where('name', 'like', self::PREFIX . '%')
            ->pluck('id');
        $questionFolderIds = QuestionFolder::where('teacher_id', $teacher->id)
            ->where('name', 'like', self::PREFIX . '%')
            ->pluck('id');

        DB::table('quiz_user')->whereIn('quiz_id', $quizIds)->delete();
        Question::withTrashed()
            ->where(fn ($query) => $query
                ->whereIn('quiz_id', $quizIds)
                ->orWhereIn('folder_id', $questionFolderIds)
                ->orWhere('content', 'like', self::PREFIX . '%'))
            ->forceDelete();
        Quiz::withTrashed()->whereIn('id', $quizIds)->forceDelete();
        QuizFolder::whereIn('id', $quizFolderIds)->delete();
        QuestionFolder::whereIn('id', $questionFolderIds)->delete();

        $submissionIds = Submission::whereIn('assignment_id', $assignmentIds)->pluck('id');
        Grade::where(function ($query) use ($submissionIds, $quizIds) {
            $query->where(function ($inner) use ($submissionIds) {
                $inner->where('gradable_type', Submission::class)->whereIn('gradable_id', $submissionIds);
            })->orWhere(function ($inner) use ($quizIds) {
                $inner->where('gradable_type', Quiz::class)->whereIn('gradable_id', $quizIds);
            });
        })->delete();
        Submission::whereIn('id', $submissionIds)->delete();
        Assignment::withTrashed()->whereIn('id', $assignmentIds)->forceDelete();

        DB::table('course_user')->whereIn('course_id', $courseIds)->delete();
        Course::withTrashed()->whereIn('id', $courseIds)->forceDelete();

        DB::table('class_user')->whereIn('class_id', $classIds)->delete();
        ClassModel::withTrashed()->whereIn('id', $classIds)->forceDelete();

        $studentIds = User::where('email', 'like', 'pg-student-%@demo.test')->pluck('id');
        DB::table('class_user')->whereIn('user_id', $studentIds)->delete();
        DB::table('course_user')->whereIn('user_id', $studentIds)->delete();
        DB::table('quiz_user')->whereIn('user_id', $studentIds)->delete();
        Grade::whereIn('student_id', $studentIds)->delete();
        Submission::whereIn('student_id', $studentIds)->delete();
        User::withTrashed()->whereIn('id', $studentIds)->forceDelete();
    }

    private function seedStudents()
    {
        return collect(range(1, 45))->map(function (int $index) {
            return User::create([
                'name' => sprintf('PG Học sinh %02d', $index),
                'email' => sprintf('pg-student-%02d@demo.test', $index),
                'password' => Hash::make('password'),
                'role' => 'student',
                'can_switch_role' => false,
                'last_active_role' => 'student',
            ]);
        })->values();
    }

    private function seedClasses(User $teacher, $students)
    {
        $subjects = ['Toán học', 'Vật lý', 'Hóa học', 'Sinh học', 'Ngữ văn', 'Tiếng Anh', 'Tin học'];

        return collect(range(1, 28))->map(function (int $index) use ($teacher, $students, $subjects) {
            $class = ClassModel::create([
                'teacher_id' => $teacher->id,
                'name' => sprintf('%s Lớp %02d', self::PREFIX, $index),
                'code' => 'PG' . Str::upper(Str::random(4)) . str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'description' => 'Lớp mẫu dùng để kiểm tra phân trang và thống kê.',
                'subject' => $subjects[($index - 1) % count($subjects)],
                'grade_level' => (string) (10 + ($index % 3)),
                'status' => $index % 9 === 0 ? 'archived' : 'active',
            ]);

            $studentIds = $students->slice(($index * 3) % $students->count(), 12)->pluck('id');
            if ($studentIds->count() < 12) {
                $studentIds = $studentIds->merge($students->take(12 - $studentIds->count())->pluck('id'));
            }

            $class->students()->sync($studentIds->mapWithKeys(fn ($id) => [$id => ['joined_at' => now()->subDays(rand(1, 90))]])->all());

            return $class;
        })->values();
    }

    private function seedCourses(User $teacher, $classes, $students)
    {
        $colors = ['#2563eb', '#ea580c', '#16a34a', '#7c3aed', '#dc2626', '#0891b2'];

        return collect(range(1, 26))->map(function (int $index) use ($teacher, $classes, $students, $colors) {
            $class = $classes[($index - 1) % $classes->count()];
            $course = Course::create([
                'teacher_id' => $teacher->id,
                'class_id' => $class->id,
                'name' => sprintf('%s Khóa học %02d', self::PREFIX, $index),
                'description' => 'Khóa học mẫu có dữ liệu bài kiểm tra, bài tập và học sinh để kiểm thử phân trang.',
                'color' => $colors[($index - 1) % count($colors)],
                'status' => $index % 4 === 0 ? 'draft' : 'published',
            ]);

            $studentIds = $class->students()->pluck('users.id')->take(10);
            $course->students()->sync($studentIds->mapWithKeys(fn ($id) => [$id => ['enrolled_at' => now()->subDays(rand(1, 60))]])->all());

            return $course;
        })->values();
    }

    private function seedQuizFolders(User $teacher)
    {
        return collect(['Chương 1', 'Chương 2', 'Ôn tập', 'Kiểm tra nhanh'])
            ->map(fn ($name) => QuizFolder::create(['teacher_id' => $teacher->id, 'name' => self::PREFIX . ' ' . $name]))
            ->values();
    }

    private function seedQuestionFolders(User $teacher)
    {
        return collect(['Trắc nghiệm', 'Đúng sai', 'Tự luận', 'Tổng hợp'])
            ->map(fn ($name) => QuestionFolder::create(['teacher_id' => $teacher->id, 'name' => self::PREFIX . ' ' . $name]))
            ->values();
    }

    private function seedQuizzes(User $teacher, $classes, $courses, $folders, $students)
    {
        return collect(range(1, 36))->map(function (int $index) use ($teacher, $classes, $courses, $folders) {
            $class = $classes[($index - 1) % $classes->count()];
            $course = $courses[($index - 1) % $courses->count()];
            $quiz = Quiz::create([
                'teacher_id' => $teacher->id,
                'folder_id' => $folders[($index - 1) % $folders->count()]->id,
                'course_id' => $course->id,
                'class_id' => $class->id,
                'title' => sprintf('%s Bài kiểm tra %02d', self::PREFIX, $index),
                'description' => 'Bài kiểm tra mẫu phục vụ kiểm tra phân trang.',
                'time_limit' => 45,
                'duration_minutes' => 45,
                'total_points' => 10,
                'passing_score' => 50,
                'max_attempts' => 1,
                'status' => $index % 5 === 0 ? 'draft' : 'published',
                'start_at' => now()->subDays(30 - ($index % 20)),
                'end_at' => now()->addDays(30),
                'is_shuffle' => true,
                'show_result' => true,
                'quiz_type' => $index % 3 === 0 ? 'practice' : 'exam',
                'anti_cheat_enabled' => $index % 3 !== 0,
            ]);

            foreach (range(1, 6) as $order) {
                Question::create([
                    'quiz_id' => $quiz->id,
                    'teacher_id' => $teacher->id,
                    'subject' => $class->subject,
                    'content' => sprintf('%s Câu hỏi %02d.%02d?', self::PREFIX, $index, $order),
                    'type' => 'multiple_choice',
                    'options' => ['Đáp án A', 'Đáp án B', 'Đáp án C', 'Đáp án D'],
                    'correct_answer' => (string) (($order - 1) % 4),
                    'points' => round(10 / 6, 2),
                    'explanation' => 'Giải thích mẫu cho câu hỏi.',
                    'order' => $order,
                ]);
            }

            return $quiz;
        })->values();
    }

    private function seedQuestionBank(User $teacher, $folders): void
    {
        foreach (range(1, 130) as $index) {
            $type = $index % 10 === 0 ? 'short_answer' : ($index % 3 === 0 ? 'true_false' : 'multiple_choice');
            Question::create([
                'quiz_id' => null,
                'teacher_id' => $teacher->id,
                'folder_id' => $folders[($index - 1) % $folders->count()]->id,
                'subject' => 'Tổng hợp',
                'content' => sprintf('%s Ngân hàng câu hỏi %03d?', self::PREFIX, $index),
                'type' => $type,
                'options' => $type === 'multiple_choice' ? ['A', 'B', 'C', 'D'] : [],
                'correct_answer' => $type === 'true_false' ? 'true' : ($type === 'short_answer' ? 'Đáp án mẫu' : '0'),
                'points' => 1,
                'explanation' => 'Câu hỏi mẫu dùng để kiểm tra phân trang ngân hàng câu hỏi.',
                'order' => 0,
            ]);
        }
    }

    private function seedAssignments(User $teacher, $classes, $courses, $students): void
    {
        foreach (range(1, 34) as $index) {
            $class = $classes[($index - 1) % $classes->count()];
            $course = $courses[($index - 1) % $courses->count()];
            $assignment = Assignment::create([
                'teacher_id' => $teacher->id,
                'class_id' => $class->id,
                'course_id' => $course->id,
                'title' => sprintf('%s Bài tập %02d', self::PREFIX, $index),
                'description' => 'Bài tập mẫu dùng để kiểm thử phân trang và chấm điểm.',
                'type' => $index % 3 === 0 ? 'text' : 'file',
                'due_at' => now()->addDays(($index % 12) - 4),
                'total_points' => 100,
            ]);

            $classStudents = $class->students()->take(8)->get();
            foreach ($classStudents as $offset => $student) {
                if (($offset + $index) % 4 === 0) {
                    continue;
                }

                $submission = Submission::create([
                    'assignment_id' => $assignment->id,
                    'student_id' => $student->id,
                    'content' => sprintf('Nội dung bài nộp mẫu cho %s.', $assignment->title),
                    'submitted_at' => now()->subDays(rand(0, 20))->subHours(rand(1, 12)),
                ]);

                if (($offset + $index) % 3 === 0) {
                    $submission->grades()->create([
                        'student_id' => $student->id,
                        'score' => rand(65, 100),
                        'feedback' => 'Nhận xét mẫu.',
                        'grader_id' => $teacher->id,
                        'graded_at' => now()->subDays(rand(0, 7)),
                    ]);
                }
            }
        }
    }

    private function seedQuizAttempts($quizzes, $students): void
    {
        foreach ($quizzes->where('status', 'published')->take(22) as $quizIndex => $quiz) {
            $studentIds = $quiz->classModel?->students()->pluck('users.id')->take(8) ?? collect();
            foreach ($studentIds as $studentOffset => $studentId) {
                $score = rand(45, 100);
                DB::table('quiz_user')->updateOrInsert(
                    ['quiz_id' => $quiz->id, 'user_id' => $studentId],
                    [
                        'score' => $score,
                        'total_points' => 100,
                        'answers' => json_encode(['demo' => true, 'score' => $score]),
                        'started_at' => now()->subDays(rand(1, 25))->subMinutes(50),
                        'submitted_at' => now()->subDays(rand(1, 25)),
                        'is_graded' => $studentOffset % 2 === 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
