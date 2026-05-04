# VietQuiz Admin Rebuild Roadmap

## Mục đích

Tài liệu này là bước phân tích trước khi làm lại Admin. Mục tiêu không phải chỉ làm một vài bảng CRUD, mà xây dựng một Admin Console đủ sâu để vận hành toàn bộ VietQuiz: người dùng, lớp học, khóa học, quiz, ngân hàng câu hỏi, bài tập, chấm điểm, thông báo, hỗ trợ, VIP/thanh toán, cấu hình hệ thống và kiểm soát dữ liệu.

## Nguyên tắc trước khi triển khai

- Không mở rộng Admin bằng cách nhồi thêm toàn bộ logic vào một `AdminController`.
- Không tạo giao diện mới lệch khỏi design system hiện có. Admin phải dùng cùng `layouts.app`, sidebar/header/card/table/form/badge/modal/pagination hiện tại.
- Không phá luồng `teacher` và `student`. Admin chỉ quản trị, không thay đổi hành vi học/làm bài nếu chưa có yêu cầu rõ.
- Không dùng tài khoản học sinh/giáo viên để tự động vào Admin. `/admin` phải là vùng xác thực riêng.
- Mọi hành động thay đổi dữ liệu quan trọng cần có validation, xác nhận, thông báo kết quả và test.
- Admin cần ưu tiên khả năng quan sát, tìm kiếm, lọc, drill-down và xử lý hàng loạt hơn là chỉ hiển thị danh sách.

## Hiện trạng dự án

### Cấu trúc backend

- `app/Http/Controllers/Auth`: đăng ký, đăng nhập, quên mật khẩu, xác thực email.
- `app/Http/Controllers/Teacher`: dashboard, lớp học, học sinh, khóa học, quiz, câu hỏi, bài tập, chấm điểm, analytics.
- `app/Http/Controllers/Student`: dashboard, lớp học, khóa học, quiz, làm bài, bài tập, điểm.
- `app/Http/Controllers/Shared`: profile, settings, notifications, help, VIP, trash dùng chung theo vai trò.
- `app/Http/Controllers/Admin`: hiện chỉ có một `AdminController` lớn.
- `app/Services`: AI tạo câu hỏi, import câu hỏi từ file, trích xuất nội dung tài liệu.
- `app/Support`: helper phân trang collection và giới hạn VIP.

### Cấu trúc dữ liệu chính

- `users`: vai trò `admin`, `teacher`, `student`, soft delete, chuyển vai trò teacher/student.
- `classes`: lớp học, mã lớp, giáo viên, trạng thái `active/archived`, soft delete.
- `courses`: khóa học, giáo viên, lớp liên kết, trạng thái `draft/published`, soft delete.
- `quizzes`: quiz, giáo viên, lớp/khóa, folder, lịch mở/đóng, chống gian lận, loại quiz, soft delete.
- `questions`: câu hỏi thuộc quiz hoặc ngân hàng câu hỏi, folder, loại câu hỏi, đáp án, soft delete.
- `assignments`: bài tập, lớp/khóa, hạn nộp, tệp đính kèm, soft delete.
- `submissions`: bài nộp của học sinh cho bài tập.
- `grades`: điểm polymorphic cho quiz/assignment, người chấm, feedback.
- `notifications`: thông báo, trạng thái đọc, soft delete.
- `tickets`: yêu cầu hỗ trợ, trạng thái, ưu tiên VIP.
- `vip_subscriptions`, `vip_payments`: thuê bao VIP và giao dịch VNPay.
- Pivot: `class_user`, `course_user`, `quiz_user`.
- Folder: `quiz_folders`, `question_folders`.

### Cấu trúc frontend

- Layout chính: `resources/views/layouts/app.blade.php`.
- Layout dashboard: `resources/views/layouts/dashboard.blade.php`.
- Layout admin hiện tại: `resources/views/layouts/admin.blade.php`.
- CSS dùng chung: `resources/css/design-system.css`, `layout.css`, `components.css`, `app.css`.
- Component hiện có: card, stat card, table, form, input, badge, alert, modal, dropdown, pagination.

