{{-- Login Page --}}
@extends('layouts.auth')

@php
    $selectedRole = request('role', 'teacher');
@endphp

@section('title', 'Đăng nhập — VietQuiz')

@php($description = 'Đăng nhập VietQuiz — Nền tảng học tập thông minh cho giáo viên và học sinh.')

@php($role = $role ?? request('role', 'teacher'))

@push('scripts')
<script>
  // Theme is handled by the main layout

  let activeRole = '{{ $role }}';

  window.selectRole = (role) => {
    activeRole = role;
    document.getElementById('tab-teacher').classList.toggle('active', role === 'teacher');
    document.getElementById('tab-student').classList.toggle('active', role === 'student');
    document.getElementById('selected-role').value = role;
  };

  // Password toggle
  const passwordInput = document.getElementById('password');
  const eyeBtn = document.getElementById('toggle-password');
  const eyeIcon = document.getElementById('eye-icon');
  eyeBtn?.addEventListener('click', () => {
    const isHidden = passwordInput.type === 'password';
    passwordInput.type = isHidden ? 'text' : 'password';
    eyeIcon.innerHTML = isHidden
      ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>`
      : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
  });

  function showError(id, msg) {
    document.getElementById(id).style.display = 'flex';
    document.getElementById(id + '-text').textContent = msg;
    document.getElementById(id.replace('-error', '')).classList.add('input-error');
  }
  function clearError(id) {
    document.getElementById(id).style.display = 'none';
    const input = document.getElementById(id.replace('-error', ''));
    if (input) input.classList.remove('input-error');
  }

  document.getElementById('email')?.addEventListener('input', () => clearError('email-error'));
  document.getElementById('password')?.addEventListener('input', () => clearError('password-error'));

  document.getElementById('login-form')?.addEventListener('submit', (e) => {
    // Let Laravel form validation handle everything
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.classList.add('loading');
    btn.textContent = 'Đang đăng nhập...';
  });

  // Pre-select role from URL
  @if($role === 'student')
    selectRole('student');
  @else
    selectRole('teacher');
  @endif
</script>
@endpush

@php($selectedRole = $role ?? request('role', 'teacher'))

@section('auth-content')
  <div class="auth-logo">
    <div class="auth-logo-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
    </div>
    <h1>Chào mừng trở lại</h1>
    <p>Đăng nhập vào tài khoản của bạn để tiếp tục</p>
  </div>

  <div class="card shadow-xl" style="border-width: 2px;">
    <div class="card-header">
      <h2 class="card-title" style="font-size: var(--text-2xl);">Đăng nhập</h2>
      <p class="card-description">Chọn vai trò và nhập thông tin đăng nhập</p>
    </div>
    <div class="card-content">
      <div class="form-group" style="margin-bottom: 1.25rem;">
        <label class="label">Tôi là...</label>
        <div class="role-tabs">
          <button class="role-tab {{ $selectedRole === 'teacher' ? 'active' : '' }}" id="tab-teacher" type="button" onclick="selectRole('teacher')">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            Giáo viên
          </button>
          <button class="role-tab {{ $selectedRole === 'student' ? 'active' : '' }}" id="tab-student" type="button" onclick="selectRole('student')">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Học sinh
          </button>
        </div>
      </div>

      <div class="alert alert-danger" id="general-error" style="display:none; margin-bottom: 1rem;">
        <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span id="general-error-text"></span>
      </div>

      @error('email')
      <div class="alert alert-danger" style="margin-bottom: 1rem;">
        <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>{{ $message }}</span>
      </div>
      @enderror

      <form method="POST" action="{{ route('login') }}" id="login-form" novalidate>
        @csrf
        <input type="hidden" name="role" id="selected-role" value="{{ $role ?? 'teacher' }}" />
        <div class="form-group" style="margin-bottom: 1rem;">
          <label class="label" for="email">Địa chỉ Email</label>
          <div class="input-wrapper">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <input type="email" id="email" name="email" class="input" placeholder="ten.ban@example.com" autocomplete="email" value="{{ old('email') }}" />
          </div>
          <p class="form-error" id="email-error" style="display:none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span id="email-error-text"></span>
          </p>
        </div>

        <div class="form-group" style="margin-bottom: 1rem;">
          <div class="flex items-center justify-between" style="margin-bottom: 0.375rem;">
            <label class="label" for="password" style="margin: 0;">Mật khẩu</label>
            <a href="{{ route('password.request') }}" style="font-size: var(--text-sm); color: var(--primary);">Quên mật khẩu?</a>
          </div>
          <div class="input-wrapper">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input type="password" id="password" name="password" class="input" placeholder="Mật khẩu" autocomplete="current-password" />
            <button type="button" class="input-icon-right" id="toggle-password" aria-label="Hiện/ẩn mật khẩu">
              <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <p class="form-error" id="password-error" style="display:none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span id="password-error-text"></span>
          </p>
        </div>

        <div class="checkbox-wrapper" style="margin-bottom: 1.25rem;">
          <input type="checkbox" id="remember" name="remember" />
          <label for="remember" style="font-size: var(--text-sm); font-weight: 400; cursor: pointer;">Ghi nhớ tôi trong 30 ngày</label>
        </div>

        <button type="submit" class="btn btn-primary w-full btn-lg" id="submit-btn">
          Đăng nhập
        </button>
      </form>

      <p style="text-align: center; margin-top: 1rem; font-size: var(--text-sm); color: var(--muted-foreground);">
        Chưa có tài khoản?
        <a href="{{ route('register') }}" style="font-weight: 600; color: var(--primary);">Tạo tài khoản</a>
      </p>
    </div>
  </div>

  <div class="card demo-card" style="border-style: dashed; background: var(--muted);">
    <div class="card-content" style="padding: 1rem;">
      <p style="font-weight: 500; font-size: var(--text-sm); color: var(--muted-foreground); margin-bottom: 0.5rem;">Thông tin đăng nhập Demo:</p>
      <div style="font-size: var(--text-sm); color: var(--muted-foreground); display: flex; flex-direction: column; gap: 0.25rem;">
        <span>Giáo viên: teacher@demo.com / password123</span>
        <span>Học sinh: student@demo.com / password123</span>
      </div>
    </div>
  </div>
@endsection
