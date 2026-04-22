# 🗺️ Lộ trình hoàn thiện VietQuiz - Laravel Web MVC Blade

> **Ngày tạo:** 22/04/2026
> **Trạng thái dự án:** Đang phát triển
> **Stack:** Laravel 13 + Blade + MySQL

---

## 📋 Tổng quan tình trạng dự án

| Thành phần | Trạng thái | Ghi chú |
|---|---|---|
| Authentication (Login/Register/Logout) | ✅ Hoàn chỉnh | Laravel Breeze |
| Teacher Dashboard | ✅ Hoàn chỉnh | Stats + navigation |
| Student Dashboard | ⚠️ Cần cập nhật dữ liệu thật | Controller đã có, view cần verify |
| Quiz tạo (Teacher) | ✅ Hoàn chỉnh | Quiz creation JS đã fix (22/04/2026) |
| Quiz làm (Student) | ✅ Hoàn chỉnh | Quiz-take + result pages (22/04/2026) |
| Assignment Teacher | ✅ Hoàn chỉnh | CRUD đầy đủ |
| Assignment Student | ⚠️ Thiếu form nộp bài | Chưa có submit form |
| Grading (Teacher) | ✅ Hoàn chỉnh | Grading view data that actually works (22/04/2026) |
| Analytics (Teacher) | ✅ Hoàn chỉnh | Analytics view with real data (22/04/2026) |
| Notifications | ✅ Hoàn chỉnh | Trigger cần thêm khi chấm điểm |
| Profile/Settings | ✅ Hoàn chỉnh | |
| Trash/Restore | ✅ Hoàn chỉnh | Soft delete |
| VIP Page | ⚠️ Placeholder | |
| Help Page | ⚠️ Placeholder | |
| Landing Page | ✅ Hoàn chỉnh | Với pricing, features, testimonials |

---

## 🔴 GIAI ĐOẠN 1: Sửa lỗi nghiêm trọng

**Ước tính:** 1 tuần
**Ưu tiên:** 🔴 Cao
**Trạng thái:** ✅ Hoàn thành (22/04/2026)

### 1.1 Schema lỗi Quiz/Question

**Vấn đề:** Bảng `questions` dùng cột `answer` nhưng controller dùng `correct_answer` và `correct_options` → khi tạo quiz sẽ lỗi hoặc lưu sai.

**Files bị ảnh hưởng:**
- `database/migrations/*questions*`
- `app/Http/Controllers/Teacher/QuestionController.php`
- `app/Http/Controllers/Teacher/QuizController.php`
- `app/Models/Question.php`

**Hành động:** ✅ ĐÃ HOÀN THÀNH
1. ~~Tạo migration thêm cột `correct_answer TEXT` vào bảng `questions`~~ (Schema đã đúng - chỉ cần sửa controller)
2. ✅ Sửa `QuestionController` - đổi `'answer'` → `'correct_answer'` trong validation và CSV import
3. ✅ Sửa `QuizController` - đổi `'answer'` → `'correct_answer'` trong validation và tạo câu hỏi
4. ✅ Thêm `class_id` vào QuizController::store() validation và create

### 1.2 Mass Assignment Protection

**Vấn đề:** Tất cả model thiếu `$fillable` đầy đủ → khi dùng `Model::create()` sẽ bị lỗi mass assignment.

**Files cần cập nhật `$fillable`:**
- `app/Models/Question.php` → ~~thêm `correct_answer`~~ ✅ Đã có sẵn
- `app/Models/Assignment.php` → ✅ Đã có sẵn
- `app/Models/Course.php` → ✅ Đã có sẵn
- `app/Models/Submission.php` → ✅ Đã có sẵn
- `app/Models/Grade.php` → ✅ Đã có sẵn
- `app/Models/Notification.php` → ✅ Đã có sẵn
- `app/Models/Quiz.php` → ✅ Đã có sẵn

**Trạng thái:** ✅ Tất cả models đã có `$fillable` đầy đủ - Không cần sửa

### 1.3 Quiz Creation JavaScript

**Vấn đề:** Form tạo quiz thiếu JavaScript xử lý thêm/sửa/xóa câu hỏi.