### Test hiện có

- Access control cho guest, student, teacher, admin web login.
- Auth, profile, role switch.
- Student: lớp, khóa, quiz list/take, help, notifications, settings, trash, VIP.
- Teacher: quản lý học sinh, lớp, khóa, quiz, bài tập, chấm điểm, smoke test các trang chính.
- Admin hiện tại mới có test đăng nhập, bảo vệ session, render trang con và phản hồi ticket.

## Đánh giá Admin hiện tại

Admin hiện tại đã có nền tối thiểu:

- `/admin` có đăng nhập riêng bằng `ADMIN_USERNAME` và `ADMIN_PASSWORD`.
- Dashboard có thống kê cơ bản.
- Có các trang users/classes/courses/quizzes/assignments/tickets/vip/system.
- Có thể đổi trạng thái một số bản ghi, soft delete/restore một số loại dữ liệu.
- Có test cơ bản.

Nhưng vẫn còn sơ sài so với hệ thống:

- Chỉ có một controller lớn, khó bảo trì khi mở rộng.
- Chưa có trang chi tiết cho user/class/course/quiz/assignment.
- Chưa có quản lý câu hỏi, ngân hàng câu hỏi, folder quiz/folder câu hỏi.
- Chưa xem được attempt quiz, đáp án, điểm, trạng thái chấm.
- Chưa xem được submission bài tập, tệp nộp, feedback, lịch sử chấm.
- Chưa có import/export, bulk actions, bộ lọc nâng cao.
- Chưa có quản lý thông báo toàn hệ thống hoặc gửi thông báo theo nhóm.
- Chưa có admin audit log cho hành động nhạy cảm.
- Chưa có quản lý cấu hình AI, VNPay, VIP limit, mail/queue/cache theo dạng vận hành.
- Chưa có phân quyền admin nội bộ, tất cả dựa vào một mật khẩu dùng chung.
- Giao diện đang giống bảng dữ liệu thô, thiếu trang tổng quan vận hành và drill-down.
- Chưa có trạng thái rỗng, lỗi, loading, responsive nâng cao đồng đều cho tất cả màn.
- Chưa có test đủ sâu cho CRUD, filter, bulk, phân quyền, dữ liệu liên kết và regression UI.

## Mục tiêu Admin hoàn chỉnh

### Dashboard vận hành

Admin dashboard cần trả lời nhanh:

- Hôm nay có bao nhiêu người dùng mới, lớp mới, quiz mới, bài nộp mới, ticket mới.
- Quiz nào đang mở, sắp mở, quá hạn hoặc có tỷ lệ nộp thấp.
- Giáo viên/học sinh nào hoạt động nhiều hoặc gặp lỗi nhiều.
- Ticket nào quá SLA, ticket VIP nào cần ưu tiên.
- Thanh toán nào đang pending, failed, cần đối soát.
- Queue/cache/storage/mail/AI/VNPay đang ở trạng thái nào.

### Quản trị dữ liệu

Admin phải quản lý được toàn bộ thực thể chính:

- Users
- Classes
- Courses
- Quizzes
- Questions
- Quiz attempts
- Assignments
- Submissions
- Grades
- Notifications
- Tickets
- VIP subscriptions
- VIP payments
- Trash
- System settings

### Trải nghiệm quản trị

- Mỗi module có index, detail, filters, quick actions.
- Các thao tác nguy hiểm có confirm rõ ràng.
- Có bulk actions cho danh sách lớn.
- Có phân trang đồng nhất bằng `components.pagination`.
- Có search/filter theo đúng nghiệp vụ từng module.
- Có empty state và error state rõ.
- Có navigation đủ sâu nhưng không rối.

## Kiến trúc đề xuất

### Controller

Tách `AdminController` thành các controller nhỏ:

