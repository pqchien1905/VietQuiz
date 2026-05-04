<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Notification;
use App\Models\Question;
use App\Models\QuestionFolder;
use App\Models\Quiz;
use App\Models\QuizFolder;
use App\Models\Submission;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VipPayment;
use App\Models\VipSubscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class VietQuizDemoSeeder extends Seeder
{
    private const DEMO_EMAIL = 'pqchien1905@gmail.com';
    private const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        $this->resetDemoData();

        $demoUser = $this->createDemoUser();
        $students = $this->createStudents();

        $classes = $this->createClasses($demoUser, $students);
        $courses = $this->createCourses($demoUser, $classes, $students);
        $folders = $this->createFolders($demoUser);
        $quizzes = $this->createQuizzes($demoUser, $classes, $courses, $folders, $students);
        $assignments = $this->createAssignments($demoUser, $classes, $courses);

        $this->createSubmissionsAndGrades($demoUser, $students, $assignments);
        $this->createQuizAttemptsAndGrades($demoUser, $students, $quizzes);
        $this->createNotifications($demoUser, $students, $classes, $courses, $quizzes, $assignments);
        $this->createVipData($demoUser, $students);
        $this->createTickets($demoUser, $students);
        $this->createSoftDeletedData($demoUser, $students->first(), $classes->first(), $courses->first());
    }

    private function createDemoUser(): User
    {
        return User::create([
            'name' => 'Phạm Quang Chiến',
            'email' => self::DEMO_EMAIL,
            'email_verified_at' => now(),
            'password' => Hash::make(self::DEMO_PASSWORD),
            'role' => 'teacher',
            'phone' => '0901905190',
            'subject' => 'Tin học',
            'can_switch_role' => true,
            'last_active_role' => 'teacher',
        ]);
    }

    private function createStudents(): Collection
    {
        return collect([
            ['Nguyễn Minh Anh', 'student01@vietquiz.test', '0910000001'],
            ['Trần Gia Bảo', 'student02@vietquiz.test', '0910000002'],
            ['Lê Khánh Linh', 'student03@vietquiz.test', '0910000003'],
            ['Phạm Tuấn Kiệt', 'student04@vietquiz.test', '0910000004'],
            ['Hoàng Ngọc Mai', 'student05@vietquiz.test', '0910000005'],
            ['Đỗ Đức Huy', 'student06@vietquiz.test', '0910000006'],
            ['Vũ Thanh Trúc', 'student07@vietquiz.test', '0910000007'],
            ['Bùi Nhật Nam', 'student08@vietquiz.test', '0910000008'],
        ])->map(fn (array $student) => User::create([
            'name' => $student[0],
            'email' => $student[1],
            'email_verified_at' => now(),
            'password' => Hash::make(self::DEMO_PASSWORD),
            'role' => 'student',
            'phone' => $student[2],
            'can_switch_role' => false,
            'last_active_role' => 'student',
        ]));
    }

    private function createClasses(User $teacher, Collection $students): Collection
    {
        $classes = collect([
            [
                'name' => 'Tin học 12A1',
                'code' => 'TIN12A1',
                'description' => 'Lớp chính để test quản lý học sinh, khóa học, bài tập và bài kiểm tra.',
                'color' => '#2563eb',
                'icon' => 'book',
                'subject' => 'Tin học',
                'grade_level' => '12',
                'status' => 'active',
                'student_indexes' => [0, 1, 2, 3, 4],
            ],
            [
                'name' => 'Lập trình Web 11B2',
                'code' => 'WEB11B2',
                'description' => 'Lớp ôn tập HTML, CSS, JavaScript và thao tác DOM.',
                'color' => '#16a34a',
                'icon' => 'code',
                'subject' => 'Tin học',
                'grade_level' => '11',
                'status' => 'active',
                'student_indexes' => [2, 3, 4, 5, 6],
            ],
            [
                'name' => 'Cơ sở dữ liệu 10C3',
                'code' => 'DB10C3',
                'description' => 'Lớp demo trạng thái lưu trữ để test thùng rác và khôi phục.',
                'color' => '#f59e0b',
                'icon' => 'database',
                'subject' => 'Tin học',
                'grade_level' => '10',
                'status' => 'archived',
                'student_indexes' => [6, 7],
            ],
        ])->map(function (array $data) use ($teacher, $students) {
            $indexes = $data['student_indexes'];
            unset($data['student_indexes']);

            $class = ClassModel::create([
                'teacher_id' => $teacher->id,
                ...$data,
            ]);

            $class->students()->attach($students->only($indexes)->pluck('id')->all(), [
                'joined_at' => now()->subDays(20),
            ]);

            return $class;
        });

        $teacher->classes()->attach($classes->pluck('id')->all(), [
            'joined_at' => now()->subDays(15),
        ]);

        return $classes;
    }

    private function createCourses(User $teacher, Collection $classes, Collection $students): Collection
    {
        $courses = collect([
            [
                'class_id' => $classes[0]->id,
                'name' => 'HTML và CSS nền tảng',
                'description' => 'Xây dựng cấu trúc trang, định dạng giao diện và bố cục responsive.',
                'color' => '#2563eb',
                'icon' => 'layout',
                'status' => 'published',
                'student_indexes' => [0, 1, 2, 3, 4],
            ],
            [
                'class_id' => $classes[1]->id,
                'name' => 'JavaScript tương tác',
                'description' => 'Biến, hàm, DOM, sự kiện và xử lý biểu mẫu cơ bản.',
                'color' => '#16a34a',
                'icon' => 'zap',
                'status' => 'published',
                'student_indexes' => [2, 3, 4, 5, 6],
            ],
            [
                'class_id' => $classes[2]->id,
                'name' => 'SQL nhập môn',
                'description' => 'Bảng, khóa chính, truy vấn SELECT và lọc dữ liệu.',
                'color' => '#f59e0b',
                'icon' => 'database',
                'status' => 'draft',
                'student_indexes' => [6, 7],
            ],
        ])->map(function (array $data) use ($teacher, $students) {
            $indexes = $data['student_indexes'];
            unset($data['student_indexes']);

            $course = Course::create([
                'teacher_id' => $teacher->id,
                ...$data,
            ]);

            $course->students()->attach($students->only($indexes)->pluck('id')->all(), [
                'enrolled_at' => now()->subDays(18),
            ]);

            return $course;
        });

        $teacher->courses()->attach($courses->where('status', 'published')->pluck('id')->all(), [
            'enrolled_at' => now()->subDays(14),
        ]);

        return $courses;
    }

    private function createFolders(User $teacher): array
    {
        return [
            'quiz' => [
                'published' => QuizFolder::create(['teacher_id' => $teacher->id, 'name' => 'Đã giao cho lớp']),
                'draft' => QuizFolder::create(['teacher_id' => $teacher->id, 'name' => 'Đề nháp']),
                'review' => QuizFolder::create(['teacher_id' => $teacher->id, 'name' => 'Ôn tập nhanh']),
            ],
            'question' => [
                'html' => QuestionFolder::create(['teacher_id' => $teacher->id, 'name' => 'HTML CSS']),
                'js' => QuestionFolder::create(['teacher_id' => $teacher->id, 'name' => 'JavaScript']),
                'sql' => QuestionFolder::create(['teacher_id' => $teacher->id, 'name' => 'SQL']),
            ],
        ];
    }

    private function createQuizzes(User $teacher, Collection $classes, Collection $courses, array $folders, Collection $students): Collection
    {
        $quizDefinitions = [
            [
                'key' => 'html_exam',
                'folder_id' => $folders['quiz']['published']->id,
                'course_id' => $courses[0]->id,
                'class_id' => $classes[0]->id,
                'title' => 'Kiểm tra HTML và CSS',
                'description' => 'Đánh giá kiến thức về thẻ HTML, selector, box model và bố cục Flexbox.',
                'duration_minutes' => 35,
                'time_limit' => 35,
                'total_points' => 10,
                'passing_score' => 5,
                'max_attempts' => 1,
                'status' => 'published',
                'start_at' => now()->subDays(10),
                'end_at' => now()->addDays(10),
                'shuffle_questions' => true,
                'shuffle_answers' => true,
                'is_shuffle' => true,
                'show_result' => true,
                'quiz_type' => 'exam',
                'anti_cheat_enabled' => true,
                'question_folder' => $folders['question']['html']->id,
                'questions' => [
                    ['Thẻ HTML nào dùng để tạo liên kết?', 'multiple_choice', ['<a>', '<link>', '<href>', '<url>'], '<a>', 2, 'Thẻ <a> tạo hyperlink trong HTML.'],
                    ['Thuộc tính CSS nào dùng để đổi màu chữ?', 'multiple_choice', ['font-color', 'text-color', 'color', 'foreground'], 'color', 2, 'Thuộc tính color quy định màu chữ.'],
                    ['Box model gồm content, padding, border và margin.', 'true_false', ['Đúng', 'Sai'], 'Đúng', 2, 'Đây là bốn phần cơ bản của CSS box model.'],
                    ['Flexbox thường dùng để làm gì?', 'multiple_choice', ['Lưu dữ liệu', 'Căn chỉnh bố cục', 'Tạo database', 'Gửi email'], 'Căn chỉnh bố cục', 2, 'Flexbox giúp sắp xếp và căn chỉnh phần tử trong layout.'],
                    ['Viết tên thuộc tính CSS dùng để tạo khoảng cách bên trong phần tử.', 'short_answer', null, 'padding', 2, 'Padding là khoảng cách bên trong viền phần tử.'],
                ],
            ],
            [
                'key' => 'js_practice',
                'folder_id' => $folders['quiz']['review']->id,
                'course_id' => $courses[1]->id,
                'class_id' => $classes[1]->id,
                'title' => 'Mini quiz JavaScript DOM',
                'description' => 'Kiểm tra nhanh selector, event listener và cập nhật nội dung trang.',
                'duration_minutes' => 20,
                'time_limit' => 20,
                'total_points' => 10,
                'passing_score' => 5,
                'max_attempts' => 2,
                'status' => 'published',
                'start_at' => now()->subDays(3),
                'end_at' => now()->addDays(14),
                'shuffle_questions' => true,
                'shuffle_answers' => true,
                'is_shuffle' => true,
                'show_result' => true,
                'quiz_type' => 'practice',
                'anti_cheat_enabled' => false,
                'question_folder' => $folders['question']['js']->id,
                'questions' => [
                    ['Phương thức nào dùng để chọn phần tử theo id?', 'multiple_choice', ['queryById', 'getElementById', 'selectId', 'findId'], 'getElementById', 3, 'getElementById chọn phần tử theo id.'],
                    ['Sự kiện click có thể được lắng nghe bằng addEventListener.', 'true_false', ['Đúng', 'Sai'], 'Đúng', 2, 'addEventListener nhận tên sự kiện và callback xử lý.'],
                    ['Từ khóa nào khai báo biến có phạm vi khối trong JavaScript?', 'multiple_choice', ['var', 'let', 'global', 'define'], 'let', 3, 'let và const có phạm vi khối.'],
                    ['DOM là viết tắt của cụm từ nào?', 'short_answer', null, 'Document Object Model', 2, 'DOM biểu diễn tài liệu HTML dưới dạng cây đối tượng.'],
                ],
            ],
            [
                'key' => 'sql_draft',
                'folder_id' => $folders['quiz']['draft']->id,
                'course_id' => $courses[2]->id,
                'class_id' => $classes[2]->id,
                'title' => 'Đề nháp SQL cơ bản',
                'description' => 'Đề chưa xuất bản để test luồng publish/unpublish và chỉnh sửa câu hỏi.',
                'duration_minutes' => 25,
                'time_limit' => 25,
                'total_points' => 10,
                'passing_score' => 5,
                'max_attempts' => 1,
                'status' => 'draft',
                'start_at' => null,
                'end_at' => null,
                'shuffle_questions' => false,
                'shuffle_answers' => false,
                'is_shuffle' => false,
                'show_result' => false,
                'quiz_type' => 'exam',
                'anti_cheat_enabled' => true,
                'question_folder' => $folders['question']['sql']->id,
                'questions' => [
                    ['Lệnh SQL nào dùng để lấy dữ liệu?', 'multiple_choice', ['SELECT', 'INSERT', 'UPDATE', 'DELETE'], 'SELECT', 5, 'SELECT dùng để truy vấn dữ liệu.'],
                    ['Khóa chính có thể trùng lặp giữa hai bản ghi.', 'true_false', ['Đúng', 'Sai'], 'Sai', 5, 'Primary key phải định danh duy nhất mỗi bản ghi.'],
                ],
            ],
        ];

        return collect($quizDefinitions)->map(function (array $definition) use ($teacher, $students) {
            $questionFolder = $definition['question_folder'];
            $questions = $definition['questions'];
            $key = $definition['key'];
            unset($definition['question_folder'], $definition['questions'], $definition['key']);

            if ($key === 'js_practice') {
                $definition['assigned_students'] = $students->slice(2, 3)->pluck('id')->values()->all();
            }

            $quiz = Quiz::create([
                'teacher_id' => $teacher->id,
                ...$definition,
            ]);

            foreach ($questions as $index => [$content, $type, $options, $answer, $points, $explanation]) {
                Question::create([
                    'quiz_id' => $quiz->id,
                    'teacher_id' => $teacher->id,
                    'folder_id' => $questionFolder,
                    'subject' => $quiz->course?->name,
                    'content' => $content,
                    'type' => $type,
                    'options' => $options,
                    'correct_answer' => $answer,
                    'points' => $points,
                    'explanation' => $explanation,
                    'order' => $index + 1,
                ]);
            }

            return $quiz;
        });
    }

    private function createAssignments(User $teacher, Collection $classes, Collection $courses): Collection
    {
        return collect([
            [
                'class_id' => $classes[0]->id,
                'course_id' => $courses[0]->id,
                'title' => 'Tạo trang giới thiệu cá nhân',
                'description' => 'Xây dựng một trang HTML/CSS có ảnh đại diện, thông tin cá nhân, kỹ năng và liên hệ.',
                'type' => 'online',
                'due_at' => now()->addDays(5),
                'total_points' => 10,
            ],
            [
                'class_id' => $classes[1]->id,
                'course_id' => $courses[1]->id,
                'title' => 'Bài tập JavaScript DOM',
                'description' => 'Viết script thay đổi nội dung trang và xử lý sự kiện click trên nút.',
                'type' => 'text',
                'due_at' => now()->addDays(8),
                'total_points' => 10,
            ],
            [
                'class_id' => $classes[2]->id,
                'course_id' => $courses[2]->id,
                'title' => 'Lược đồ bảng học sinh',
                'description' => 'Mô tả bảng students với id, name, email và class_id.',
                'type' => 'file',
                'due_at' => now()->subDays(2),
                'total_points' => 10,
            ],
        ])->map(fn (array $assignment) => Assignment::create([
            'teacher_id' => $teacher->id,
            ...$assignment,
        ]));
    }

    private function createSubmissionsAndGrades(User $teacher, Collection $students, Collection $assignments): void
    {
        $submissionData = [
            [$assignments[0], $students[0], 'Em đã hoàn thành trang giới thiệu cá nhân gồm header, phần kỹ năng và form liên hệ.', 9, 'Bố cục rõ ràng, cần bổ sung responsive cho mobile.'],
            [$assignments[0], $students[1], 'Bài làm có đủ HTML, CSS và phần giới thiệu bản thân.', 8, 'Nội dung tốt, màu sắc nên đồng bộ hơn.'],
            [$assignments[1], $students[2], 'Em đã tạo nút bấm đổi nội dung và đổi màu nền bằng JavaScript.', 10, 'Hoàn thành tốt yêu cầu thao tác DOM.'],
            [$assignments[1], $students[3], 'Em nộp phần xử lý click và validate input.', null, null],
            [$assignments[2], $students[6], 'Em gửi mô tả bảng và khóa chính.', 7, 'Cần nêu rõ kiểu dữ liệu cho từng cột.'],
        ];

        foreach ($submissionData as [$assignment, $student, $content, $score, $feedback]) {
            $submission = Submission::create([
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
                'content' => $content,
                'submitted_at' => now()->subDays(rand(1, 4))->addMinutes(rand(10, 80)),
            ]);

            if ($score !== null) {
                Grade::create([
                    'student_id' => $student->id,
                    'gradable_type' => Submission::class,
                    'gradable_id' => $submission->id,
                    'score' => $score,
                    'feedback' => $feedback,
                    'grader_id' => $teacher->id,
                    'graded_at' => now()->subDay(),
                ]);
            }
        }
    }

    private function createQuizAttemptsAndGrades(User $teacher, Collection $students, Collection $quizzes): void
    {
        $attempts = [
            [$quizzes[0], $students[0], 8, ['<a>', 'color', 'Đúng', 'Căn chỉnh bố cục', 'margin']],
            [$quizzes[0], $students[1], 6, ['<a>', 'text-color', 'Đúng', 'Căn chỉnh bố cục', 'padding']],
            [$quizzes[0], $students[2], 10, ['<a>', 'color', 'Đúng', 'Căn chỉnh bố cục', 'padding']],
            [$quizzes[1], $students[2], 9, ['getElementById', 'Đúng', 'let', 'Document Object Model']],
            [$quizzes[1], $students[3], 5, ['queryById', 'Đúng', 'var', 'Document Object Model']],
        ];

        foreach ($attempts as [$quiz, $student, $score, $answers]) {
            $questionIds = $quiz->questions()->orderBy('order')->pluck('id')->values();
            $answerPayload = $questionIds->combine($answers)->all();

            DB::table('quiz_user')->insert([
                'quiz_id' => $quiz->id,
                'user_id' => $student->id,
                'score' => $score,
                'total_points' => $quiz->total_points,
                'answers' => json_encode($answerPayload, JSON_UNESCAPED_UNICODE),
                'started_at' => now()->subDays(2)->subMinutes($quiz->duration_minutes),
                'submitted_at' => now()->subDays(2),
                'is_graded' => true,
                'shuffled_options' => null,
                'created_at' => now()->subDays(2)->subMinutes($quiz->duration_minutes),
                'updated_at' => now()->subDays(2),
            ]);

            Grade::create([
                'student_id' => $student->id,
                'gradable_type' => Quiz::class,
                'gradable_id' => $quiz->id,
                'score' => $score,
                'feedback' => $score >= 8
                    ? 'Kết quả tốt, tiếp tục luyện thêm bài nâng cao.'
                    : 'Cần ôn lại các câu sai và làm lại phần kiến thức nền.',
                'grader_id' => $teacher->id,
                'graded_at' => now()->subDays(2)->addHour(),
            ]);
        }
    }

    private function createNotifications(User $teacher, Collection $students, Collection $classes, Collection $courses, Collection $quizzes, Collection $assignments): void
    {
        $notifications = [
            [$teacher, 'class_joined', 'Có học sinh mới trong lớp', $students[0]->name . ' vừa tham gia lớp ' . $classes[0]->name, ['class_id' => $classes[0]->id], false],
            [$teacher, 'submission_created', 'Có bài nộp mới', $students[3]->name . ' đã nộp bài ' . $assignments[1]->title, ['assignment_id' => $assignments[1]->id], false],
            [$students[0], 'quiz_assigned', 'Bài kiểm tra mới', 'Bạn có bài kiểm tra ' . $quizzes[0]->title . ' trong khóa ' . $courses[0]->name, ['quiz_id' => $quizzes[0]->id], false],
            [$students[0], 'grade_published', 'Điểm mới đã được công bố', 'Bài tập ' . $assignments[0]->title . ' đã có điểm và nhận xét.', ['assignment_id' => $assignments[0]->id], true],
            [$students[2], 'assignment_assigned', 'Bài tập JavaScript DOM', 'Giáo viên vừa giao bài tập mới, hạn nộp sau 8 ngày.', ['assignment_id' => $assignments[1]->id], false],
        ];

        foreach ($notifications as [$user, $type, $title, $body, $data, $isRead]) {
            Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'is_read' => $isRead,
            ]);
        }
    }

    private function createVipData(User $demoUser, Collection $students): void
    {
        $subscription = VipSubscription::create([
            'user_id' => $demoUser->id,
            'plan' => 'yearly',
            'status' => 'active',
            'started_at' => now()->subDays(12),
            'expires_at' => now()->addYear(),
        ]);

        VipPayment::create([
            'user_id' => $demoUser->id,
            'vip_subscription_id' => $subscription->id,
            'txn_ref' => 'VQVIP-DEMO-' . now()->format('YmdHis'),
            'plan' => 'yearly',
            'amount' => 199000,
            'bank_code' => 'NCB',
            'status' => 'paid',
            'vnp_transaction_no' => 'VNP' . now()->format('YmdHis'),
            'vnp_bank_code' => 'NCB',
            'vnp_response_code' => '00',
            'vnp_transaction_status' => '00',
            'paid_at' => now()->subDays(12),
            'vnp_payload' => ['demo' => true],
        ]);

        VipSubscription::create([
            'user_id' => $students[4]->id,
            'plan' => 'monthly',
            'status' => 'expired',
            'started_at' => now()->subMonths(2),
            'expires_at' => now()->subMonth(),
        ]);
    }

    private function createTickets(User $teacher, Collection $students): void
    {
        foreach ([
            [$students[0], 'quiz', 'Cần xem lại kết quả quiz', 'Em muốn xem lại câu trả lời sai trong bài HTML và CSS.', 'in_progress', 'normal'],
            [$students[2], 'grades', 'Thắc mắc điểm bài tập DOM', 'Em đã bổ sung phần xử lý sự kiện nhưng chưa thấy cập nhật điểm.', 'open', 'vip'],
            [$teacher, 'technical', 'Kiểm tra cấu hình gửi email', 'Tôi muốn xác nhận email thông báo giao bài đang hoạt động.', 'resolved', 'normal'],
        ] as [$user, $category, $subject, $description, $status, $priority]) {
            Ticket::create([
                'user_id' => $user->id,
                'category' => $category,
                'subject' => $subject,
                'description' => $description,
                'status' => $status,
                'priority' => $priority,
                'admin_response' => $status === 'resolved' ? 'Đã kiểm tra và cấu hình đang hoạt động.' : null,
            ]);
        }
    }

    private function createSoftDeletedData(User $teacher, User $student, ClassModel $class, Course $course): void
    {
        $assignment = Assignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'course_id' => $course->id,
            'title' => 'Bài tập đã xóa mềm',
            'description' => 'Dữ liệu dùng để test thùng rác và khôi phục.',
            'type' => 'text',
            'due_at' => now()->addDays(3),
            'total_points' => 10,
        ]);
        $assignment->delete();

        $notification = Notification::create([
            'user_id' => $student->id,
            'type' => 'system',
            'title' => 'Thông báo đã xóa mềm',
            'body' => 'Dữ liệu này dùng để test thùng rác thông báo.',
            'data' => [],
            'is_read' => true,
        ]);
        $notification->delete();
    }

    private function resetDemoData(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach ([
                'vip_payments',
                'vip_subscriptions',
                'tickets',
                'notifications',
                'grades',
                'submissions',
                'quiz_user',
                'questions',
                'assignments',
                'quizzes',
                'quiz_folders',
                'question_folders',
                'course_user',
                'class_user',
                'courses',
                'classes',
                'password_reset_tokens',
                'sessions',
            ] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            User::withTrashed()->forceDelete();
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
}
