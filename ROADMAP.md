# VietQuiz - Lộ trình hoàn thiện dự án

> **Ngày tạo:** 22/04/2026
> **Cập nhật lần cuối:** 04/05/2026
> **Trạng thái dự án:** Đang phát triển - giai đoạn hoàn thiện
> **Stack:** Laravel 13 + Blade + Tailwind CSS + Vite + MySQL
> **Roles:** Teacher, Student

---

## Tổng Quan

Dự án VietQuiz là một hệ thống quản lý học tập và thi trực tuyến tiếng Việt, hiện hoàn thành khoảng **85%** sau khi tháo phần admin panel để làm lại từ đầu. Core app cho giáo viên và học sinh vẫn hoạt động với dữ liệu thật: đăng nhập, phân quyền, lớp học, khóa học, quiz, bài tập, chấm điểm, analytics, thông báo, VIP, thùng rác và ticket hỗ trợ.

Các phần còn lại chủ yếu nằm ở cấu hình production, tính năng nhắc hạn, policy authorization, hardening bảo mật và deployment.

## Đã Hoàn Thành

- Authentication bằng Laravel Breeze, session-based.
- Phân quyền Teacher, Student bằng middleware `role`.
- Dashboard cho Teacher và Student.
- CRUD lớp học, khóa học, quiz, câu hỏi, bài tập.
- Student quiz-taking flow với timer, submit, result và chống thao tác gian lận cơ bản.
- Teacher grading cho quiz và assignment, có thông báo/email khi chấm.
- Analytics giáo viên và export dữ liệu.
- Student/Teacher help center và ticket submit.
- Soft delete/trash cho các phần dùng chung.
- VIP subscription/payment flow cơ bản.
- Performance indexes cơ bản.
- Rate limit cho route submit quiz.
- Admin panel đã được xóa để chuẩn bị làm lại từ đầu.

## Vừa Cập Nhật Ngày 04/05/2026

- Xóa toàn bộ route admin trong `routes/web.php`.
- Xóa `app/Http/Controllers/Admin`.
- Xóa `resources/views/layouts/admin.blade.php`.
- Xóa `resources/views/pages/admin`.
- Xóa test admin cũ.
- Chặn đăng nhập tài khoản `role=admin` trong lúc admin panel chưa có bản mới.
- Cập nhật `README.md` theo cấu trúc hiện tại.

## Việc Còn Lại Ưu Tiên Cao

### 1. Admin Panel Rebuild

Phần admin cũ đã được tháo ra để làm lại. Khi rebuild nên bắt đầu từ các module nhỏ:

- Admin authentication/authorization rõ ràng.
- Dashboard tổng quan.
- Quản lý users.
- Quản lý tickets.
- Quản lý subscriptions/payments.
- System settings.

### 2. SMTP Production Configuration

Hiện `.env` local có thể vẫn dùng mail log/array. Trước deploy cần cấu hình SMTP thật hoặc provider email.

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="noreply@vietquiz.vn"
MAIL_FROM_NAME="VietQuiz"
```

### 3. Quiz Attempt Review

Student đã xem được kết quả quiz, nhưng nên bổ sung trang review bài làm rõ ràng:

- Route: `student.quiz-review`
- Controller: `Student\QuizController@review`
- View: `resources/views/pages/student/quiz-review.blade.php`
- Chỉ hiển thị đáp án khi cấu hình quiz cho phép.

### 4. Deadline Reminder

Cần thêm command/scheduler để nhắc học sinh khi quiz hoặc assignment sắp hết hạn.

- Tạo command `SendDeadlineReminders`.
- Schedule trong `routes/console.php`.
- Tạo notification/email cho học sinh chưa nộp.

### 5. Authorization Policies

Nhiều controller đã có check ownership trực tiếp. Trước production nên gom dần sang policy:

- `QuizPolicy`
- `QuestionPolicy`
- `AssignmentPolicy`
- `ClassPolicy`
- `CoursePolicy`

Mục tiêu là giảm logic phân quyền lặp trong controller và tránh sót action.

### 6. File Upload Security Audit

Rà lại toàn bộ upload:

- Giới hạn size hợp lý.
- Kiểm tra extension và MIME type.
- Không dùng tên file gốc làm đường dẫn lưu trực tiếp.
- Tách private/public storage rõ ràng.

## Việc Còn Lại Ưu Tiên Trung Bình

- Student dashboard progress chart.
- Teacher dashboard mini chart.
- Quiz attempt history để so sánh điểm qua nhiều lần làm.
- Cache dashboard stats.
- Cloud storage nếu deploy thật với nhiều file upload.
- Database backup automation.
- System log viewer cho admin sau khi admin panel mới được dựng lại.

## Testing Và Deployment

Checklist trước deploy:

- `composer validate --strict`
- `php artisan test`
- `npm run build`
- `php artisan migrate --force` trên staging
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`
- Queue worker cho email/notification.
- Cron `php artisan schedule:run`.
- `APP_ENV=production`, `APP_DEBUG=false`.
- SSL, backup, log rotation.

## Thứ Tự Khuyến Nghị

1. Thiết kế lại admin panel.
2. Cấu hình SMTP thật.
3. Thêm Quiz Attempt Review.
4. Thêm Deadline Reminder.
5. Chuẩn hóa authorization policies.
6. Audit file upload.
7. Bổ sung charts/history.
8. Chuẩn bị deployment checklist.

## Tóm Tắt

| Nhóm | Trạng thái | Ghi chú |
|---|---|---|
| Core LMS | Hoàn thành phần chính | Quiz, assignment, grading, analytics đã có |
| Admin | Đã tháo ra | Sẽ làm lại từ đầu |
| Student | Gần hoàn thành | Cần thêm review/history nâng cao |
| Security | Đang hoàn thiện | Cần policy và file upload audit |
| Production | Chưa hoàn tất | Cần SMTP, scheduler, queue, deploy config |

**Ước tính còn lại: 4-6 tuần** để đạt mức production-ready tùy phạm vi admin panel mới.