- `Admin\AuthController`: login/logout admin.
- `Admin\DashboardController`: tổng quan vận hành.
- `Admin\UserController`: users, vai trò, trạng thái, hoạt động.
- `Admin\ClassController`: lớp, thành viên, khóa, quiz, bài tập.
- `Admin\CourseController`: khóa học, học sinh, quiz, bài tập.
- `Admin\QuizController`: quiz, câu hỏi, attempt, publish/close.
- `Admin\QuestionController`: ngân hàng câu hỏi, folder, import/export.
- `Admin\AssignmentController`: bài tập, submission, attachment.
- `Admin\GradeController`: điểm, feedback, export.
- `Admin\NotificationController`: thông báo hệ thống.
- `Admin\TicketController`: hỗ trợ, SLA, phản hồi.
- `Admin\VipController`: subscription, payment, VNPay.
- `Admin\TrashController`: dữ liệu đã xóa mềm.
- `Admin\SystemController`: cấu hình, health check.
- `Admin\AuditLogController`: lịch sử thao tác admin.

### Middleware và xác thực

Giai đoạn đầu có thể giữ session admin riêng:

- Session key riêng cho admin.
- Khi đang đăng nhập web guard teacher/student thì `/admin` luôn yêu cầu login admin.
- Login admin logout web guard.
- Logout admin chỉ xóa admin session.

Giai đoạn sau nên nâng cấp:

- Tạo guard hoặc middleware `admin`.
- Dùng tài khoản admin trong `users` hoặc bảng `admin_users`.
- Hash password, không lưu mật khẩu plain text trong `.env`.
- Hỗ trợ đổi mật khẩu admin trong giao diện.
- Có rate limit login admin.
- Có audit log login/logout/fail.

### Service layer

Tách nghiệp vụ dùng lại:

- `AdminDashboardMetrics`: gom số liệu dashboard.
- `AdminSearchFilters`: filter/query chuẩn cho từng module.
- `AdminBulkActionService`: bulk delete/restore/status.
- `AdminAuditLogger`: ghi log hành động.
- `SystemHealthService`: health check storage, queue, cache, mail, AI, VNPay.
- `AdminExportService`: CSV/XLSX export theo module.

### Views

Giữ cùng design system:

- `resources/views/layouts/admin.blade.php` dùng sidebar/header hiện tại.
- `resources/views/pages/admin/dashboard.blade.php`.
- `resources/views/pages/admin/{module}/index.blade.php`.
- `resources/views/pages/admin/{module}/show.blade.php`.
- `resources/views/pages/admin/{module}/partials/*.blade.php`.
- Dùng lại `components.pagination`, `components.modal`, badge/button/input/table/card.

## Lộ trình triển khai

### Phase 0 - Chuẩn hóa nền admin

Mục tiêu: làm nền vững trước khi thêm chức năng.

Việc cần làm:

- Tách controller admin hiện tại thành các controller theo module.
- Chuẩn hóa middleware `admin.session`.
- Chuẩn hóa route group `Route::prefix('admin')->name('admin.')->middleware(...)`.
- Tách CSS admin inline thành file hoặc partial có phạm vi rõ.
- Chuẩn hóa sidebar theo nhóm: Tổng quan, Học tập, Người dùng, Hỗ trợ, Thanh toán, Hệ thống.
- Chuẩn hóa layout responsive mobile.
- Chuẩn hóa flash message, error display, empty state.

Tiêu chí hoàn thành:

- Admin login/logout hoạt động đúng.
- Teacher/student không tự vào admin.
- Tất cả trang admin hiện có render OK.
- Không còn controller admin một file quá lớn.

Test:

- Admin auth test.
- Admin guest redirect/login page test.
- Teacher/student cannot access admin without admin login.
- Admin route smoke test.

### Phase 1 - Dashboard vận hành thực sự

Mục tiêu: dashboard không chỉ là số đếm mà là màn vận hành.