**File:** `resources/views/pages/teacher/quiz-create.blade.php`

**Hành động:** ✅ ĐÃ HOÀN THÀNH
- [x] Thêm JS để thêm/xóa câu hỏi động
- [x] Validate dữ liệu trước khi submit
- [x] Preview câu hỏi trước khi lưu
- [x] Xử lý loại câu hỏi (trắc nghiệm, tự luận, đúng/sai)
- [x] Modal sửa câu hỏi với đầy đủ fields
- [x] Tổng quan số câu hỏi, thời gian, điểm đạt
- [x] Nút Lưu Nháp / Xuất bản

---

## 🟡 GIAI ĐOẠN 2: Hoàn thiện Quiz cho Student

**Ước tính:** 2-3 tuần
**Ưu tiên:** 🔴 Rất cao (CORE FEATURE)

### 2.1 Quiz-Take Page (Student) - QUAN TRỌNG NHẤT

**File:** `resources/views/pages/student/quiz-take.blade.php`
**Controller:** `app/Http/Controllers/Student/QuizController.php`

**Giao diện mục tiêu:**

```
┌──────────────────────────────────────────────┐
│ Quiz: [Tên Quiz]              ⏱ 15:00      │
│ Câu 1/10                      [Nộp bài]    │
├──────────────────────────────────────────────┤
│ [Câu hỏi - text hoặc image]                 │
│                                              │
│ ○ Đáp án A                                   │
│ ○ Đáp án B                                   │
│ ○ Đáp án C                                   │
│ ○ Đáp án D                                   │
├──────────────────────────────────────────────┤
│ [Prev] [1] [2] [3]...[Next]                │
└──────────────────────────────────────────────┘
```

**Tính năng cần có:**

- [x] **Hiển thị câu hỏi** - từng lượt (1 câu/màn hình)
- [x] **Navigation** - prev/next + jump to question number
- [x] **Timer** - đếm ngược thời gian (hiển thị đẹp)
- [x] **Đánh dấu đã trả lời** - highlight số câu đã trả trên navigation
- [x] **Auto-submit** - khi hết giờ tự động nộp
- [x] **Xác nhận nộp** - popup confirm trước khi nộp
- [x] **Review câu hỏi** - xem lại tất cả câu trước khi nộp
- [x] **Hiển thị kết quả** - sau khi nộp → chuyển sang trang result

**Controller cần cập nhật:** ✅ ĐÃ CÓ SẴN
- `Student/QuizController@take` - trả đủ data (questions với options, time_limit)
- `Student/QuizController@submit` - xử lý submit, tạo Submission + Grade

**Trạng thái:** ✅ Hoàn thành (22/04/2026)

### 2.2 Quiz Result Page

**File:** `resources/views/pages/student/quiz-result.blade.php`

**Cần hiển thị:**
- [x] Điểm số / Tổng điểm (lớn, nổi bật)
- [x] Thời gian làm bài
- [x] Số câu đúng / sai / chưa trả lời
- [x] **Chi tiết từng câu** - đáp án đã chọn vs đáp án đúng
- [x] Nút "Làm lại" hoặc "Quay về khóa học"
- [x] Phân tích kết quả (thanh điểm đúng/sai/bỏ qua)
- [x] Tabs: Thống kê / Xem đáp án

**Trạng thái:** ✅ Hoàn thành (22/04/2026)

### 2.3 Quiz History (Student)

**File:** `resources/views/pages/student/quizzes.blade.php`

- [ ] Danh sách quiz đã làm
- [ ] Điểm số, thời gian, ngày làm
- [ ] Nút "Làm lại" nếu cho phép
- [ ] Nút "Xem lại bài"

### 2.4 Assignment Submission (Student)

**File:** `resources/views/pages/student/assignment-detail.blade.php`

**Cần có:**
- [ ] Nút "Nộp bài" (submit assignment)
- [ ] Upload file (PDF, DOCX, ZIP) - cần thêm file upload
- [ ] Nhập text trực tiếp (cho assignment dạng viết)
- [ ] Hiển thị deadline + thời gian còn lại
- [ ] Trạng thái: Đã nộp / Chưa nộp / Quá hạn
- [ ] Nút "Nộp lại" nếu cho phép
- [ ] Preview bài đã nộp

