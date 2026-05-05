<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="VietQuiz — Nền tảng Kiểm tra Đánh giá Toàn diện hàng đầu Việt Nam. Tạo đề thi, chấm điểm tự động, phân tích học sinh." />
  <title>VietQuiz — Nền tảng Học tập & Kiểm tra Thông minh</title>

  <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
  <meta name="theme-color" content="#2563eb">

  @vite(['resources/css/app.css'])

  @stack('styles')
</head>
<body>
  @yield('body')
  <div id="toast-container"></div>

  @vite(['resources/js/app.js'])
  @stack('scripts')
</body>
</html>
