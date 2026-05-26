<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Notification;
use App\Models\Promotion;
use App\Models\Question;
use App\Models\QuestionFolder;
use App\Models\Quiz;
use App\Models\QuizFolder;
use App\Models\QuizViolation;
use App\Models\Submission;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VipPayment;
use App\Models\VipPlan;
use App\Models\VipSubscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class VietQuizDemoSeeder extends Seeder
{
    private const FOCUS_EMAIL = 'pqchien1905@gmail.com';
    private const DEFAULT_PASSWORD = 'password';

    public function run(): void
    {
        $this->resetData();
        $this->seedSampleFiles();

        $passwordHash = Hash::make(self::DEFAULT_PASSWORD);
        [$teacherMain, $teacherMath, $teacherPhysics] = $this->seedTeachers($passwordHash);
        $students = $this->seedStudents($passwordHash);
        $focusUser = User::where('email', self::FOCUS_EMAIL)->firstOrFail();

        $classes = $this->seedClasses($teacherMain, $teacherMath, $teacherPhysics, $students);
        $courses = $this->seedCourses($classes);
        $this->enrollFocusUserAsStudent($focusUser, $classes, $courses);
        [$quizFolders, $questionFolders] = $this->seedFolders($teacherMain, $teacherMath, $teacherPhysics);

        $this->seedQuestionBank($teacherMain, $questionFolders[$teacherMain->id]);
        $quizzes = $this->seedQuizzes($teacherMain, $teacherMath, $teacherPhysics, $classes, $courses, $quizFolders, $students);
        $assignments = $this->seedAssignments($teacherMain, $teacherMath, $teacherPhysics, $classes, $courses);

        $this->seedAssignmentSubmissionsAndGrades($assignments, $focusUser, $teacherMain, $teacherMath, $teacherPhysics);
        $this->seedQuizAttemptsAndGrades($quizzes, $focusUser, $teacherMain, $teacherMath, $teacherPhysics);
        $this->seedFocusUserLearningJourney($focusUser, $teacherMain, $quizzes, $assignments);
        $this->seedNotifications($teacherMain, $students, $assignments, $quizzes, $focusUser);
        $this->seedVipAndPayments($teacherMain, $students);
        $this->seedPromotions();
        $this->seedTickets($teacherMain, $students);
    }

    private function enrollFocusUserAsStudent(User $focusUser, Collection $classes, Collection $courses): void
    {
        foreach ($classes->take(3) as $index => $class) {
            $class->students()->syncWithoutDetaching([
                $focusUser->id => ['joined_at' => now()->subDays(20 - $index)],
            ]);
        }

        foreach ($courses->take(4) as $index => $course) {
            $course->students()->syncWithoutDetaching([
                $focusUser->id => ['enrolled_at' => now()->subDays(18 - $index)],
            ]);
        }
    }

    private function resetData(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'vip_payments', 'vip_subscriptions', 'vip_plans', 'promotions', 'tickets',
            'notifications', 'quiz_violations', 'grades', 'submissions', 'quiz_user',
            'questions', 'assignments', 'quizzes', 'question_folders', 'quiz_folders',
            'course_user', 'class_user', 'courses', 'classes', 'sessions', 'password_reset_tokens',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        User::withTrashed()->where('email', '!=', 'admin@vietquiz.vn')->forceDelete();
        Schema::enableForeignKeyConstraints();
    }

    private function seedSampleFiles(): void
    {
        Storage::makeDirectory('assignments');
        Storage::makeDirectory('submissions');

        Storage::put('assignments/mau-bao-cao-phan-cung.txt', "MẪU BÁO CÁO PHẦN CỨNG\n\n1. Giới thiệu\n2. Thành phần chính\n3. Kết luận");
        Storage::put('assignments/mau-python-danh-sach.py', "# Bài tập Python\nnumbers = [1, 2, 3, 4, 5]\nprint(sum(numbers))\n");
        Storage::put('assignments/mau-landing-page.md', "# Bài tập Landing Page\n- Header\n- Hero\n- Footer\n");

        Storage::put('submissions/bai-nop-mau-1.txt', "Đây là bài nộp mẫu của học sinh. Nội dung đã hoàn thành theo yêu cầu.");
        Storage::put('submissions/bai-nop-mau-2.txt', "Bài nộp mẫu số 2: có bổ sung ví dụ thực tế và kết luận.");
    }

    private function seedTeachers(string $passwordHash): array
    {
        $teachers = [
            ['name' => 'Phạm Quang Chiến', 'email' => self::FOCUS_EMAIL, 'phone' => '0901905190', 'subject' => 'Tin học', 'can_switch_role' => true],
            ['name' => 'Nguyễn Thị Lan Anh', 'email' => 'lananh.teacher@vietquiz.test', 'phone' => '0902000001', 'subject' => 'Toán học', 'can_switch_role' => false],
            ['name' => 'Trần Minh Khoa', 'email' => 'khoa.teacher@vietquiz.test', 'phone' => '0902000002', 'subject' => 'Vật lý', 'can_switch_role' => false],
        ];

        return collect($teachers)->map(fn (array $teacher) => User::create($teacher + [
            'password' => $passwordHash,
            'role' => 'teacher',
            'last_active_role' => 'teacher',
            'email_verified_at' => now(),
        ]))->values()->all();
    }

    private function seedStudents(string $passwordHash): Collection
    {
        $rows = [
            ['name' => 'Nguyễn Bảo An', 'email' => 'baoan.student@vietquiz.test'],
            ['name' => 'Lê Minh Châu', 'email' => 'minhchau.student@vietquiz.test'],
            ['name' => 'Trần Gia Huy', 'email' => 'giahuy.student@vietquiz.test'],
            ['name' => 'Phạm Khánh Linh', 'email' => 'khanhlinh.student@vietquiz.test'],
            ['name' => 'Đỗ Quốc Bảo', 'email' => 'quocbao.student@vietquiz.test'],
            ['name' => 'Hoàng Nhật Minh', 'email' => 'nhatminh.student@vietquiz.test'],
            ['name' => 'Võ Tuệ Nhi', 'email' => 'tuenhi.student@vietquiz.test'],
            ['name' => 'Đặng Đức Anh', 'email' => 'ducanh.student@vietquiz.test'],
            ['name' => 'Bùi Hà My', 'email' => 'hamy.student@vietquiz.test'],
            ['name' => 'Mai Thành Đạt', 'email' => 'thanhdat.student@vietquiz.test'],
            ['name' => 'Phan Ngọc Mai', 'email' => 'ngocmai.student@vietquiz.test'],
            ['name' => 'Lý Hoàng Nam', 'email' => 'hoangnam.student@vietquiz.test'],
        ];

        return collect($rows)->values()->map(function (array $student, int $index) use ($passwordHash) {
            return User::create($student + [
                'phone' => '09110' . str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                'password' => $passwordHash,
                'role' => 'student',
                'can_switch_role' => false,
                'last_active_role' => 'student',
                'email_verified_at' => now(),
            ]);
        });
    }

    private function seedClasses(User $teacherMain, User $teacherMath, User $teacherPhysics, Collection $students): Collection
    {
        $rows = [
            ['teacher_id' => $teacherMain->id, 'name' => 'Tin học 10A1', 'code' => 'TH10A1', 'subject' => 'Tin học', 'grade_level' => '10', 'color' => '#2563eb', 'icon' => 'laptop', 'range' => [0, 7]],
            ['teacher_id' => $teacherMain->id, 'name' => 'Lập trình Python 11A', 'code' => 'PY11A', 'subject' => 'Tin học', 'grade_level' => '11', 'color' => '#059669', 'icon' => 'code', 'range' => [3, 11]],
            ['teacher_id' => $teacherMain->id, 'name' => 'Web cơ bản 12A', 'code' => 'WEB12A', 'subject' => 'Công nghệ', 'grade_level' => '12', 'color' => '#7c3aed', 'icon' => 'globe', 'range' => [0, 11]],
            ['teacher_id' => $teacherMath->id, 'name' => 'Toán 9 nâng cao', 'code' => 'TOAN9NC', 'subject' => 'Toán học', 'grade_level' => '9', 'color' => '#dc2626', 'icon' => 'calculator', 'range' => [0, 8]],
            ['teacher_id' => $teacherPhysics->id, 'name' => 'Vật lý 11B', 'code' => 'LY11B', 'subject' => 'Vật lý', 'grade_level' => '11', 'color' => '#0891b2', 'icon' => 'atom', 'range' => [2, 10]],
        ];

        return collect($rows)->map(function (array $row) use ($students) {
            $class = ClassModel::create([
                'teacher_id' => $row['teacher_id'],
                'name' => $row['name'],
                'code' => $row['code'],
                'description' => 'Lớp học dữ liệu demo sát vận hành thực tế, có bài kiểm tra, bài tập và chấm điểm.',
                'status' => 'active',
                'color' => $row['color'],
                'icon' => $row['icon'],
                'subject' => $row['subject'],
                'grade_level' => $row['grade_level'],
            ]);

            [$start, $end] = $row['range'];
            $class->students()->sync($students->slice($start, $end - $start + 1)->mapWithKeys(
                fn (User $student, int $offset) => [$student->id => ['joined_at' => now()->subDays(40 - $offset)]]
            )->all());

            return $class;
        })->values();
    }

    private function seedCourses(Collection $classes): Collection
    {
        $rows = [
            ['class' => 0, 'name' => 'Nhập môn máy tính', 'status' => 'published', 'icon' => 'monitor', 'color' => '#2563eb'],
            ['class' => 0, 'name' => 'Bảng tính và dữ liệu', 'status' => 'published', 'icon' => 'table', 'color' => '#0f766e'],
            ['class' => 1, 'name' => 'Python căn bản', 'status' => 'published', 'icon' => 'terminal', 'color' => '#16a34a'],
            ['class' => 2, 'name' => 'HTML CSS JavaScript', 'status' => 'published', 'icon' => 'braces', 'color' => '#7c3aed'],
            ['class' => 3, 'name' => 'Đại số lớp 9', 'status' => 'published', 'icon' => 'sigma', 'color' => '#dc2626'],
            ['class' => 4, 'name' => 'Điện học căn bản', 'status' => 'published', 'icon' => 'zap', 'color' => '#0891b2'],
        ];

        return collect($rows)->map(function (array $row) use ($classes) {
            $class = $classes[$row['class']];
            $course = Course::create([
                'teacher_id' => $class->teacher_id,
                'class_id' => $class->id,
                'name' => $row['name'],
                'description' => 'Khóa học gồm bài giảng, quiz định kỳ và bài tập theo chủ đề.',
                'cover_image' => null,
                'status' => $row['status'],
                'icon' => $row['icon'],
                'color' => $row['color'],
            ]);

            $course->students()->sync($class->students->mapWithKeys(
                fn (User $student, int $offset) => [$student->id => ['enrolled_at' => now()->subDays(30 - $offset)]]
            )->all());

            return $course;
        })->values();
    }

    private function seedFolders(User ...$teachers): array
    {
        $quizFolders = collect($teachers)->mapWithKeys(fn (User $teacher) => [
            $teacher->id => collect(['Kiểm tra 15 phút', 'Kiểm tra chương', 'Ôn tập cuối kỳ'])
                ->map(fn (string $name) => QuizFolder::create(['teacher_id' => $teacher->id, 'name' => $name])),
        ]);

        $questionFolders = collect($teachers)->mapWithKeys(fn (User $teacher) => [
            $teacher->id => collect(['Nhận biết', 'Thông hiểu', 'Vận dụng'])
                ->map(fn (string $name) => QuestionFolder::create(['teacher_id' => $teacher->id, 'name' => $name])),
        ]);

        return [$quizFolders, $questionFolders];
    }

    private function seedQuestionBank(User $teacherMain, Collection $folders): void
    {
        $bank = [
            ['folder' => 0, 'content' => 'RAM có vai trò gì trong máy tính?', 'type' => 'multiple_choice', 'options' => ['Lưu tạm thời dữ liệu', 'Lưu trữ vĩnh viễn', 'Xử lý âm thanh', 'Kết nối Internet'], 'answer' => '0'],
            ['folder' => 1, 'content' => 'Phát biểu đúng về biến trong Python là?', 'type' => 'multiple_choice', 'options' => ['Biến phải khai báo kiểu trước', 'Biến được tạo khi gán giá trị', 'Biến chỉ lưu số', 'Biến không đổi được'], 'answer' => '1'],
            ['folder' => 2, 'content' => 'Git dùng để quản lý phiên bản mã nguồn.', 'type' => 'true_false', 'options' => [], 'answer' => 'true'],
        ];

        foreach ($bank as $index => $item) {
            Question::create([
                'quiz_id' => null,
                'teacher_id' => $teacherMain->id,
                'folder_id' => $folders[$item['folder']]->id,
                'subject' => 'Tin học',
                'content' => $item['content'],
                'type' => $item['type'],
                'options' => $item['options'],
                'correct_answer' => $item['answer'],
                'points' => 1,
                'explanation' => 'Dữ liệu mẫu cho ngân hàng câu hỏi.',
                'order' => $index + 1,
            ]);
        }
    }

    private function seedQuizzes(User $teacherMain, User $teacherMath, User $teacherPhysics, Collection $classes, Collection $courses, Collection $quizFolders, Collection $students): Collection
    {
        $focusTeacherId = User::where('email', self::FOCUS_EMAIL)->value('id');
        $rows = [
            ['teacher' => $teacherMain, 'title' => 'Quiz nhập môn máy tính', 'class' => 0, 'course' => 0, 'folder' => 0, 'status' => 'published', 'quiz_type' => 'practice', 'start' => -6, 'end' => 14],
            ['teacher' => $teacherMain, 'title' => 'Kiểm tra bảng tính', 'class' => 0, 'course' => 1, 'folder' => 1, 'status' => 'published', 'quiz_type' => 'exam', 'start' => -3, 'end' => 7],
            ['teacher' => $teacherMain, 'title' => 'Python biến và kiểu dữ liệu', 'class' => 1, 'course' => 2, 'folder' => 1, 'status' => 'published', 'quiz_type' => 'exam', 'start' => -4, 'end' => 9],
            ['teacher' => $teacherMain, 'title' => 'JavaScript DOM mini test', 'class' => 2, 'course' => 3, 'folder' => 2, 'status' => 'published', 'quiz_type' => 'exam', 'start' => -1, 'end' => 11],
            ['teacher' => $teacherMain, 'title' => 'Quiz công khai Tin học', 'class' => null, 'course' => null, 'folder' => 2, 'status' => 'published', 'quiz_type' => 'practice', 'start' => -2, 'end' => 30, 'public' => true],
            ['teacher' => $teacherMath, 'title' => 'Đại số hệ phương trình', 'class' => 3, 'course' => 4, 'folder' => 1, 'status' => 'published', 'quiz_type' => 'exam', 'start' => -6, 'end' => 8],
            ['teacher' => $teacherPhysics, 'title' => 'Định luật Ohm', 'class' => 4, 'course' => 5, 'folder' => 0, 'status' => 'published', 'quiz_type' => 'exam', 'start' => -6, 'end' => 6],
        ];

        return collect($rows)->map(function (array $row, int $quizIndex) use ($classes, $courses, $quizFolders, $students, $focusTeacherId) {
            $teacher = $row['teacher'];
            $quiz = Quiz::create([
                'teacher_id' => $teacher->id,
                'folder_id' => $quizFolders[$teacher->id][$row['folder']]->id,
                'course_id' => $row['course'] === null ? null : $courses[$row['course']]->id,
                'class_id' => $row['class'] === null ? null : $classes[$row['class']]->id,
                'title' => $row['title'],
                'description' => 'Bộ đề phục vụ demo thực tế: có trộn câu hỏi, trộn đáp án, giới hạn thời gian.',
                'duration_minutes' => 30,
                'time_limit' => 30,
                'total_points' => 100,
                'passing_score' => 60,
                'max_attempts' => 1,
                'status' => $row['status'],
                'start_at' => now()->addDays($row['start']),
                'end_at' => now()->addDays($row['end']),
                'shuffle_questions' => true,
                'shuffle_answers' => true,
                'is_shuffle' => true,
                'show_result' => true,
                'quiz_type' => $row['quiz_type'],
                'anti_cheat_enabled' => true,
                'assigned_students' => null,
                'public_to_all_students' => (bool) ($row['public'] ?? false),
            ]);

            $this->seedQuizQuestions($quiz, $teacher->id);
            $targetStudents = $quiz->class_id ? $classes->firstWhere('id', $quiz->class_id)?->students : $students->take(6);
            $focusUser = User::where('email', self::FOCUS_EMAIL)->first();
            if ($focusUser && $targetStudents instanceof Collection && ! $targetStudents->contains('id', $focusUser->id)) {
                $targetStudents->prepend($focusUser);
            }

            foreach (($targetStudents ?? collect())->take(8)->values() as $i => $student) {
                $submitted = now()->subDays(rand(1, 8))->subMinutes(rand(5, 90));
                $score = rand(52, 96);
                DB::table('quiz_user')->updateOrInsert(
                    ['quiz_id' => $quiz->id, 'user_id' => $student->id],
                    [
                        'score' => $score,
                        'total_points' => 100,
                        'answers' => json_encode(['source' => 'seed', 'quiz_index' => $quizIndex, 'student_index' => $i], JSON_UNESCAPED_UNICODE),
                        'started_at' => (clone $submitted)->subMinutes(rand(15, 35)),
                        'submitted_at' => $submitted,
                        'is_graded' => true,
                        'shuffled_options' => json_encode([], JSON_UNESCAPED_UNICODE),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                if ($quiz->teacher_id === $focusTeacherId && $score < 65) {
                    Grade::create([
                        'student_id' => $student->id,
                        'gradable_type' => Quiz::class,
                        'gradable_id' => $quiz->id,
                        'score' => $score,
                        'feedback' => 'Cần xem lại phần vận dụng và quản lý thời gian làm bài.',
                        'grader_id' => $quiz->teacher_id,
                        'graded_at' => (clone $submitted)->addHours(3),
                    ]);
                }
            }

            if ($quiz->title === 'JavaScript DOM mini test') {
                $attemptId = DB::table('quiz_user')->where('quiz_id', $quiz->id)->value('id');
                $studentId = DB::table('quiz_user')->where('quiz_id', $quiz->id)->value('user_id');
                if ($attemptId && $studentId) {
                    QuizViolation::create([
                        'quiz_id' => $quiz->id,
                        'user_id' => $studentId,
                        'quiz_attempt_id' => $attemptId,
                        'event_type' => 'tab_switch',
                        'metadata' => ['count' => 2, 'note' => 'Rời màn hình khi làm bài'],
                        'occurred_at' => now()->subDay(),
                    ]);
                }
            }

            return $quiz;
        })->values();
    }

    private function seedQuizQuestions(Quiz $quiz, int $teacherId): void
    {
        $questions = [
            ['type' => 'multiple_choice', 'content' => 'CPU là thành phần nào trong máy tính?', 'options' => ['Bộ xử lý trung tâm', 'Bộ nhớ ngoài', 'Thiết bị nhập', 'Card màn hình'], 'answer' => '0'],
            ['type' => 'multiple_choice', 'content' => 'Công cụ nào dùng để tạo bảng tính?', 'options' => ['Word', 'Excel', 'PowerPoint', 'Paint'], 'answer' => '1'],
            ['type' => 'true_false', 'content' => 'HTML là ngôn ngữ lập trình backend.', 'options' => [], 'answer' => 'false'],
            ['type' => 'short_answer', 'content' => 'Nếu website tải chậm, em ưu tiên tối ưu phần nào trước?', 'options' => [], 'answer' => 'Tối ưu ảnh, giảm JS không cần thiết và dùng cache.'],
        ];

        foreach ($questions as $index => $item) {
            Question::create([
                'quiz_id' => $quiz->id,
                'teacher_id' => $teacherId,
                'folder_id' => null,
                'subject' => 'Tổng hợp',
                'content' => $item['content'],
                'type' => $item['type'],
                'options' => $item['options'],
                'correct_answer' => $item['answer'],
                'points' => 25,
                'explanation' => 'Dữ liệu mẫu cho phần giải thích đáp án.',
                'order' => $index + 1,
            ]);
        }
    }

    private function seedAssignments(User $teacherMain, User $teacherMath, User $teacherPhysics, Collection $classes, Collection $courses): Collection
    {
        $rows = [
            ['teacher' => $teacherMain, 'class' => 0, 'course' => 0, 'title' => 'Báo cáo tìm hiểu phần cứng cơ bản', 'type' => 'text', 'due' => 4, 'attachment' => 'assignments/mau-bao-cao-phan-cung.txt'],
            ['teacher' => $teacherMain, 'class' => 1, 'course' => 2, 'title' => 'Bài tập Python xử lý danh sách', 'type' => 'file', 'due' => 3, 'attachment' => 'assignments/mau-python-danh-sach.py'],
            ['teacher' => $teacherMain, 'class' => 2, 'course' => 3, 'title' => 'Thiết kế landing page giới thiệu bản thân', 'type' => 'online', 'due' => 6, 'attachment' => 'assignments/mau-landing-page.md'],
            ['teacher' => $teacherMath, 'class' => 3, 'course' => 4, 'title' => 'Bài tập hệ phương trình bậc nhất', 'type' => 'text', 'due' => 2, 'attachment' => null],
            ['teacher' => $teacherPhysics, 'class' => 4, 'course' => 5, 'title' => 'Báo cáo thực hành định luật Ohm', 'type' => 'file', 'due' => 5, 'attachment' => 'assignments/mau-bao-cao-phan-cung.txt'],
        ];

        return collect($rows)->map(function (array $row) use ($classes, $courses) {
            return Assignment::create([
                'teacher_id' => $row['teacher']->id,
                'class_id' => $classes[$row['class']]->id,
                'course_id' => $courses[$row['course']]->id,
                'title' => $row['title'],
                'description' => 'Mô tả chi tiết, tiêu chí chấm điểm và hướng dẫn nộp bài theo định dạng đã thống nhất trong lớp.',
                'attachment' => $row['attachment'],
                'type' => $row['type'],
                'due_at' => now()->addDays($row['due'])->setHour(23)->setMinute(0),
                'total_points' => 100,
            ]);
        })->values();
    }

    private function seedAssignmentSubmissionsAndGrades(Collection $assignments, User $focusUser, User ...$graders): void
    {
        foreach ($assignments as $index => $assignment) {
            $classStudents = ClassModel::find($assignment->class_id)?->students ?? collect();
            if (! $classStudents->contains('id', $focusUser->id)) {
                $classStudents->prepend($focusUser);
            }

            foreach ($classStudents->take(8)->values() as $i => $student) {
                $submittedAt = now()->subDays(rand(1, 10))->subHours(rand(0, 20));
                $submission = Submission::create([
                    'assignment_id' => $assignment->id,
                    'student_id' => $student->id,
                    'content' => 'Bài làm của ' . $student->name . ': em đã hoàn thành theo hướng dẫn và có bổ sung ví dụ thực tế.',
                    'attachment' => $assignment->type === 'file'
                        ? ($i % 2 === 0 ? 'submissions/bai-nop-mau-1.txt' : 'submissions/bai-nop-mau-2.txt')
                        : null,
                    'submitted_at' => $submittedAt,
                ]);

                if (($i + $index) % 5 !== 0) {
                    $score = rand(58, 96);
                    Grade::create([
                        'student_id' => $student->id,
                        'gradable_type' => Submission::class,
                        'gradable_id' => $submission->id,
                        'score' => $score,
                        'feedback' => $score >= 80
                            ? 'Bài làm tốt, trình bày rõ ràng và đúng trọng tâm.'
                            : 'Cần bổ sung giải thích và đối chiếu với yêu cầu đề bài để đạt điểm cao hơn.',
                        'grader_id' => $graders[array_rand($graders)]->id,
                        'graded_at' => (clone $submittedAt)->addHours(rand(6, 24)),
                    ]);
                }
            }
        }
    }

    private function seedQuizAttemptsAndGrades(Collection $quizzes, User $focusUser, User ...$graders): void
    {
        foreach ($quizzes as $quiz) {
            foreach (DB::table('quiz_user')->where('quiz_id', $quiz->id)->get() as $row) {
                if ($row->score !== null && $row->score < 60) {
                    Grade::firstOrCreate(
                        [
                            'student_id' => $row->user_id,
                            'gradable_type' => Quiz::class,
                            'gradable_id' => $quiz->id,
                        ],
                        [
                            'score' => (int) $row->score,
                            'feedback' => 'Cần xem lại các câu vận dụng và quản lý thời gian làm bài.',
                            'grader_id' => $graders[array_rand($graders)]->id,
                            'graded_at' => now()->subDay(),
                        ]
                    );
                }
            }
        }

        $publicQuiz = $quizzes->first(fn (Quiz $quiz) => $quiz->public_to_all_students);
        if ($focusUser && $publicQuiz) {
            DB::table('quiz_user')->updateOrInsert(
                ['quiz_id' => $publicQuiz->id, 'user_id' => $focusUser->id],
                [
                    'score' => 88,
                    'total_points' => 100,
                    'answers' => json_encode(['note' => 'focus-account-demo'], JSON_UNESCAPED_UNICODE),
                    'started_at' => now()->subDay()->subMinutes(35),
                    'submitted_at' => now()->subDay(),
                    'is_graded' => true,
                    'shuffled_options' => json_encode([], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedNotifications(User $teacherMain, Collection $students, Collection $assignments, Collection $quizzes, User $focusUser): void
    {
        Notification::create([
            'user_id' => $teacherMain->id,
            'type' => 'submission',
            'title' => 'Có bài nộp mới cần chấm',
            'body' => 'Lớp Tin học 10A1 vừa nộp thêm 2 bài tập.',
            'data' => ['assignment_id' => $assignments->first()?->id],
            'is_read' => false,
        ]);

        Notification::create([
            'user_id' => $teacherMain->id,
            'type' => 'quiz_result',
            'title' => 'Thống kê quiz mới cập nhật',
            'body' => 'Quiz Python biến và kiểu dữ liệu đã có thêm lượt nộp bài.',
            'data' => ['quiz_id' => $quizzes->firstWhere('title', 'Python biến và kiểu dữ liệu')?->id],
            'is_read' => true,
        ]);

        foreach ($students->take(8) as $student) {
            Notification::create([
                'user_id' => $student->id,
                'type' => 'assignment_assigned',
                'title' => 'Bạn có bài tập mới',
                'body' => 'Vui lòng vào mục Bài tập để xem đề bài và hạn nộp.',
                'data' => ['assignment_id' => $assignments->random()->id],
                'is_read' => rand(0, 1) === 1,
            ]);
        }

        Notification::create([
            'user_id' => $focusUser->id,
            'type' => 'grade_published',
            'title' => 'Điểm số mới đã được cập nhật',
            'body' => 'Bạn có điểm mới ở cả bài tập và bài kiểm tra. Vào mục Điểm số để xem chi tiết.',
            'data' => ['source' => 'seed-focus-student'],
            'is_read' => false,
        ]);
    }

    private function seedFocusUserLearningJourney(User $focusUser, User $teacherMain, Collection $quizzes, Collection $assignments): void
    {
        $quizIntro = $quizzes->firstWhere('title', 'Quiz nhập môn máy tính');
        $quizSpreadsheet = $quizzes->firstWhere('title', 'Kiểm tra bảng tính');
        $quizPython = $quizzes->firstWhere('title', 'Python biến và kiểu dữ liệu');

        if ($quizIntro) {
            DB::table('quiz_user')->updateOrInsert(
                ['quiz_id' => $quizIntro->id, 'user_id' => $focusUser->id],
                [
                    'score' => 92,
                    'total_points' => 100,
                    'answers' => json_encode(['q1' => '0', 'q2' => '1', 'q3' => 'false'], JSON_UNESCAPED_UNICODE),
                    'started_at' => now()->subDays(2)->subMinutes(28),
                    'submitted_at' => now()->subDays(2),
                    'is_graded' => true,
                    'shuffled_options' => json_encode([], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if ($quizSpreadsheet) {
            DB::table('quiz_user')->updateOrInsert(
                ['quiz_id' => $quizSpreadsheet->id, 'user_id' => $focusUser->id],
                [
                    'score' => null,
                    'total_points' => null,
                    'answers' => null,
                    'started_at' => now()->subMinutes(12),
                    'submitted_at' => null,
                    'is_graded' => false,
                    'shuffled_options' => json_encode([], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if ($quizPython) {
            // Force one "missed" quiz item on student dashboard/quiz list.
            $quizPython->update([
                'start_at' => now()->subDays(5),
                'end_at' => now()->subDay(),
            ]);
            DB::table('quiz_user')
                ->where('quiz_id', $quizPython->id)
                ->where('user_id', $focusUser->id)
                ->delete();
        }

        $assignmentIntro = $assignments->firstWhere('title', 'Báo cáo tìm hiểu phần cứng cơ bản');
        $assignmentPython = $assignments->firstWhere('title', 'Bài tập Python xử lý danh sách');
        $assignmentWeb = $assignments->firstWhere('title', 'Thiết kế landing page giới thiệu bản thân');

        if ($assignmentIntro) {
            $submission = Submission::updateOrCreate(
                ['assignment_id' => $assignmentIntro->id, 'student_id' => $focusUser->id],
                [
                    'content' => 'Em đã trình bày đầy đủ: CPU, RAM, SSD và ví dụ cấu hình máy học tập.',
                    'attachment' => 'submissions/bai-nop-mau-1.txt',
                    'submitted_at' => now()->subDays(3),
                ]
            );
            Grade::updateOrCreate(
                [
                    'student_id' => $focusUser->id,
                    'gradable_type' => Submission::class,
                    'gradable_id' => $submission->id,
                ],
                [
                    'score' => 89,
                    'feedback' => 'Bài làm tốt, có ví dụ thực tế và trình bày mạch lạc.',
                    'grader_id' => $teacherMain->id,
                    'graded_at' => now()->subDays(2),
                ]
            );
        }

        if ($assignmentPython) {
            $submission = Submission::updateOrCreate(
                ['assignment_id' => $assignmentPython->id, 'student_id' => $focusUser->id],
                [
                    'content' => 'Em nộp file xử lý danh sách, đã dùng vòng lặp và hàm tổng.',
                    'attachment' => 'submissions/bai-nop-mau-2.txt',
                    'submitted_at' => now()->subDay(),
                ]
            );
            Grade::where('student_id', $focusUser->id)
                ->where('gradable_type', Submission::class)
                ->where('gradable_id', $submission->id)
                ->delete();
        }

        if ($assignmentWeb) {
            // Keep as pending not submitted.
            Submission::where('assignment_id', $assignmentWeb->id)
                ->where('student_id', $focusUser->id)
                ->delete();
        }

        // Extra overdue assignment to ensure "quá hạn chưa nộp" status exists.
        if ($assignmentIntro) {
            $overdue = Assignment::firstOrCreate(
                [
                    'teacher_id' => $teacherMain->id,
                    'class_id' => $assignmentIntro->class_id,
                    'course_id' => $assignmentIntro->course_id,
                    'title' => 'Bài tập ôn tập đã quá hạn',
                ],
                [
                    'description' => 'Bài tập dùng để mô phỏng trạng thái quá hạn chưa nộp cho học sinh.',
                    'attachment' => null,
                    'type' => 'text',
                    'due_at' => now()->subDays(2),
                    'total_points' => 100,
                ]
            );
            Submission::where('assignment_id', $overdue->id)
                ->where('student_id', $focusUser->id)
                ->delete();
        }
    }

    private function seedVipAndPayments(User $teacherMain, Collection $students): void
    {
        foreach (VipPlan::defaults() as $row) {
            VipPlan::updateOrCreate(['audience' => $row['audience'], 'plan' => $row['plan']], $row);
        }

        $teacherSubscription = VipSubscription::create([
            'user_id' => $teacherMain->id,
            'plan' => 'yearly',
            'started_at' => now()->subMonths(2),
            'expires_at' => now()->addMonths(10),
            'status' => 'active',
        ]);

        VipPayment::create([
            'user_id' => $teacherMain->id,
            'vip_subscription_id' => $teacherSubscription->id,
            'txn_ref' => 'VQTEACHER' . now()->format('YmdHis'),
            'plan' => 'yearly',
            'amount' => 1668000,
            'bank_code' => 'VCB',
            'status' => 'paid',
            'vnp_transaction_no' => '79012345',
            'vnp_bank_code' => 'VCB',
            'vnp_response_code' => '00',
            'vnp_transaction_status' => '00',
            'paid_at' => now()->subMonths(2),
            'vnp_payload' => ['source' => 'demo-seed'],
        ]);

        $studentVip = $students->first();
        if ($studentVip) {
            $studentSubscription = VipSubscription::create([
                'user_id' => $studentVip->id,
                'plan' => 'monthly',
                'started_at' => now()->subDays(5),
                'expires_at' => now()->addDays(25),
                'status' => 'active',
            ]);

            VipPayment::create([
                'user_id' => $studentVip->id,
                'vip_subscription_id' => $studentSubscription->id,
                'txn_ref' => 'VQSTUDENT' . now()->format('YmdHis'),
                'plan' => 'monthly',
                'amount' => 19000,
                'bank_code' => 'MOMO',
                'status' => 'paid',
                'vnp_transaction_no' => '79012346',
                'vnp_bank_code' => 'MOMO',
                'vnp_response_code' => '00',
                'vnp_transaction_status' => '00',
                'paid_at' => now()->subDays(5),
                'vnp_payload' => ['source' => 'demo-seed'],
            ]);
        }
    }

    private function seedPromotions(): void
    {
        Promotion::create([
            'code' => 'TEACHER20',
            'name' => 'Ưu đãi giáo viên mới',
            'description' => 'Giảm 20% cho giáo viên nâng cấp gói Pro trong 30 ngày.',
            'vip_plan' => 'yearly',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'usage_limit' => 200,
            'used_count' => 32,
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->addDays(23),
            'status' => 'active',
        ]);

        Promotion::create([
            'code' => 'STUDENT10K',
            'name' => 'Hỗ trợ học sinh',
            'description' => 'Giảm trực tiếp 10.000đ cho gói tháng học sinh.',
            'vip_plan' => 'monthly',
            'discount_type' => 'fixed',
            'discount_value' => 10000,
            'usage_limit' => 500,
            'used_count' => 104,
            'starts_at' => now()->subDays(15),
            'ends_at' => now()->addDays(15),
            'status' => 'active',
        ]);
    }

    private function seedTickets(User $teacherMain, Collection $students): void
    {
        Ticket::create([
            'user_id' => $teacherMain->id,
            'category' => 'technical',
            'subject' => 'Cần tối ưu tốc độ tải trang Bài tập',
            'description' => 'Khi mở danh sách bài nộp lớn, trang có lúc tải chậm. Đề nghị tối ưu phân trang.',
            'status' => 'in_progress',
            'priority' => 'vip',
            'admin_response' => 'Đã tiếp nhận và đang tối ưu truy vấn.',
        ]);

        $student = $students->get(2);
        if ($student) {
            Ticket::create([
                'user_id' => $student->id,
                'category' => 'account',
                'subject' => 'Không nhận được email thông báo điểm',
                'description' => 'Tài khoản có điểm mới nhưng không thấy email thông báo.',
                'status' => 'open',
                'priority' => 'normal',
                'admin_response' => null,
            ]);
        }
    }
}