---

## 🟡 GIAI ĐOẠN 3: Hoàn thiện Analytics & Grading

**Ước tính:** 1-2 tuần
**Ưu tiên:** 🟡 Trung bình-cao

### 3.1 Analytics View (Teacher)

**File:** `resources/views/pages/teacher/analytics.blade.php`

**Controller đã có:** `app/Http/Controllers/Teacher/AnalyticsController.php`

Controller trả đủ data thật:
- `$scoreByClass` - điểm TB theo lớp
- `$distribution` - phân bố điểm (Giỏi/Khá/TB/Yếu)
- `$topStudents` - top 5 học sinh
- `$weeklyTrend` - xu hướng 6 tuần
- `$period` - filter week/month/quarter/year

**Cần làm:** ✅ ĐÃ HOÀN THÀNH (22/04/2026)
- [x] Render biểu đồ phân bố điểm (bar chart + donut SVG)
- [x] Render chart xu hướng tuần (bar chart)
- [x] Hiển thị top students với huy hiệu
- [x] Filter period (form buttons GET)
- [x] Export CSV (link đến route đã có)
- [x] Chi tiết theo lớp (table với dữ liệu thật)

### 3.2 Grading View (Teacher)

**File:** `resources/views/pages/teacher/grading.blade.php`

**Controller đã có:** `app/Http/Controllers/Teacher/GradingController.php`

**Cần làm:** ✅ ĐÃ HOÀN THÀNH (22/04/2026)
- [x] Tabs: Pending / Graded (JavaScript tabs)
- [x] Danh sách submissions chờ chấm (group by item)
- [x] Form chấm điểm inline (modal với validation)
- [x] AJAX submit (fetch POST)
- [x] Local data update (không cần reload)
- [x] Search filter
- [x] Export CSV (link đến route đã có)
- [x] Sửa điểm cho bài đã chấm

### 3.3 Notification tự động

**Cần thêm trigger notification khi:**
- [ ] Giao viên chấm điểm xong → notify student
- [ ] Giao viên tạo quiz mới → notify students trong lớp
- [ ] Giao viên giao bài tập mới → notify students
- [ ] Deadline approaching → remind students (cron job)

**Nơi thêm:**
- `app/Http/Controllers/Teacher/GradingController.php` - sau `Grade::create()`
- `app/Http/Controllers/Teacher/QuizController.php` - sau tạo quiz
- `app/Http/Controllers/Teacher/AssignmentController.php` - sau giao bài

---

## 🟢 GIAI ĐOẠN 4: Cải thiện UI/UX

**Ước tính:** 1-2 tuần
**Ưu tiên:** 🟡 Trung bình

### 4.1 Course Detail Page (Student)

**File:** `resources/views/pages/student/course-detail.blade.php`

**Cần thêm:**
- [ ] Tabs: Bài giảng / Bài tập / Quiz / Điểm số
- [ ] Danh sách bài học (có trạng thái completed)
- [ ] Nút "Làm Quiz" / "Nộp bài" cho từng item
- [ ] Progress bar (% hoàn thành)
- [ ] Mô tả khóa học

### 4.2 Class Detail Page (Teacher)

**File:** `resources/views/pages/teacher/class-detail.blade.php`

**Cần thêm:**
- [ ] Tabs: Bài tập / Quiz / Học sinh / Analytics
- [ ] Quản lý học sinh (thêm/xóa)
- [ ] Import học sinh bằng CSV
- [ ] Xem chi tiết học sinh (điểm, bài nộp)
- [ ] Gửi thông báo cho lớp

### 4.3 Student Dashboard

**File:** `resources/views/pages/student/dashboard.blade.php`

**Cần thêm real data:**
- [ ] Số quiz đã làm
- [ ] Điểm trung bình
- [ ] Bài tập pending (deadline sắp tới)
- [ ] Khóa học đang học
- [ ] Thống kê hoàn thành

### 4.4 VIP & Help Pages

**Files:**
- `resources/views/pages/teacher/vip.blade.php`
- `resources/views/pages/teacher/help.blade.php`
- `resources/views/pages/student/vip.blade.php`
- `resources/views/pages/student/help.blade.php`