Việc cần làm:

- Thêm KPI theo thời gian: hôm nay, 7 ngày, 30 ngày.
- Thêm biểu đồ hoặc bảng trend cho users, quiz attempts, submissions, tickets, payments.
- Thêm danh sách cần xử lý:
  - Ticket open/in_progress quá lâu.
  - Thanh toán pending/failed.
  - Quiz đang mở nhưng ít lượt nộp.
  - Bài tập quá hạn có nhiều học sinh chưa nộp.
  - Giáo viên/học sinh bị khóa hoặc mới xóa.
- Thêm quick links đến detail module.

Tiêu chí hoàn thành:

- Dashboard giúp admin biết việc gì cần xử lý ngay.
- Số liệu tính từ database thật, không hardcode.
- Truy vấn có index hoặc giới hạn hợp lý.

Test:

- Dashboard metrics test.
- Dashboard urgent lists test.
- No N+1 query review ở các danh sách chính.

### Phase 2 - Quản lý người dùng hoàn chỉnh

Mục tiêu: admin quản lý vòng đời tài khoản.

Việc cần làm:

- Index nâng cấp:
  - Search name/email/phone.
  - Filter role, VIP, deleted, verified, created date.
  - Sort by created_at, last activity, class/course count, ticket count.
- Detail user:
  - Hồ sơ, vai trò, trạng thái, VIP.
  - Lớp đang tham gia hoặc lớp đang dạy.
  - Khóa học liên quan.
  - Quiz attempts.
  - Assignment submissions.
  - Grades.
  - Notifications.
  - Tickets.
  - Trash liên quan.
- Actions:
  - Update profile fields.
  - Reset password.
  - Verify/unverify email nếu cần.
  - Lock/unlock soft delete.
  - Convert role hoặc bật/tắt `can_switch_role`.
  - Grant/revoke VIP.
  - Send notification to user.
  - Export user data.

Tiêu chí hoàn thành:

- Admin nhìn được toàn bộ lịch sử học/dạy của một user.
- Không mất dữ liệu liên kết khi khóa tài khoản.
- Các action nhạy cảm có confirm và audit log.

Test:

- Update user.
- Lock/restore user.
- Role/can_switch_role update.
- User detail data visibility.
- Send notification to user.

### Phase 3 - Quản lý lớp học

Mục tiêu: admin quản lý được lớp như giáo viên nhưng ở cấp hệ thống.

Việc cần làm:

- Index:
  - Filter teacher, subject, grade_level, status, deleted.
  - Search name/code.
  - Sort theo số học sinh, số quiz, số bài tập.
- Detail class:
  - Thông tin lớp.
  - Giáo viên.
  - Học sinh.
  - Khóa học.
  - Quiz.
  - Bài tập.
  - Thông báo.
  - Lịch sử xóa/khôi phục.
- Actions:
  - Update thông tin lớp.
  - Archive/unarchive.
  - Add/remove student.
  - Transfer teacher.
  - Send notification to class.
  - Export student list.
  - Import students giống teacher flow.

Tiêu chí hoàn thành:

- Admin xử lý được lớp lỗi giáo viên không xử lý được.
- Không cho add user không phải student vào lớp.
- Transfer teacher không phá course/quiz/assignment.

Test:

- Class filter.
- Add/remove student.
- Transfer teacher.
- Archive/restore/delete.
- Export/import student list.

### Phase 4 - Quản lý khóa học

Mục tiêu: admin kiểm soát nội dung khóa học và ghi danh.

Việc cần làm:

- Index:
  - Filter teacher, class, status, deleted.
  - Search name/description.
- Detail course:
  - Info, owner, class.
  - Students.
  - Quizzes.
  - Assignments.
  - Enrollment history.
- Actions:
  - Update info.
  - Publish/unpublish.
  - Sync students from class.
  - Add/remove student.
  - Duplicate course.
  - Transfer teacher/class.
  - Soft delete/restore.

