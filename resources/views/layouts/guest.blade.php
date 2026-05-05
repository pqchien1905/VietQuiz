<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'VietQuiz')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="theme-color" content="#2563eb">
    <link rel="stylesheet" href="{{ asset('build/assets/app-i6Rajjet.css') }}" />
    <script type="module" src="{{ asset('build/assets/app-CDytjvPd.js') }}"></script>
</head>
<body>
    @yield('body')
</body>
</html>