**Cần thêm:**
- [ ] VIP page: Hiển thị tính năng premium, pricing, so sánh gói
- [ ] Help page: FAQ, hướng dẫn sử dụng, contact form
- [ ] Status badge cho user VIP

---

## 🔵 GIAI ĐOẠN 5: Tính năng nâng cao

**Ước tính:** 2-3 tuần
**Ưu tiên:** 🟢 Thấp (tùy nhu cầu)

### 5.1 Quiz Randomization
- [ ] Random thứ tự câu hỏi
- [ ] Random đáp án (đảo vị trí)
- [ ] Mỗi student nhận đề khác nhau
- [ ] Option: đề cố định / đề ngẫu nhiên

### 5.2 Quiz Anti-Cheat
- [ ] Disable copy/paste trong quiz
- [ ] Fullscreen mode khi làm bài
- [ ] Giới hạn thời gian chặt chẽ
- [ ] Chặn right-click / developer tools
- [ ] Log hành vi đáng nghi (nếu cần)

### 5.3 Quiz Attempt History
- [ ] Lưu lịch sử làm quiz (nhiều lần)
- [ ] So sánh điểm qua các lần
- [ ] Review lại bài cũ (xem đáp án)
- [ ] Biểu đồ tiến bộ theo thời gian

### 5.4 Assignment File Upload
- [ ] Upload file (PDF, DOCX, ZIP, ảnh)
- [ ] Preview file trong trình duyệt
- [ ] Giới hạn dung lượng (config)
- [ ] Scan virus cơ bản
- [ ] Lưu file vào storage (local/S3)

### 5.5 Automated Essay Grading (AI)
- [ ] Gợi ý điểm cho tự luận (dùng AI API)
- [ ] Feedback tự động bằng AI
- [ ] Plagiarism check (nếu cần)

### 5.6 Email Notifications
- [ ] Cấu hình SMTP trong .env
- [ ] Gửi email khi có điểm mới
- [ ] Email reminder deadline
- [ ] Email welcome khi đăng ký
- [ ] Email reset password (đã có Breeze)

### 5.7 Admin Panel
- [ ] Trang quản trị (quản lý users, classes toàn bộ)
- [ ] Dashboard tổng quan hệ thống
- [ ] Quản lý VIP subscriptions
- [ ] System settings (cấu hình app)
- [ ] Xem logs
- [ ] Backup database

### 5.8 Public Landing Page Enhancement
- [ ] Demo quiz (không cần đăng nhập)
- [ ] Pricing page chi tiết
- [ ] Testimonials thực tế
- [ ] Blog / Tin tức

---

## 🟣 GIAI ĐOẠN 6: Testing & Deployment

**Ước tính:** 2 tuần
**Ưu tiên:** 🟢 Thấp (trước deploy)

### 6.1 Testing
- [ ] Unit test cho Models
- [ ] Feature test cho Controllers
- [ ] Browser test với Laravel Dusk
- [ ] Test tất cả flows (login → tạo quiz → làm quiz → chấm điểm)

### 6.2 Performance Optimization
- [ ] Cache queries (Redis/file) cho dashboard stats
- [ ] Optimize images (resize, compress)
- [ ] Lazy load trong views
- [ ] Pagination cho danh sách lớn (students, submissions)
- [ ] Index database cho các column thường query

### 6.3 Security
- [ ] CSRF protection (đã có Laravel)
- [ ] XSS protection (đã có Blade)
- [ ] SQL injection protection (đã có Eloquent)
- [ ] Rate limiting cho quiz submit
- [ ] Authorization checks (`abort_unless`) cho tất cả controller
- [ ] Validate file upload (type, size)
- [ ] Sanitize user input

### 6.4 Deployment
- [ ] Chọn hosting (VPS/Shared/Laravel Forge/Render)
- [ ] SSL certificate
- [ ] Database backup strategy (tự động)
- [ ] Log monitoring
- [ ] Environment configuration
- [ ] Queue worker (cho email, notification)

---

## 📅 Tóm tắt thời gian

