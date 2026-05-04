{{-- Forgot Password Page --}}
@extends('layouts.auth')
@section('title', 'Quên mật khẩu - VietQuiz')

@section('auth-content')
  <div class="auth-logo">
    <div class="auth-logo-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
      </svg>
    </div>
    <h1 style="font-size:var(--text-2xl);font-weight:700;">VietQuiz</h1>
  </div>

  <div class="card fade-in">
    <div class="card-header" style="text-align:center;">
      <div style="width:3.25rem;height:3.25rem;border-radius:999px;background:color-mix(in srgb,var(--primary) 12%,transparent);display:flex;align-items:center;justify-content:center;margin:0 auto .875rem;color:var(--primary);">
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M4 4h16v16H4z"/>
          <path d="m22 6-10 7L2 6"/>
        </svg>
      </div>
      <h2 class="card-title" style="font-size:var(--text-xl);">Quên mật khẩu?</h2>
      <p class="card-description">Nhập email đã đăng ký, chúng tôi sẽ gửi liên kết đặt lại mật khẩu cho bạn.</p>
    </div>

    <div class="card-content" style="display:flex;flex-direction:column;gap:1rem;">
      @if (session('status'))
        <div class="alert alert-success">
          <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M20 6 9 17l-5-5"/>
          </svg>
          <span>{{ session('status') }}</span>
        </div>
      @endif

      @error('email')
        <div class="alert alert-danger">
          <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <span>{{ $message }}</span>
        </div>
      @enderror

      <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="form-group" style="margin-bottom:1rem;">
          <label class="label label-required" for="email">Địa chỉ email</label>
          <div class="input-with-icon">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
            <input type="email" class="input has-icon @error('email') input-error @enderror" name="email" id="email" placeholder="ten.ban@example.com" value="{{ old('email') }}" autocomplete="email" required autofocus />
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-full">Gửi liên kết đặt lại mật khẩu</button>
      </form>

      <div class="alert alert-info" style="font-size:var(--text-xs);">
        <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        Liên kết đặt lại mật khẩu sẽ hết hạn sau <strong>10 phút</strong>.
      </div>
    </div>

    <div class="card-footer" style="justify-content:center;">
      <a href="{{ route('login') }}" class="btn btn-ghost btn-sm gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
        Quay lại đăng nhập
      </a>
    </div>
  </div>
@endsection
