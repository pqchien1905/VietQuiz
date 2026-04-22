# VietQuiz - Hệ thống Thi Trực Tuyến

<p align="center">
    <img src="https://img.shields.io/badge/Laravel-13.x-red?style=flat-square&logo=laravel" alt="Laravel">
    <img src="https://img.shields.io/badge/PHP-8.4-blue?style=flat-square&logo=php" alt="PHP">
    <img src="https://img.shields.io/badge/MySQL-8.x-blue?style=flat-square&logo=mysql" alt="MySQL">
</p>

## Giới thiệu

**VietQuiz** là hệ thống thi trực tuyến được xây dựng bằng **Laravel 13** và **Blade**, hỗ trợ hai vai trò chính:

- **Giáo viên**: Tạo lớp học, khóa học, bài kiểm tra (quiz), bài tập. Chấm điểm và xem phân tích kết quả học sinh.
- **Học sinh**: Đăng ký lớp, làm bài kiểm tra, nộp bài tập, xem điểm và kết quả.

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

## Tech Stack

| Thành phần | Công nghệ |
|---|---|
| Backend | Laravel 13.x |
| Frontend | Blade + Vanilla JS |
| Database | MySQL 8.x |
| Auth | Laravel Breeze + Sanctum |
| CSS | Custom CSS Variables |
| Icons | Inline SVG |

## Yêu cầu hệ thống

- **PHP**: 8.2+
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
```

## Cấu trúc dự án

```
VietQuiz/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/            # REST API controllers
│   │   │   ├── Auth/           # Laravel Breeze auth
│   │   │   ├── Student/        # Student controllers
│   │   │   └── Teacher/        # Teacher controllers
│   │   └── Middleware/
│   │       └── CheckRole.php   # Role-based access
│   └── Models/                 # 10 Eloquent models
├── database/
│   └── migrations/             # Database schema
├── resources/
│   └── views/
│       ├── components/         # Reusable Blade components
│       ├── layouts/            # Layout templates
│       └── pages/
│           ├── auth/           # Login, register, etc.
│           ├── student/        # Student pages
│           └── teacher/        # Teacher pages
├── routes/
│   ├── web.php
│   ├── api.php
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
- **class_user** - Liên kết lớp - học sinh
- **course_user** - Liên kết khóa học - học sinh
- **quiz_user** - Quiz attempts

## Lộ trình phát triển

Xem [ROADMAP.md](./ROADMAP.md) để biết chi tiết các giai đoạn phát triển.

### Đã hoàn thành
- ✅ Authentication (Login/Register)
- ✅ Teacher Dashboard
- ✅ CRUD Classes, Courses, Quizzes, Assignments
- ✅ Grading system
- ✅ Soft delete & trash management
- ✅ REST API với Sanctum
- ✅ Quiz creation form (Giai đoạn 1)

### Đang phát triển
- 🟡 Quiz-taking UI cho học sinh
- 🟡 Analytics views
- 🟡 Grading views

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