Tiêu chí hoàn thành:

- Admin sửa được liên kết sai giữa course/class/teacher.
- Publish/unpublish dùng đúng enum `draft/published`.

Test:

- Publish/unpublish.
- Sync students.
- Transfer class/teacher validation.
- Soft delete/restore.

### Phase 5 - Quiz, câu hỏi và attempt

Mục tiêu: đây là module trọng tâm, cần vượt xa bảng quiz hiện tại.

Việc cần làm:

- Quiz index:
  - Filter teacher, class, course, folder, status, quiz_type, open/closed/scheduled, anti_cheat.
  - Search title/description.
  - Bulk publish/close/delete/restore.
- Quiz detail:
  - Cấu hình quiz.
  - Danh sách câu hỏi.
  - Danh sách học sinh được giao.
  - Attempts từ `quiz_user`.
  - Thống kê điểm, tỷ lệ nộp, tỷ lệ đạt.
  - Lịch mở/đóng.
- Question management:
  - CRUD câu hỏi.
  - Folder quiz/question.
  - Bank questions where `quiz_id` null.
  - Import Excel/PDF/DOCX.
  - AI generation có kiểm tra VIP/feature.
  - Bulk move folder/delete.
- Attempt management:
  - Xem đáp án từng học sinh.
  - Xem shuffled options.
  - Reset attempt nếu admin cho phép.
  - Regrade objective questions.
  - Export attempts.

Tiêu chí hoàn thành:

- Admin thấy được vì sao học sinh không làm được quiz.
- Admin xử lý được quiz sai lịch, sai phân công, sai câu hỏi.
- Không làm hỏng logic chấm điểm hiện tại.

Test:

- Quiz filters.
- Question CRUD/import.
- Attempt detail.
- Reset/regrade attempt.
- Bulk actions.
- Student quiz take regression.

### Phase 6 - Bài tập, bài nộp và chấm điểm

Mục tiêu: admin theo dõi toàn bộ vòng đời bài tập.

Việc cần làm:

- Assignment index:
  - Filter teacher, class, course, type, due status, deleted.
  - Search title.
- Assignment detail:
  - Info.
  - Submissions.
  - Attachment preview/download.
  - Grades.
  - Missing submissions.
- Submission management:
  - Xem bài nộp.
  - Download attachment.
  - Mark late/missing nếu cần.
  - Admin hỗ trợ sửa lỗi submission.
- Grade management:
  - Xem grade polymorphic quiz/assignment.
  - Sửa điểm/feedback có audit log.
  - Export gradebook.

Tiêu chí hoàn thành:

- Admin biết ai chưa nộp, ai đã nộp, ai đã chấm.
- Tệp đính kèm hoạt động đúng như teacher grading.

Test:

- Assignment filters.
- Submission detail.
- Grade update.
- Attachment access.
- Export gradebook.

### Phase 7 - Notifications và ticket support

Mục tiêu: admin xử lý hỗ trợ và truyền thông hệ thống.

Việc cần làm:

- Notification center:
  - List notifications.
  - Filter user/type/read/deleted.
  - Send to one user, role, class, course.
  - Preview before send.
  - Soft delete/restore notifications.
- Ticket center:
  - Inbox theo trạng thái, priority, category, role.
  - SLA/age badges.
  - Detail ticket có lịch sử phản hồi.
  - Internal note nếu cần.
  - Reply template.
  - Auto notify user khi status hoặc response thay đổi.
  - Bulk close/assign priority.

Tiêu chí hoàn thành:

- Ticket VIP được ưu tiên rõ.
- Phản hồi admin tạo notification đúng.
- Admin không phải vào từng màn student/teacher để xử lý hỗ trợ.

Test:

- Send notification.
- Ticket response notification.
- Status transitions.
- Filters and bulk close.

### Phase 8 - VIP, thanh toán và cấu hình thương mại

Mục tiêu: admin vận hành VIP/VNPay chính xác.

