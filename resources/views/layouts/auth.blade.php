<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'VietQuiz')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="theme-color" content="#2563eb">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
<div class="auth-page" id="auth-page">
  <button class="icon-btn" data-theme-toggle style="position:fixed; top:1rem; right:1rem;" aria-label="Chuyển chủ đề">
    <span data-theme-icon></span>
  </button>
  <div class="auth-box animate-fade-in-up">
    @yield('auth-content')
  </div>
</div>
@stack('scripts')
</body>
</html>
