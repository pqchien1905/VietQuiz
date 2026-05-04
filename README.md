# VietQuiz - Hệ thống Thi Trực Tuyến

<p align="center">
    <img src="https://img.shields.io/badge/Laravel-13.x-red?style=flat-square&logo=laravel" alt="Laravel">
    <img src="https://img.shields.io/badge/PHP-8.4-blue?style=flat-square&logo=php" alt="PHP">
    <img src="https://img.shields.io/badge/MySQL-8.x-blue?style=flat-square&logo=mysql" alt="MySQL">
</p>

## Giới thiệu

**VietQuiz** là hệ thống thi trực tuyến và quản lý học tập được xây dựng bằng **Laravel 13** và **Blade**, hỗ trợ hai vai trò chính:

- **Giáo viên**: Tạo lớp học, khóa học, bài kiểm tra (quiz), bài tập. Chấm điểm và xem phân tích kết quả học sinh.
- **Học sinh**: Đăng ký lớp, làm bài kiểm tra, nộp bài tập, xem điểm và kết quả.
- **Admin**: Quản trị toàn bộ hệ thống tại `/admin` với tài khoản mặc định `admin` / `password`.

## Tính năng chính

### Giáo viên
- Dashboard với thống kê tổng quan
- Quản lý lớp học, khóa học
- Tạo bài kiểm tra (quiz) với nhiều loại câu hỏi: trắc nghiệm, đúng/sai, tự luận
- Tạo bài tập và chấm điểm
- Xem phân tích kết quả học sinh (analytics)
- Gửi thông báo cho học sinh
- Quản lý thùng rác (soft delete)

### Học sinh
- Dashboard cá nhân
- Tham gia lớp học
- Làm bài kiểm tra với timer
- Nộp bài tập
- Xem điểm số và kết quả
- Nhận thông báo

### Admin
- Trang đăng nhập riêng tại `/admin`
- Dashboard tổng quan người dùng, lớp, khóa, quiz, bài tập, ticket và VIP
- Quản lý người dùng, lớp học, khóa học, quiz, bài tập
- Phản hồi ticket hỗ trợ và gửi thông báo cho người dùng
- Theo dõi VIP, thanh toán và thông tin hệ thống

## Tech Stack

| Thành phần | Công nghệ |
|---|---|
| Backend | Laravel 13.x |
| Frontend | Blade + Vanilla JS |
| Database | MySQL 8.x |
| Auth | Laravel Breeze (session-based) |
| CSS | Tailwind CSS + Custom CSS Variables |
| Icons | Inline SVG |
| Import/Export | PhpSpreadsheet |

## Yêu cầu hệ thống

- **PHP**: 8.3+
- **Composer**: 2.x
- **MySQL**: 8.0+
- **Node.js**: 18+ (để build assets)

## Cài đặt

```bash
# 1. Clone repository
git clone https://github.com/pqchien1905/VietQuiz.git
cd VietQuiz

# 2. Cài đặt dependencies
composer install
npm install

# 3. Tạo file .env
cp .env.example .env

# 4. Cấu hình database trong .env
# DB_DATABASE=vietquiz
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Tạo app key
php artisan key:generate

# 6. Chạy migrations
php artisan migrate

# 7. (Tùy chọn) Seed dữ liệu mẫu
php artisan db:seed

# 8. Tạo storage link
php artisan storage:link

# 9. Chạy server
php artisan serve

# Kiểm tra nhanh
php artisan test
npm run build
```

## Cấu trúc dự án

```
VietQuiz/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # Admin system console
│   │   │   ├── Auth/           # Laravel Breeze auth
│   │   │   ├── Shared/         # Help, notifications, profile, settings, VIP, trash
│   │   │   ├── Student/        # Student controllers
│   │   │   └── Teacher/        # Teacher controllers
│   │   └── Middleware/
│   │       └── CheckRole.php   # Role-based access
│   ├── Mail/                   # Email notifications
│   ├── Models/                 # Eloquent models
│   ├── Services/               # Import/extract/generate services
│   └── Support/                # Shared support helpers
├── database/
│   └── migrations/             # Database schema
├── resources/
│   ├── css/                    # App styles
│   ├── js/                     # App scripts
│   └── views/
│       ├── components/         # Reusable Blade components
│       ├── emails/             # Email templates
│       ├── layouts/            # Layout templates
│       └── pages/
│           ├── auth/           # Login, register, etc.
│           ├── admin/          # Admin pages
│           ├── student/        # Student pages
│           └── teacher/        # Teacher pages
├── routes/
│   ├── web.php
│   └── auth.php
└── ROADMAP.md                 # Development roadmap
```

## Database Schema

- **users** - Tài khoản (giáo viên/học sinh)
- **classes** - Lớp học
- **courses** - Khóa học
- **quizzes** - Bài kiểm tra
- **questions** - Câu hỏi
- **assignments** - Bài tập
- **submissions** - Bài nộp
- **grades** - Điểm số (polymorphic)
- **notifications** - Thông báo
- **tickets** - Yêu cầu hỗ trợ
- **vip_subscriptions** - Đăng ký VIP
- **vip_payments** - Thanh toán VIP
- **quiz_folders** - Thư mục quiz
- **question_folders** - Thư mục ngân hàng câu hỏi
- **class_user** - Liên kết lớp - học sinh
- **course_user** - Liên kết khóa học - học sinh
- **quiz_user** - Quiz attempts

## Lộ trình phát triển

Xem [ROADMAP.md](./ROADMAP.md) để biết chi tiết các giai đoạn phát triển.

### Đã hoàn thành
- ✅ Authentication (Login/Register)
- ✅ Teacher Dashboard
- ✅ Student Dashboard
- ✅ CRUD Classes, Courses, Quizzes, Assignments
- ✅ Grading system
- ✅ Analytics views
- ✅ Soft delete & trash management
- ✅ Quiz creation and quiz-taking flow
- ✅ Support tickets for students and teachers
- ✅ Admin panel rebuild

### Đang phát triển
- 🟡 SMTP production configuration
- 🟡 Deadline reminders via scheduler
- 🟡 Authorization policies
- 🟡 Deployment hardening

## Đóng góp

1. Fork repository
2. Tạo feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push branch (`git push origin feature/amazing-feature`)
5. Tạo Pull Request

## Bảo mật

Nếu phát hiện lỗ hổng bảo mật, vui lòng gửi email thay vì tạo issue công khai.

## License

Dự án này sử dụng MIT License.