Việc cần làm:

- VIP dashboard:
  - Active/expired/cancelled subscriptions.
  - Revenue paid by period.
  - Pending/failed payments.
  - Conversion by role.
- Subscription detail:
  - User, plan, dates, status.
  - Extend/cancel/reactivate.
  - Manual grant/revoke.
- Payment detail:
  - VNPay transaction data.
  - Payload raw.
  - Bank code/response code.
  - Mark paid only with audit reason.
- Settings:
  - Display current VNPay config status without revealing secrets.
  - Plan metadata and limits should be centralized.

Tiêu chí hoàn thành:

- Admin đối soát được thanh toán lỗi.
- Không lộ secret VNPay.
- Không tự ý sửa `paid_at` thiếu audit.

Test:

- Update subscription.
- Payment status transitions.
- VNPay return/IPN regression.
- Secret masking.

### Phase 9 - Trash, audit log và an toàn dữ liệu

Mục tiêu: admin có khả năng khôi phục và kiểm tra hành động.

Việc cần làm:

- Trash center:
  - Gom users/classes/courses/quizzes/questions/assignments/notifications.
  - Filter type, owner, deleted_at.
  - Restore selected/all.
  - Force delete selected/all với confirm mạnh.
- Audit log:
  - Ghi admin_id/session, action, target_type, target_id, before/after, ip, user agent.
  - Xem log theo module/user/action/date.
  - Không cho sửa audit log từ UI.
- Data safety:
  - Chặn force delete khi còn liên kết nguy hiểm nếu chưa có rule rõ.
  - Các bulk actions phải báo số bản ghi ảnh hưởng.

Tiêu chí hoàn thành:

- Mọi action nhạy cảm có dấu vết.
- Admin có thể phục hồi dữ liệu do xóa nhầm.

Test:

- Restore/force delete từng loại.
- Audit log created on admin actions.
- Bulk action validation.

### Phase 10 - System health và cấu hình vận hành

Mục tiêu: admin thấy tình trạng ứng dụng.

Việc cần làm:

- Health checks:
  - PHP/Laravel env.
  - Database connection.
  - Storage writable.
  - Queue driver.
  - Cache driver.
  - Mail config.
  - AI question API config.
  - LibreOffice path.
  - VNPay config.
- Maintenance actions:
  - Clear cache/config/view nếu được phép.
  - Queue failed jobs list.
  - Download simple diagnostics report.
- Security:
  - Mask secrets.
  - Chỉ hiển thị đủ để vận hành, không lộ `.env`.

Tiêu chí hoàn thành:

- Admin biết hệ thống thiếu config nào.
- Không có thông tin nhạy cảm bị render ra HTML.

Test:

- Health check status.
- Secret masking.
- System page render on missing configs.

## Thiết kế giao diện Admin đề xuất

### Sidebar

Nhóm menu:

- Tổng quan
  - Dashboard
  - Việc cần xử lý
- Người dùng
  - Tài khoản
  - Vai trò & VIP
- Học tập
  - Lớp học
  - Khóa học
  - Quiz
  - Ngân hàng câu hỏi
  - Bài tập
  - Điểm & bài nộp
- Vận hành
  - Thông báo
  - Hỗ trợ
  - Thanh toán
- Dữ liệu
  - Thùng rác
  - Audit log
- Hệ thống
  - Health
  - Cấu hình

### Mẫu trang index

Mỗi index nên có:

- Page header rõ ràng.
- KPI cards nhỏ.
- Toolbar search/filter/sort.
- Bulk selection.
- Table có cột trạng thái, owner, liên kết chính, updated_at.
- Row action ngắn: xem, sửa nhanh, khóa/xóa/khôi phục.
- Empty state.
- Pagination.

### Mẫu trang detail

Mỗi detail nên có:

- Header với tên đối tượng, badge trạng thái, action chính.
- Tabs: Tổng quan, Liên kết, Hoạt động, Dữ liệu liên quan, Lịch sử.
- Timeline hoặc audit section.
- Các card nhỏ nhưng không lồng card quá nhiều.