| Giai đoạn | Nội dung | Thời gian | Ưu tiên |
|---|---|---|---|
| **G1: Lỗi nghiêm trọng** | Schema, Mass Assignment, JS | 1 tuần | 🔴 Cao |
| **G2: Quiz Student** | Take quiz, Result, Assignment | 2-3 tuần | 🔴 Rất cao |
| **G3: Analytics & Grading** | Charts, Grading, Notifications | 1-2 tuần | 🟡 Trung bình-cao |
| **G4: UI/UX** | Pages, Dashboard, VIP/Help | 1-2 tuần | 🟡 Trung bình |
| **G5: Nâng cao** | Anti-cheat, AI, Email, Admin | 2-3 tuần | 🟢 Thấp |
| **G6: Testing & Deploy** | Test, Optimize, Deploy | 2 tuần | 🟢 Thấp |

**Tổng: 10-13 tuần** để có sản phẩm hoàn chỉnh production-ready.

---

## 🎯 Đề xuất thứ tự ưu tiên

```
1. G1 (tuần 1)    → Sửa lỗi schema ngay
                        ↓
2. G2 (tuần 2-3)  → Quiz cho student ← CORE FEATURE
                        ↓
3. G3 (tuần 3-4)  → Analytics + Grading
                        ↓
4. G4 (tuần 4-5)  → UI/UX
                        ↓
5. G5 (tuần 5-8)  → Nâng cao (tùy nhu cầu)
                        ↓
6. G6 (tuần 8-10) → Testing & Deploy
```

---

## 📁 Cấu trúc project hiện tại

```
VietQuiz/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                 # 8 API controllers
│   │   │   ├── Auth/                # 9 Breeze auth controllers
│   │   │   ├── Student/            # 12 student controllers
│   │   │   ├── Teacher/            # 14 teacher controllers
│   │   │   ├── ProfileController.php
│   │   │   └── Controller.php
│   │   └── Middleware/
│   │       └── CheckRole.php        # Custom role middleware
│   └── Models/                      # 10 Eloquent models
├── database/migrations/              # 18 migration files
├── resources/views/
│   ├── components/                  # Blade components
│   ├── layouts/                     # 5 layout templates
│   ├── pages/
│   │   ├── auth/                    # 6 auth views
│   │   ├── student/                # 15 student views
│   │   └── teacher/                # 15 teacher views
│   └── profile/
├── routes/
│   ├── web.php                      # Main web routes
│   ├── api.php                     # API routes
│   └── auth.php                    # Auth routes
├── composer.json
├── .env
└── bootstrap/app.php
```

---

## 🐛 Known Issues (cần fix)

- [x~~] **Schema lỗi** - `questions.answer` vs `questions.correct_answer` → ✅ Đã fix (22/04/2026)
- [ ] **Mass Assignment** - Models thiếu `$fillable` đầy đủ → ✅ Không cần fix (đã có sẵn)
- [x~~] **Quiz creation JS** - Form không hoạt động → ✅ Đã fix (22/04/2026)
- [ ] **Quiz-take page** - View trống
- [ ] **Analytics/Grading views** - Dùng data giả
- [ ] **Student assignment** - Chưa có form submit

---

## ✅ Checklist hoàn thành dự án

- [x] G1.1: Schema fix (QuestionController + QuizController)
- [x] G1.2: Mass assignment fix → Không cần (đã có sẵn)
- [x] G1.3: Quiz creation JS
- [x] G2: Quiz-take page (22/04/2026)
- [x] G2: Quiz result page (22/04/2026)
- [ ] G2: Quiz history
- [ ] G2: Assignment submission
- [x] G3: Analytics real data (22/04/2026)
- [x] G3: Grading real data + AJAX (22/04/2026)
- [ ] G3: Auto notifications
- [ ] G4: Course detail page
- [ ] G4: Class detail page
- [ ] G4: Student dashboard
- [ ] G4: VIP/Help pages
- [ ] G5: Quiz randomization
- [ ] G5: Anti-cheat
- [ ] G5: File upload
- [ ] G5: Email notifications
- [ ] G5: Admin panel
- [ ] G6: Testing
- [ ] G6: Security audit
- [ ] G6: Deployment

---

*Lần cuối cập nhật: 22/04/2026*
