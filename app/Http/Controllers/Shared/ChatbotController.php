<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function message(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $role = $user->isTeacher() ? 'teacher' : 'student';
        $message = trim($validated['message']);
        $normalized = Str::of(Str::ascii($message))->lower()->toString();

        $response = $this->matchResponse($normalized, $role);

        return response()->json([
            'reply' => $response['reply'],
            'actions' => $response['actions'],
            'topic' => $response['topic'],
        ]);
    }

    private function matchResponse(string $message, string $role): array
    {
        $knowledge = $role === 'teacher'
            ? $this->teacherKnowledge()
            : $this->studentKnowledge();

        foreach ($knowledge as $entry) {
            foreach ($entry['keywords'] as $keyword) {
                if (str_contains($message, $keyword)) {
                    return [
                        'topic' => $entry['topic'],
                        'reply' => $entry['reply'],
                        'actions' => $entry['actions'],
                    ];
                }
            }
        }

        return $this->fallbackResponse($role);
    }

    private function studentKnowledge(): array
    {
        return [
            [
                'topic' => 'quiz',
                'keywords' => ['quiz', 'kiem tra', 'bai thi', 'lam bai', 'nop bai kiem tra', 'het gio'],
                'reply' => 'Bạn vào Bài kiểm tra, chọn bài đang mở rồi nhấn Làm bài. Nếu bài có giới hạn thời gian, hãy kiểm tra kết nối mạng và chủ động nộp trước khi hết giờ. Sau khi nộp, kết quả trắc nghiệm thường hiển thị ngay nếu giáo viên đã bật chấm tự động.',
                'actions' => [
                    ['label' => 'Mở bài kiểm tra', 'url' => route('student.quizzes')],
                    ['label' => 'Xem điểm số', 'url' => route('student.grades')],
                ],
            ],
            [
                'topic' => 'assignment',
                'keywords' => ['bai tap', 'nop bai tap', 'deadline', 'han nop', 'file dinh kem', 'tai file'],
                'reply' => 'Bạn mở Bài tập, chọn bài được giao, nhập nội dung hoặc đính kèm file rồi gửi. Nếu giáo viên chưa chấm và bài chưa quá hạn, hệ thống cho phép cập nhật lại bài nộp.',
                'actions' => [
                    ['label' => 'Mở bài tập', 'url' => route('student.assignments')],
                    ['label' => 'Gửi hỗ trợ', 'url' => route('student.help')],
                ],
            ],
            [
                'topic' => 'class',
                'keywords' => ['lop', 'ma lop', 'tham gia lop', 'join', 'link moi', 'khoa hoc'],
                'reply' => 'Để tham gia lớp, bạn dùng mã lớp hoặc link mời do giáo viên cung cấp. Sau khi vào lớp, các khóa học và bài được giao sẽ xuất hiện trong khu vực học tập của bạn.',
                'actions' => [
                    ['label' => 'Tham gia lớp', 'url' => route('student.join-class')],
                    ['label' => 'Lớp học của tôi', 'url' => route('student.classes')],
                    ['label' => 'Khóa học', 'url' => route('student.courses')],
                ],
            ],
            [
                'topic' => 'grades',
                'keywords' => ['diem', 'ket qua', 'cham diem', 'bang diem', 'xem diem'],
                'reply' => 'Trang Điểm số tổng hợp kết quả quiz, bài tập đã chấm và các bài đang chờ giáo viên phản hồi. Nếu điểm chưa hiển thị, bài có thể đang chờ chấm hoặc giáo viên chưa công bố.',
                'actions' => [
                    ['label' => 'Xem điểm số', 'url' => route('student.grades')],
                    ['label' => 'Thông báo', 'url' => route('student.notifications')],
                ],
            ],
            [
                'topic' => 'account',
                'keywords' => ['tai khoan', 'mat khau', 'email', 'avatar', 'ho so', 'cai dat', 'thong bao'],
                'reply' => 'Bạn có thể cập nhật hồ sơ, đổi mật khẩu và tùy chỉnh thông báo trong Cài đặt. Nếu không đăng nhập được hoặc thông tin bị sai, hãy gửi ticket hỗ trợ kèm email tài khoản.',
                'actions' => [
                    ['label' => 'Cài đặt', 'url' => route('student.settings')],
                    ['label' => 'Hồ sơ', 'url' => route('student.profile')],
                    ['label' => 'Gửi hỗ trợ', 'url' => route('student.help')],
                ],
            ],
            [
                'topic' => 'vip',
                'keywords' => ['vip', 'goi', 'thanh toan', 'quang cao', 'nang cap'],
                'reply' => 'Gói VIP của học sinh tập trung vào trải nghiệm học mượt hơn, như bỏ quảng cáo nếu hệ thống đang bật quảng cáo. Bạn có thể xem gói, trạng thái và thanh toán trong trang VIP.',
                'actions' => [
                    ['label' => 'Trang VIP', 'url' => route('student.vip')],
                ],
            ],
        ];
    }

    private function teacherKnowledge(): array
    {
        return [
            [
                'topic' => 'class',
                'keywords' => ['lop', 'hoc sinh', 'ma lop', 'moi hoc sinh', 'danh sach', 'import hoc sinh'],
                'reply' => 'Bạn tạo lớp trong Lớp của Tôi, sau đó mời học sinh bằng mã lớp, link mời hoặc import danh sách. Khi học sinh tham gia, bạn có thể quản lý danh sách và đồng bộ sang khóa học.',
                'actions' => [
                    ['label' => 'Lớp của tôi', 'url' => route('teacher.classes')],
                    ['label' => 'Quản lý học sinh', 'url' => route('teacher.students')],
                ],
            ],
            [
                'topic' => 'quiz',
                'keywords' => ['quiz', 'kiem tra', 'tao de', 'xuat ban', 'cau hoi', 'tron dap an', 'ai'],
                'reply' => 'Để tạo bài kiểm tra, hãy vào Tạo bài kiểm tra, thiết lập tiêu đề, thời gian, lớp được giao và câu hỏi. Trước khi xuất bản, nên kiểm tra thang điểm, câu bắt buộc và chế độ trộn đáp án.',
                'actions' => [
                    ['label' => 'Tạo bài kiểm tra', 'url' => route('teacher.quiz-create')],
                    ['label' => 'Bài kiểm tra', 'url' => route('teacher.quizzes')],
                    ['label' => 'Ngân hàng câu hỏi', 'url' => route('teacher.questions')],
                ],
            ],
            [
                'topic' => 'assignment',
                'keywords' => ['bai tap', 'giao bai', 'nop bai', 'file', 'han nop', 'deadline'],
                'reply' => 'Bạn tạo bài tập trong mục Bài tập, chọn lớp hoặc khóa học liên quan, đặt hạn nộp và đính kèm tài liệu nếu cần. Học sinh nộp bài sẽ xuất hiện trong màn chấm theo từng bài tập.',
                'actions' => [
                    ['label' => 'Bài tập', 'url' => route('teacher.assignments')],
                    ['label' => 'Bài tập', 'url' => route('teacher.assignments')],
                ],
            ],
            [
                'topic' => 'grading',
                'keywords' => ['cham diem', 'diem', 'bai nop', 'phan hoi', 'xuat diem', 'grade'],
                'reply' => 'Bạn chấm trực tiếp trong mục Bài tập: mở bài cần chấm, chọn học sinh nộp bài, nhập điểm và phản hồi. Với quiz trắc nghiệm, hệ thống có thể tự tính điểm nếu đáp án đã được cấu hình đúng.',
                'actions' => [
                    ['label' => 'Bài tập', 'url' => route('teacher.assignments')],
                    ['label' => 'Phân tích', 'url' => route('teacher.analytics')],
                ],
            ],
            [
                'topic' => 'course',
                'keywords' => ['khoa hoc', 'bai hoc', 'dong bo', 'publish', 'xuat ban khoa'],
                'reply' => 'Khóa học dùng để gom nội dung học tập cho lớp. Bạn có thể tạo khóa, gắn học sinh từ lớp, xuất bản hoặc tạm ẩn khi cần chỉnh sửa.',
                'actions' => [
                    ['label' => 'Khóa học', 'url' => route('teacher.courses')],
                    ['label' => 'Lớp của tôi', 'url' => route('teacher.classes')],
                ],
            ],
            [
                'topic' => 'account',
                'keywords' => ['tai khoan', 'mat khau', 'email', 'ho so', 'cai dat', 'thong bao', 'doi vai tro'],
                'reply' => 'Bạn có thể cập nhật hồ sơ, đổi mật khẩu, cấu hình thông báo và chuyển sang màn học sinh trong menu tài khoản. Với lỗi tài khoản cần kiểm tra dữ liệu, hãy gửi ticket hỗ trợ.',
                'actions' => [
                    ['label' => 'Cài đặt', 'url' => route('teacher.settings')],
                    ['label' => 'Hồ sơ', 'url' => route('teacher.profile')],
                    ['label' => 'Gửi hỗ trợ', 'url' => route('teacher.help')],
                ],
            ],
            [
                'topic' => 'vip',
                'keywords' => ['vip', 'pro', 'goi', 'thanh toan', 'nang cap', 'enterprise'],
                'reply' => 'Gói Pro/VIP dành cho giáo viên mở rộng năng lực vận hành như dùng tính năng nâng cao và ưu tiên hỗ trợ. Bạn có thể xem quyền lợi, hạn dùng và thanh toán trong trang VIP.',
                'actions' => [
                    ['label' => 'Trang VIP', 'url' => route('teacher.vip')],
                ],
            ],
        ];
    }

    private function fallbackResponse(string $role): array
    {
        $helpRoute = $role === 'teacher' ? route('teacher.help') : route('student.help');
        $dashboardRoute = $role === 'teacher' ? route('teacher.dashboard') : route('student.dashboard');

        return [
            'topic' => 'fallback',
            'reply' => 'Mình chưa tìm thấy hướng dẫn thật sát với câu hỏi này. Bạn có thể hỏi cụ thể hơn, ví dụ: cách tạo quiz, nộp bài tập, xem điểm, tham gia lớp, đổi mật khẩu hoặc gửi ticket để đội hỗ trợ kiểm tra.',
            'actions' => [
                ['label' => 'Trung tâm trợ giúp', 'url' => $helpRoute],
                ['label' => 'Về bảng điều khiển', 'url' => $dashboardRoute],
            ],
        ];
    }
}