## Route dự kiến

Ví dụ route group sau khi tách:

```php
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('users', UserController::class)->only(['index', 'show', 'update']);
    Route::post('users/{user}/lock', [UserController::class, 'lock'])->name('users.lock');
    Route::post('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');

    Route::resource('classes', ClassController::class)->only(['index', 'show', 'update']);
    Route::resource('courses', CourseController::class)->only(['index', 'show', 'update']);
    Route::resource('quizzes', QuizController::class)->only(['index', 'show', 'update']);
    Route::resource('questions', QuestionController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::resource('assignments', AssignmentController::class)->only(['index', 'show', 'update']);

    Route::get('grades', [GradeController::class, 'index'])->name('grades.index');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('vip', [VipController::class, 'index'])->name('vip.index');
    Route::get('trash', [TrashController::class, 'index'])->name('trash.index');
    Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('system', [SystemController::class, 'index'])->name('system.index');
});
```

## Test roadmap

### Feature tests bắt buộc

- Admin auth:
  - Guest thấy login admin.
  - Sai mật khẩu bị reject.
  - Login đúng vào dashboard.
  - Teacher/student đang đăng nhập vẫn phải login admin.
- Dashboard:
  - Metrics đúng dữ liệu.
  - Urgent cards đúng.
- Users:
  - Filter/search.
  - Detail render.
  - Lock/restore/update.
- Classes/courses:
  - Update status.
  - Transfer owner.
  - Add/remove student.
- Quizzes/questions:
  - Filter.
  - Detail render.
  - Attempt render.
  - Question CRUD/import.
- Assignments/submissions/grades:
  - Detail render.
  - Grade update.
  - Attachment access.
- Notifications/tickets:
  - Send notification.
  - Ticket response creates notification.
- VIP/payment:
  - Update subscription.
  - Payment status update.
  - VNPay regression.
- Trash/audit:
  - Restore/force delete.
  - Audit log is created.
- System:
  - Secret masking.
  - Missing config does not crash.

### Verification commands

```bash
php artisan route:list --except-vendor
php artisan test
composer validate --strict
npm run build
```

## Thứ tự ưu tiên đề xuất

1. Tách controller/middleware/layout nền Admin.
2. Làm dashboard vận hành thật.
3. Làm user detail vì mọi module đều liên kết user.
4. Làm class/course detail để có khung học tập.
5. Làm quiz/question/attempt vì đây là lõi hệ thống.
6. Làm assignment/submission/grade.
7. Làm notification/ticket.
8. Làm VIP/payment.
9. Làm trash/audit log.
10. Làm system health/config.

## Những quyết định cần chốt trước khi code

- Admin sẽ tiếp tục dùng mật khẩu `.env` hay chuyển sang tài khoản `users.role = admin`?
- Có cần nhiều admin với phân quyền khác nhau không?
- Có cho admin chỉnh trực tiếp điểm/attempt không, hay chỉ xem và reset?
- Có cho force delete dữ liệu học tập không, hay chỉ soft delete/restore?
- Admin có cần import/export Excel cho mọi module ngay phase đầu không?
- Có cần audit log bắt buộc trước khi cho phép các action nhạy cảm không?
- Có cần quản lý plan VIP động trong database không, hay giữ hardcode theo `VipFeature`?

## Kết luận kỹ thuật

Admin hiện tại là bản khởi động hợp lệ nhưng chưa tương xứng với phạm vi của VietQuiz. Cách làm đúng tiếp theo là không tiếp tục vá từng bảng, mà tái cấu trúc Admin thành một console vận hành theo module, có detail pages, search/filter mạnh, bulk actions, audit log, trash center, và test đầy đủ. Sau khi tài liệu này được duyệt, việc triển khai nên bắt đầu từ Phase 0 để tránh càng làm càng khó bảo trì.
