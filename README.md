# VietQuiz

VietQuiz là ứng dụng quản lý học tập và kiểm tra trực tuyến được xây dựng bằng Laravel, Blade, Tailwind CSS, Alpine.js và Vite. Ứng dụng tập trung vào quy trình dạy học phổ thông/đào tạo nội bộ: giáo viên tạo lớp, khóa học, ngân hàng câu hỏi, bài kiểm tra, bài tập; học sinh tham gia lớp, làm bài, nộp bài và theo dõi điểm; quản trị viên giám sát toàn hệ thống.

## Mục lục

- [Chức năng chính](#chức-năng-chính)
- [Công nghệ sử dụng](#công-nghệ-sử-dụng)
- [Yêu cầu hệ thống](#yêu-cầu-hệ-thống)
- [Cài đặt nhanh](#cài-đặt-nhanh)
- [Cài đặt thủ công](#cài-đặt-thủ-công)
- [Cấu hình môi trường](#cấu-hình-môi-trường)
- [Tài khoản demo](#tài-khoản-demo)
- [Lệnh thường dùng](#lệnh-thường-dùng)
- [Cấu trúc thư mục](#cấu-trúc-thư-mục)
- [Kiểm thử](#kiểm-thử)

## Chức năng chính

### Xác thực và tài khoản

- Đăng ký, đăng nhập, đăng xuất.
- Quên mật khẩu, đặt lại mật khẩu qua email.
- Xác minh email theo luồng Laravel Breeze.
- Hồ sơ cá nhân, đổi thông tin, đổi mật khẩu.
- Phân quyền theo vai trò `teacher` và `student`.
- Hỗ trợ tài khoản kép để chuyển đổi giữa vai trò giáo viên và học sinh.

### Phân hệ giáo viên

- Dashboard tổng quan hoạt động giảng dạy.
- Quản lý lớp học: tạo/sửa/xóa, lưu trữ/khôi phục, mã lớp, link tham gia, danh sách học sinh.
- Mời học sinh bằng email hoặc link, import danh sách học sinh, export danh sách.
- Quản lý khóa học: tạo/sửa/xóa, xuất bản/hủy xuất bản, nhân bản khóa học, đồng bộ học sinh từ lớp.
- Quản lý ngân hàng câu hỏi theo thư mục.
- Tạo câu hỏi thủ công, import file/CSV, tạo câu hỏi bằng AI khi cấu hình API.
- Quản lý bài kiểm tra: thư mục đề, thời gian làm bài, trộn đáp án, chống gian lận, phân công theo lớp/khóa học/học sinh.
- Chấm điểm bài làm, xem bài nộp, tải file đính kèm, export bảng điểm.
- Quản lý bài tập có hạn nộp và file đính kèm.
- Thống kê/analytics và xuất báo cáo.
- Trung tâm thông báo, hỗ trợ/ticket, cài đặt, thùng rác và gói VIP.

### Phân hệ học sinh

- Dashboard tổng quan lớp, khóa học, bài kiểm tra, bài tập và điểm.
- Tham gia lớp bằng mã lớp hoặc link mời.
- Xem lớp học, khóa học, giáo viên, nội dung được giao.
- Làm bài kiểm tra trực tuyến, nộp bài, xem kết quả.
- Nộp bài tập kèm file đính kèm.
- Theo dõi điểm theo bài kiểm tra/bài tập.
- Nhận thông báo, gửi ticket hỗ trợ, cập nhật hồ sơ/cài đặt.
- Quản lý thùng rác dữ liệu cá nhân và gói VIP.

### Phân hệ quản trị

- Trang admin riêng tại `/admin`.
- Đăng nhập admin bằng thông tin trong `.env`.
- Dashboard hệ thống, tìm kiếm và analytics.
- Quản lý người dùng, lớp học, khóa học, bài kiểm tra, câu hỏi, bài tập.
- Quản lý bài nộp, điểm, thông báo, ticket hỗ trợ.
- Quản lý gói VIP, đăng ký VIP, thanh toán VIP và khuyến mãi.
- Quản lý thùng rác: khôi phục hoặc xóa vĩnh viễn từng mục/nhiều mục/toàn bộ.

### VIP, thanh toán và giới hạn gói miễn phí

- Gói miễn phí giới hạn số lớp học, câu hỏi ngân hàng và số câu hỏi trong mỗi đề.
- Gói VIP mở rộng giới hạn và bật một số tính năng nâng cao như AI/export.
- Tích hợp VNPay sandbox/production qua `VNPAY_TMN_CODE`, `VNPAY_HASH_SECRET`, `VNPAY_PAYMENT_URL`.
- Có callback return URL và IPN cho VNPay.

### Tích hợp bổ sung

- Gửi email SMTP cho reset password, mời/giao bài và thông báo liên quan.
- Tạo câu hỏi bằng AI qua endpoint cấu hình trong `.env`.
- Đọc/ghi dữ liệu Excel bằng `phpoffice/phpspreadsheet`.
- Hỗ trợ LibreOffice để chuyển đổi/preview tài liệu nếu cấu hình `LIBREOFFICE_PATH`.

## Công nghệ sử dụng

- PHP `^8.3`
- Laravel `^13.0`
- Laravel Breeze
- Laravel Blade
- Tailwind CSS 3
- Alpine.js
- Vite 5
- SQLite mặc định theo `.env.example` hoặc MySQL/MariaDB nếu cấu hình lại
- PHPUnit 12
- phpoffice/phpspreadsheet

## Yêu cầu hệ thống

- PHP 8.3 trở lên.
- Composer.
- Node.js và npm.
- SQLite hoặc MySQL/MariaDB.
- Extension PHP thường dùng cho Laravel: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`.
- Với import/export Excel: các extension mà PhpSpreadsheet yêu cầu, thường gồm `zip`, `xml`, `gd` hoặc tương đương tùy môi trường.
- Với gửi email thật: tài khoản SMTP.
- Với preview/chuyển đổi tài liệu: LibreOffice.

Nếu dùng Laragon trên Windows, đặt project trong `C:\laragon\www\VietQuiz`, bật Apache/Nginx và MySQL/MariaDB nếu không dùng SQLite.

## Cài đặt nhanh

Repository có script Composer `setup` để cài dependency, tạo `.env`, sinh app key, migrate database, cài npm và build frontend.

```bash
composer run setup
```

Sau đó chạy ứng dụng:

```bash
composer run dev
```

Lệnh `composer run dev` chạy đồng thời:

- Laravel dev server.
- Queue listener.
- Laravel Pail để xem log.
- Vite dev server.

Mặc định ứng dụng thường chạy tại:

```text
http://127.0.0.1:8000
```

## Cài đặt thủ công

### 1. Clone source code

```bash
git clone <repository-url> VietQuiz
cd VietQuiz
```

Nếu đã có source trong Laragon:

```bash
cd C:\laragon\www\VietQuiz
```

### 2. Cài dependency PHP

```bash
composer install
```

### 3. Cài dependency frontend

```bash
npm install
```

### 4. Tạo file môi trường

```bash
cp .env.example .env
```

Trên PowerShell:

```powershell
Copy-Item .env.example .env
```

### 5. Sinh khóa ứng dụng

```bash
php artisan key:generate
```

### 6. Cấu hình database

Mặc định `.env.example` dùng SQLite:

```env
DB_CONNECTION=sqlite
```

Tạo file database nếu chưa có:

```bash
touch database/database.sqlite
```

Trên PowerShell:

```powershell
New-Item -ItemType File -Force database/database.sqlite
```

Nếu dùng MySQL/MariaDB, sửa `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vietquiz
DB_USERNAME=root
DB_PASSWORD=
```

Sau đó tạo database `vietquiz` trong MySQL/MariaDB.

### 7. Chạy migration

```bash
php artisan migrate
```

Muốn nạp dữ liệu demo:

```bash
php artisan db:seed
```

Hoặc reset database và nạp demo:

```bash
php artisan migrate:fresh --seed
```

### 8. Build hoặc chạy frontend

Môi trường development:

```bash
npm run dev
```

Build production:

```bash
npm run build
```

### 9. Chạy server Laravel

```bash
php artisan serve
```

Mở:

```text
http://127.0.0.1:8000
```

## Cấu hình môi trường

Các biến quan trọng trong `.env`:

```env
APP_NAME=VietQuiz
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
```

### Database

SQLite:

```env
DB_CONNECTION=sqlite
```

MySQL/MariaDB:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vietquiz
DB_USERNAME=root
DB_PASSWORD=
```

### Queue, session và cache

`.env.example` dùng database cho session, queue và cache:

```env
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Sau khi migrate, có thể chạy queue worker:

```bash
php artisan queue:listen --tries=1 --timeout=0
```

### Email SMTP

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="your-gmail@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Với Gmail, nên dùng App Password thay vì mật khẩu tài khoản chính.

### Admin

```env
ADMIN_USERNAME=admin
ADMIN_PASSWORD=password
```

Đường dẫn admin:

```text
/admin
```

### AI tạo câu hỏi

```env
AI_QUESTION_API_KEY=
AI_QUESTION_API_URL=http://localhost:20128/v1/messages
AI_QUESTION_MODEL=gh/gpt-4o-mini
AI_QUESTION_API_ADAPTER=anthropic_messages
AI_QUESTION_TIMEOUT=45
```

Nếu không cấu hình API key/endpoint hợp lệ, chức năng tạo câu hỏi bằng AI sẽ không hoạt động.

### LibreOffice

```env
LIBREOFFICE_PATH=
```

Trên Windows, ví dụ:

```env
LIBREOFFICE_PATH="C:\Program Files\LibreOffice\program\soffice.exe"
```

### VNPay

```env
VNPAY_TMN_CODE=
VNPAY_HASH_SECRET=
VNPAY_PAYMENT_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
VNPAY_VERSION=2.1.0
```

Callback đang được khai báo:

```text
/vip/vnpay-return
/vip/vnpay-ipn
```

Khi chạy local và cần VNPay gọi IPN, cần public tunnel như ngrok hoặc cấu hình domain có thể truy cập từ Internet.

## Tài khoản demo

Seeder chính `VietQuizDemoSeeder` tạo dữ liệu demo với tài khoản:

```text
Email: pqchien1905@gmail.com
Password: password
Vai trò: teacher, có thể chuyển sang student
```

Một số tài khoản học sinh demo:

```text
Email: student01@vietquiz.test
Password: password
Vai trò: student
```

```text
Email: student02@vietquiz.test
Password: password
Vai trò: student
```

Tài khoản admin dùng biến `.env`:

```text
Username: admin
Password: password
URL: /admin
```

## Lệnh thường dùng

Chạy toàn bộ môi trường dev:

```bash
composer run dev
```

Chạy Laravel server:

```bash
php artisan serve
```

Chạy Vite:

```bash
npm run dev
```

Build frontend:

```bash
npm run build
```

Chạy migration:

```bash
php artisan migrate
```

Reset database và seed demo:

```bash
php artisan migrate:fresh --seed
```

Chạy queue:

```bash
php artisan queue:listen --tries=1 --timeout=0
```

Xóa cache cấu hình/view/route:

```bash
php artisan optimize:clear
```

Format code PHP theo Laravel Pint:

```bash
./vendor/bin/pint
```

Trên Windows PowerShell:

```powershell
vendor\bin\pint
```

## Cấu trúc thư mục

```text
app/
  Http/Controllers/
    Admin/      Controller cho trang quản trị
    Teacher/    Controller cho giáo viên
    Student/    Controller cho học sinh
    Shared/     Hồ sơ, cài đặt, thông báo, VIP, hỗ trợ, thùng rác
  Models/       Model Eloquent
  Support/      Helper nghiệp vụ như giới hạn VIP

database/
  migrations/   Schema database
  seeders/      Dữ liệu demo

resources/
  views/
    layouts/    Layout chung
    pages/
      admin/    Giao diện admin
      auth/     Đăng nhập/đăng ký/quên mật khẩu
      teacher/  Giao diện giáo viên
      student/  Giao diện học sinh
  css/          CSS của ứng dụng
  js/           JavaScript frontend

routes/
  web.php       Route chính của ứng dụng
  auth.php      Route xác thực

tests/
  Feature/      Feature tests theo phân hệ
```

## Kiểm thử

Chạy toàn bộ test:

```bash
composer run test
```

Hoặc:

```bash
php artisan test
```

Chạy một nhóm test cụ thể:

```bash
php artisan test tests/Feature/Teacher
php artisan test tests/Feature/Student
php artisan test tests/Feature/Admin
```

## Ghi chú phát triển

- Không commit file `.env`, `vendor/`, `node_modules/`, file database local hoặc file build tạm.
- Sau khi thay đổi `.env`, nên chạy `php artisan optimize:clear`.
- Khi đổi schema, tạo migration mới thay vì sửa migration đã dùng chung.
- Với tính năng dùng queue/email/thanh toán, nên test cả luồng thành công và thất bại.
- Dữ liệu tiếng Việt trong một số seeder cũ có thể bị lỗi encoding; nếu cần demo đẹp, ưu tiên cập nhật nội dung seed bằng UTF-8.
